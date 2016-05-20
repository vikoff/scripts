<?php

class ParserSqlLog
{
	const ROW_CONNECT = 1;
	const ROW_QUERY_START = 2;
	const ROW_QUERY_CONTINUE = 3;
	const ROW_QUITE = 4;

	protected $_totalLines = 0;
	protected $_lineNumber = 0;
	protected $_numInserts = 0;
	protected $_file;

	protected $_curLogDate;
	protected $_prevRowType;
	protected $_prevQuery = array();
	protected $_sessId;

	public function __construct($file)
	{
		$this->_file = $file;

		if (!file_exists($file))
			throw new Exception("file '$file' is not exists");

		if (!is_readable($file))
			throw new Exception("file '$file' is not readable");
	}

	public function parse()
	{
		$this->_totalLines = trim(`cat '$this->_file' | wc -l`);
		$this->_createSession();

		echo "create parse session #$this->_sessId\n";
		echo "totally $this->_totalLines lines\n";

		$db = db::get();
		$db->beginTransaction();

		$this->_lineNumber = 0;
		$startTime = microtime(1);
		$rs = fopen($this->_file, 'r');

		while (!feof($rs)) {
			$this->_lineNumber++;
			$line = fgets($rs);
			$this->_processLine($line);
		}

		$this->_processLastLine();

		list($minDate, $maxDate) = array_values($db->fetchRow("SELECT MIN(date), MAX(date) FROM sql_log WHERE sess_id=?", $this->_sessId));
		$db->update('sql_log_sessions', array(
			'total_queries' => $this->_numInserts,
			'date_first' => $minDate,
			'date_last' => $maxDate,
			'processed_at' => $db->raw('NOW()'),
		), 'id=?', $this->_sessId);

		$db->commit();

		$duration = sprintf('%.2f', microtime(1) - $startTime);

		echo "\n\nSession #$this->_sessId. $this->_numInserts sqls inserted in $duration sec ($this->_lineNumber rows parsed)\n\n";
	}

	public function _processLine($line)
	{
		if ($this->_lineNumber < 4)
			return;

		if ($this->_lineNumber % 100 == 0) echo ".";
		if ($this->_lineNumber % 10000 == 0) {
			$percent = $this->_totalLines ? round($this->_lineNumber/$this->_totalLines * 100) : 100;
			echo " parse $this->_lineNumber lines ($percent%)\n";
		}

		$reTime = '(\d{6})\s+(\d{1,2}:\d{1,2}:\d{1,2})\s+';

		// основная строка лога
		if (preg_match("/^(?:\t\t|$reTime)(\d+) ([a-zA-Z]+)\s+(.*)/", $line, $matches)) {

			// сохранение предыдущей строки (запрос может быть на несколько строк)
			if ($this->_prevQuery) {
				$this->_saveData($this->_prevQuery);
				$this->_prevQuery = array();
			}

			// если передано время
			if ($matches[1] && $matches[2]) {
				$date = '20'.implode('-', str_split($matches[1], 2)).' '.$matches[2];
				$this->_curLogDate = new DateTime($date);
			}

			// не обрабатываем строки лога если нам не известно время
			if (!$this->_curLogDate)
				return;

			$connId = $matches[3];
			$command = strtolower($matches[4]);

			// запрос может быть многострочным, поэтому сохраним данные текущей строки до следующей итерации
			if ($command == 'query') {
				$sql = trim($matches[5]);
				if (substr($sql, 0, 2) != '/*') {
					list($sqlType) = preg_split('/\s+/', $sql, 2);
					$this->_prevQuery = array(
						'conn_id' => $connId,
						'command' => $command,
						'date' => $this->_curLogDate->format('Y-m-d H:i:s'),
						'sql' => $sql,
						'sql_type' => strtoupper($sqlType),
					);
				}
			}
			// остальные запросы однострочны - сохраняем их сразу
			else {
				$this->_saveData(array(
					'conn_id' => $connId,
					'command' => $command,
					'date' => $this->_curLogDate->format('Y-m-d H:i:s'),
				));
			}

		} else {
			if ($this->_prevQuery) {
				$this->_prevQuery['sql'] .= ' '.trim($line);
			}
		}
	}

	protected function _createSession()
	{
		$db = db::get();
		$this->_sessId = $db->insert('sql_log_sessions', array(
			'comments' => '',
		));
	}

	public function _processLastLine()
	{
		if ($this->_prevQuery) {
			$this->_saveData($this->_prevQuery);
			$this->_prevQuery = array();
		}
	}

	protected function _saveData($data)
	{
		$db = db::get();

		if ($this->_numInserts % 100 == 0) {
			$db->commit();
			$db->beginTransaction();
		}

		if (!empty($data['sql'])) {
			$data['sql_reduced'] = $this->_getReducedSql($data['sql']);
		}
		$data['sess_id'] = $this->_sessId;
		db::get()->insert('sql_log', $data);
		$this->_numInserts++;
	}

	protected function _getReducedSql($sql)
	{
		$sql = preg_replace('/\bNULL\b/i', '?', $sql);
		$sql = preg_replace('/\d+(\.\d+)?/i', '?', $sql);

		$quote = "(?<!\\\\)'";
		$sql = preg_replace("/$quote.*?$quote/", '?', $sql);

		return $sql;
	}

}