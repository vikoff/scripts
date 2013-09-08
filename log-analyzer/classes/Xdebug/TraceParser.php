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

	public static function integrityCheck()
	{
		$iteration = 1;
		$db = db::get();
		$sql = "SELECT id, parent_func_id FROM xdebug_trace WHERE all_parent_ids IS NULL LIMIT 1000";

		while ($ids = $db->fetchPairs($sql)) {
			foreach ($ids as $funcId => $parentId) {
				$parentsDesc = array();
				if ($parentId)
					$parentsDesc[] = $parentId;
				while ($parentId) {
					$parents = $db->fetchRow("
						SELECT t1.parent_func_id parent1, t2.parent_func_id parent2, t3.parent_func_id parent3
						FROM xdebug_trace t1
						LEFT JOIN xdebug_trace t2 ON t2.id=t1.parent_func_id
						LEFT JOIN xdebug_trace t3 ON t3.id=t2.parent_func_id
						WHERE t1.id=?
					", $parentId);
					for ($i = 1; $i <= 3; $i++) {
						if ($parents && (int)$parents['parent'.$i])
							$parentsDesc[] = $parents['parent'.$i];
					}
					$parentId = $parents ? (int)$parents['parent3'] : 0;
				}

				echo count($parentsDesc).'|';
				$parentsDesc = array_reverse($parentsDesc);
				$db->update('xdebug_trace', array(
					'all_parent_ids' => $parentsDesc ? implode(',', $parentsDesc) : ''
				), 'id=?', $funcId);

				if ($iteration % 10 == 0) echo ".";
				if ($iteration % 1000 == 0) echo " $iteration\n";
				$iteration++;
			}
		}
		echo "\ncomplete\n";
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

		// инкремент счетчиков вложенных вызовов для всех функций
		foreach ($this->_nestedCalls as $callIndex => $numCalls)
			if ($callIndex != $data['call_index'])
				$this->_nestedCalls[$callIndex]++;

		// сбор списка родительских функций, удаление стека глубже текущего уровня
		ksort($this->_levels);
		$allParentIds = array();
		foreach ($this->_levels as $level => $levelData) {
			if ($level < $data['level']) {
				$allParentIds[] = $levelData['id'];
			} elseif ($level > $data['level']) {
				unset($this->_levels[$level]);
			}
		}

		$data['all_parent_ids'] = implode(',', $allParentIds);
		$id = $db->insert('xdebug_trace', $data);
		$this->_numInserts++;

		// установим функцию как текущую на данном уровне
		$this->_levels[ $data['level'] ] = array('id' => $id);
		ksort($this->_levels);
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
