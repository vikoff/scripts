<?php
/**
 * User: yuriy
 * Date: 05.09.13 20:47
 */

class Xdebug_TraceParser
{
	protected $_dbTable;
	protected $_options;
	protected $_sessId = 1;
	protected $_sessData = array();
	protected $_lineNumber = 0;
	protected $_numInserts = 0;
	protected $_prevRowData = array();

	protected $_levels = array();
	protected $_curLevel = 1;

	public function __construct($options = array())
	{
		$this->_dbTable = !empty($options['db_table']) ? $options['db_table'] : 'xdebug_trace';
		$this->_options = $options;
	}

	public function parse($file)
	{
		if (!file_exists($file))
			throw new Exception("file '$file' is not exists");

		if (!is_readable($file))
			throw new Exception("file '$file' is not readable");

		$this->_createSession();
		$this->_checkDbTable();

		$db = db::get();
		$db->beginTransaction();

		$this->_lineNumber = 0;
		$startTime = microtime(1);
		$rs = fopen($file, 'r');

		while (!feof($rs)) {
			$this->_lineNumber++;
			$line = trim(fgets($rs));
			$this->_processLine($line);
		}

		$db->commit();
		$this->_saveUnfinishedRows();
		$this->updateSessionData($this->_sessId);

		if (!empty($this->_options['remove_after'])) {
			$unlinked = unlink($file);
			echo "\n".($unlinked ? "file $file removed" : "unable to remove file $file" )."\n";
		}

		$duration = sprintf('%.4f', microtime(1) - $startTime);
		echo "\nCURRENT SESSION INDEX: $this->_sessId\n"
			."$this->_numInserts row inserted in $duration sec ($this->_lineNumber rows parsed)\n\n";
	}

	protected function _createSession()
	{
		$keys = array('application', 'request_url', 'app_base_path', 'comments');
		$data = array(
			'db_table' => $this->_dbTable,
		);
		foreach ($keys as $key)
			if (!empty($this->_options[$key]))
				$data[$key] = $this->_options[$key];

		$db = db::get();
		$this->_sessId = $db->insert('xdebug_trace_sessions', $data);
		$this->_sessData = $db->fetchRow("SELECT * FROM xdebug_trace_sessions WHERE id=?", $this->_sessId);
	}

	public function updateSessionData($sessId)
	{
		$db = db::get();
		list($maxMem, $maxTime, $numCalls) = array_values($db->fetchRow(
			"SELECT MAX(memory_end), MAX(time_end), COUNT(1) FROM $this->_dbTable WHERE sess_id=?", $sessId));
		$db->update('xdebug_trace_sessions', array(
			'total_memory' => $maxMem,
			'total_time' => $maxTime,
			'total_calls' => $numCalls,
		), 'id=?', $sessId);
	}

	protected function _processLine($line)
	{
		$fields = array(
			0 => 'level',
			1 => 'call_index',
			2 => 'part',
			3 => 'time',
			4 => 'memory',
			5 => 'func_name',
			6 => 'user_defined',
			7 => 'included_file',
			8 => 'call_file',
			9 => 'call_line',
			10 => 'num_args',
			11 => 'args',
		);
		$numFields = count($fields);

		$parts = explode("\t", $line, $numFields);
		if (count($parts) < 3)
			return;

		if ($this->_lineNumber % 100 == 0) echo ".";
		if ($this->_lineNumber % 10000 == 0) echo " $this->_lineNumber\n";

		$keyValues = array_combine($fields, $parts + array_fill(0, $numFields, ''));
		if ($keyValues['part'] == '0') {
			if ($this->_prevRowData) {
				$this->_saveData($this->_prevRowData);
				$this->_prevRowData = array();
			}
			$keyValues['time_start'] = $keyValues['time'];
			$keyValues['memory_start'] = $keyValues['memory'];
			unset($keyValues['part'], $keyValues['time'], $keyValues['memory']);
			$this->_prevRowData = $keyValues;
		} elseif ($keyValues['part'] == '1') {
			// если предыдущая start строка была об этой же фунции, сохраним все start и finish данные вместе
			if ($this->_prevRowData && $this->_prevRowData['call_index'] == $keyValues['call_index']) {
				$this->_prevRowData['time_end'] = $keyValues['time'];
				$this->_prevRowData['memory_end'] = $keyValues['memory'];
				$this->_saveData($this->_prevRowData);
				$this->_prevRowData = array();
			}
			// иначе сохраним данные предыдущей и текущей строк отдельно
			else {
				if ($this->_prevRowData) {
					$this->_saveData($this->_prevRowData);
					$this->_prevRowData = array();
				}
				$this->_saveLeavingStackData($keyValues['level'], $keyValues['call_index'], $keyValues['time'], $keyValues['memory']);
			}
		}
	}

