<?php

header('Content-type: text/plain; charset=utf-8');

$pdo = new PDO('mysql:dbname=urlshort;host=127.0.0.1', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

class DataFill
{
	public $pdo;
	public $table;
	public $columns;

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

	public function generate($numRows)
	{
		echo "start generating $numRows rows\n";

		$groupInserts = 20;
		$rows = array();
		
		for ($i = 0; $i < $numRows; $i++) {
			$rows[] = $this->_generateRow();
			if (count($rows) >= $groupInserts) {
				$this->_insert($rows);
				$rows = array();
			}
		}

		if ($rows)
			$this->_insert($rows);

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

		echo ".";
		$sql = "INSERT INTO $this->table (".implode(', ', $columns).") VALUES\n\t".implode(",\n\t", $rowSqls)."\n";
		// echo $sql; print_r($bind); die;
		
		$this->pdo->prepare($sql)->execute($bind);
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

$dataFill = new DataFill($pdo, 'links_share_stat', array(
	'link_id' => 250754,
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

$numRows = 20;
if (in_array(readline("Insert $numRows rows? [Y/n] "), array('y', 'Y', '', true)))
	$dataFill->generate($numRows);
