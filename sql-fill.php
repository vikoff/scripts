<?php

header('Content-type: text/plain; charset=utf-8');

$pdo = new PDO('mysql:dbname=urlshort;host=127.0.0.1', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

class DataFill
{
	public $pdo;
	public $table;
	public $columns;

	public $rowsInGroupInsert = 20;
	public $insertsInTransaction = 30;

	protected $_numInserts = 0;

	public function __construct(PDO $pdo, $table, $columns)
	{
		$this->pdo = $pdo;
		$this->table = $table;
		$this->columns = $columns;
	}

	public function randomDateFromInterval($startDate, $endDate = 'now', $format = 'Y-m-d H:i:s')
	{
		$startTimestamp = $this->_getDateTime($startDate)->getTimestamp();
		$endTimestamp = $this->_getDateTime($endDate)->getTimestamp();

		$rand = mt_rand($startTimestamp, $endTimestamp);

		$output = date($format, $rand);
		return $output;
	}

	public function randomString($length = 10)
	{
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, strlen($characters) - 1)];
	    }
	    return $randomString;
	}

	public function generate($numRows)
	{
		echo "start generating $numRows rows\n";

		$rows = array();
		$this->pdo->exec('BEGIN');
		
		for ($i = 0; $i < $numRows; $i++) {
			$rows[] = $this->_generateRow();
			if (count($rows) >= $this->rowsInGroupInsert) {
				$this->_insert($rows);
				$rows = array();
			}
		}

		if ($rows)
			$this->_insert($rows);

		$this->pdo->exec('COMMIT');
		echo "\ngeneration complete\n";
	}

	protected function _generateRow()
	{
		$row = array();
		foreach ($this->columns as $name => $val) {
			$row[ $name ] = is_callable($val) ? $val($this) : $val;
		}

		return $row;
	}

	protected function _insert($rows)
	{
		if (!$rows)
			return;

		$columns = array_keys($rows[0]);
		$rowSqls = array();
		$bind = array();

		foreach ($rows as $row) {
			$placeholders = array();
			foreach ($row as $name => $value) {
				$placeholders[] = '?';
				$bind[] = $value;
			}
			$rowSqls[] = '('.implode(', ', $placeholders).')';
		}

		$sql = "INSERT INTO $this->table (".implode(', ', $columns).") VALUES\n\t".implode(",\n\t", $rowSqls)."\n";
		// echo $sql; print_r($bind); die;
		
		$this->pdo->prepare($sql)->execute($bind);
		$this->_numInserts++;

		if ($this->_numInserts % $this->insertsInTransaction == 0) {
			// $this->pdo->exec('COMMIT');
			// $this->pdo->exec('BEGIN');
		}

		echo ".";
		if ($this->_numInserts % 100 == 0)
			echo " ".($this->_numInserts * $this->rowsInGroupInsert)."\n";
	}

	protected function _getDateTime($date)
	{
		$dateTimeObj = null;
		if (is_int($date)) {
			$dateTimeObj = new DateTime('@'.$date);
		} elseif (is_string($date)) {
			$dateTimeObj = new DateTime($date);
		} elseif ($date instanceof DateTime) {
			$dateTimeObj = $date;
		} else {
			throw new Exception('Invalid date type');
		}

		return $dateTimeObj;
	}
}

function fillLinksShareStat($numRows = 20)
{
	global $pdo;
	$table = 'links_share_stat';

	$dataFill = new DataFill($pdo, $table, array(
		'link_id' => 250759,
		'user_id' => function($self) {
			static $ids = array();
			if (!$ids) {
				$ids = $self->pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
			}
			return $ids[ array_rand($ids) ];
		},
		'shared_to' => function($self) {
			$shared = array('twitter', 'facebook', 'google_plusone_share', 'stumbleupon', 'linkedin', 'other');
			return $shared[ array_rand($shared) ];
		},
		'created_at' => function($self) {
			return $self->randomDateFromInterval('2013-07-10 17:12:43', '2013-07-11 00:00:00');
		}
	));

	if (in_array(readline("Insert $numRows rows into $table table? [Y/n] "), array('y', 'Y', '', true)))
		$dataFill->generate($numRows);
}

function fillUserSessions($numRows = 20)
{
	global $pdo;
	$table = 'user_sessions';
	
	$dataFill = new DataFill($pdo, $table, array(
		'sess_key' => function($self) {
			return $self->randomString(32);
		},
		'data' => function($self) {
			return $self->randomString(rand(100, 1000));
		},
		'expire_at' => function($self) {
			return $self->randomDateFromInterval('2013-07-10 17:12:43', '2013-07-15 00:00:00');
		},
	));

	if (in_array(readline("Insert $numRows rows into $table table? [Y/n] "), array('y', 'Y', '', true)))
		$dataFill->generate($numRows);
}

fillUserSessions(50000);

