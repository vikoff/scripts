<?php
/**
 * User: yuriy
 * Date: 05.09.13 20:47
 */

class Xdebug_TraceParser
{
	protected $_options;
	protected $_sessId;
	protected $_sessData = array();
	protected $_lineNumber = 0;
	protected $_numInserts = 0;
	protected $_prevRowData = array();

	/**
	 * nested calls counter for each function
	 * @var array ('call_index' => 'num_nested_calls')
	 */
	protected $_nestedCalls = array();

	protected $_levels = array();
	protected $_curLevel = 1;

	public function __construct($options = array())
	{
		$this->_options = $options;
	}

	public function parse($file)
	{
		if (!file_exists($file))
			throw new Exception("file '$file' is not exists");

		if (!is_readable($file))
			throw new Exception("file '$file' is not readable");

		$this->_createSession();

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
			'db_table' => 'xdebug_trace',
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
			"SELECT MAX(memory_end), MAX(time_end), COUNT(1) FROM xdebug_trace WHERE sess_id=?", $sessId));
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
			// если остались данные с прошлой итерации, сохраним их
			if ($this->_prevRowData) {
				$this->_saveData($this->_prevRowData);
				$this->_prevRowData = array();
			}
			$keyValues['time_start'] = $keyValues['time'];
			$keyValues['memory_start'] = $keyValues['memory'];
			unset($keyValues['part'], $keyValues['time'], $keyValues['memory']);
			// не сохраняем данные сразу, а записываем в переменную,
			// т.к. на следующей итерации может быть finish этой же функции
			$this->_prevRowData = $keyValues;
			// начинаем счетчик вложенных вызовов для данной функции
			$this->_nestedCalls[ $keyValues['call_index'] ] = 0;
		} elseif ($keyValues['part'] == '1') {
			// если предыдущая start строка была об этой же фунции, сохраним все start и finish данные вместе
			if ($this->_prevRowData && $this->_prevRowData['call_index'] == $keyValues['call_index']) {
				$this->_prevRowData['time_end'] = $keyValues['time'];
				$this->_prevRowData['memory_end'] = $keyValues['memory'];
				$this->_prevRowData['num_nested_calls'] = $this->_nestedCalls[ $keyValues['call_index'] ];
				$this->_saveData($this->_prevRowData);
				$this->_prevRowData = array();
				// закрываем счетчик вложенных вызовов для данной функции
				unset($this->_nestedCalls[ $keyValues['call_index'] ]);
			}
			// иначе сохраним данные предыдущей и текущей строк отдельно
			else {
				if ($this->_prevRowData) {
					$this->_saveData($this->_prevRowData);
					$this->_prevRowData = array();
				}
				$this->_saveFuncFinishData($keyValues['call_index'], $keyValues['time'], $keyValues['memory']);
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

		$id = $db->insert('xdebug_trace', $data);
		$this->_numInserts++;

		// инкремент счетчиков вложенных вызовов для всех функций
		foreach ($this->_nestedCalls as $callIndex => $numCalls)
			if ($callIndex != $data['call_index'])
				$this->_nestedCalls[$callIndex]++;

		// установим функцию как текущую на данном уровне
		$this->_levels[ $data['level'] ] = array('id' => $id);

		ksort($this->_levels);
		foreach ($this->_levels as $level => $levelData) {
			if ($level > $data['level']) {
				unset($this->_levels[$level]);
			}
		}
	}

	protected function _saveFuncFinishData($callIndex, $timeEnd, $memoryEnd)
	{
		db::get()->update('xdebug_trace', array(
			'time_end' => $timeEnd,
			'memory_end' => $memoryEnd,
			'num_nested_calls' => $this->_nestedCalls[$callIndex],
		), 'sess_id=? AND call_index=?', array($this->_sessId, $callIndex));

		// закрываем счетчик вложенных вызовов для данной функции
		unset($this->_nestedCalls[$callIndex]);
	}

	protected function _saveUnfinishedRows()
	{
		if (!$this->_levels)
			return;

		$db = db::get();
		$ids = $db->fetchPairs("SELECT id, call_index FROM xdebug_trace WHERE sess_id=? AND time_end IS NULL", $this->_sessId);
		if (!$ids)
			return;

		list($time, $memory) = array_values($db->fetchRow(
			"SELECT MAX(time_end), MAX(memory_end) FROM xdebug_trace WHERE sess_id=?",
			$this->_sessId));

		echo "\nsave ".count($ids)." unfinished calls\n";

		foreach ($ids as $id => $callIndex) {
			$db->update('xdebug_trace', array(
				'time_end' => $time,
				'memory_end' => $memory,
				'num_nested_calls' => $this->_nestedCalls[$callIndex],
			), 'id=?', $id);
		}
	}

}