	protected function _saveData($data)
	{
		$data['sess_id'] = $this->_sessId;
		$data['parent_func_id'] = $data['level'] == 1 ? 0 : $this->_levels[ $data['level'] - 1 ]['id'];

		$db = db::get();

		if ($this->_numInserts % 100 == 0) {
			$db->commit();
			$db->beginTransaction();
		}

		$id = $db->insert($this->_dbTable, $data);
		$this->_numInserts++;

		// сохраним num_nested_calls у предыдущей фунции данного уровня
		if (!empty($this->_levels[ $data['level'] ]))
			$this->_saveNestedCalls($this->_levels[ $data['level'] ]);

		// установим функцию как текущую на данном уровне
		$this->_levels[ $data['level'] ] = array('id' => $id, 'num_nested_calls' => 0);

		ksort($this->_levels);
		foreach ($this->_levels as $level => $levelData) {
			if ($level < $data['level']) {
				$this->_levels[$level]['num_nested_calls']++;
			} elseif ($level > $data['level']) {
				$this->_saveNestedCalls($this->_levels[$level]);
				unset($this->_levels[$level]);
			}
		}
	}

	protected function _saveLeavingStackData($level, $callIndex, $timeEnd, $memoryEnd)
	{
		db::get()->update($this->_dbTable, array(
			'time_end' => $timeEnd,
			'memory_end' => $memoryEnd,
		), 'sess_id=? AND level=? AND call_index=?', array($this->_sessId, $level, $callIndex));
	}

	protected function _saveNestedCalls($data)
	{
		db::get()->update($this->_dbTable, array('num_nested_calls' => $data['num_nested_calls']), 'id=?', $data['id']);
	}

	protected function _saveUnfinishedRows()
	{
		if (!$this->_levels)
			return;

		$db = db::get();
		$searchIds = array();
		$idNumCalls = array();
		foreach ($this->_levels as $level) {
			$searchIds[] = $level['id'];
			$idNumCalls[ $level['id'] ] = $level['num_nested_calls'];
		}

		$ids = $db->fetchCol(
			"SELECT * FROM $this->_dbTable WHERE sess_id=? AND id IN(".implode(',', $searchIds).") AND time_end IS NULL",
			$this->_sessId);

		if (!$ids)
			return;

		list($time, $memory) = array_values($db->fetchRow(
			"SELECT MAX(time_end), MAX(memory_end) FROM $this->_dbTable WHERE sess_id=?",
			$this->_sessId));

		echo "\nsave ".count($ids)." unfinished calls\n";

		foreach ($ids as $id) {
			$db->update($this->_dbTable, array(
				'time_end' => $time,
				'memory_end' => $memory,
				'num_nested_calls' => $idNumCalls[$id],
			), 'id=?', $id);
		}
	}

	protected function _checkDbTable()
	{
		$db = db::get();
		if (in_array($this->_dbTable, $db->showTables())) {
		// иначе созданим таблицу
		} else {
			throw new Exception('db table creation not implemented');
		}
	}
}