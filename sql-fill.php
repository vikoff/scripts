<?php

header('Content-type: text/plain; charset=utf-8');

// $pdo = new PDO('mysql:dbname=urlshort;host=127.0.0.1', 'root', '');
$pdo = new PDO('pgsql:host=localhost;port=5432;dbname=urlshort_stat;user=yuriy;password=0000');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

class DataFill
{
	public $pdo;
	public $table;
	public $columns;

	public $rowsInGroupInsert = 20;
	public $insertsInTransaction = 30;
	
	public $curRow = array();

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
		$this->curRow = array();
		foreach ($this->columns as $name => $val) {
			$this->curRow[ $name ] = is_callable($val) ? $val($this) : $val;
		}

		return $this->curRow;
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
			$this->pdo->exec('COMMIT');
			$this->pdo->exec('BEGIN');
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

function fillClicks($numRows = 20)
{
	global $pdo;
	$table = 'clicks';

	$dataFill = new DataFill($pdo, $table, array(
		'link_id' => function($self) {
			$ids = array('250802', '250814', '250829', '250836', '250837', '250838', '250839', '250840', '250841', '250843', '250844', '250845', '250846', '250847', '250848', '250849', '250852', '250860', '250866', '250867', '250868', '250869', '250870', '250871', '250872', '250873', '250874', '250876', '250877', '250879', '250880', '250881', '250882', '250883', '250884', '250885', '250886', '250887', '250888', '250892', '250893');

			return $ids[ array_rand($ids) ];
		},
		'browser' => 'generator2',
		'created_at' => function($self) {
			return $self->randomDateFromInterval('2013-09-10 17:12:43', '2013-10-08 00:00:00');
		},
		'create_date' => function($self) {
			$parts = explode(' ', $self->curRow['created_at']);
			return $parts[0];
		},
		'user_ip' => '127.0.0.1',
	));

	$dataFill->rowsInGroupInsert = 50;
	$dataFill->insertsInTransaction = 100;
	if (in_array(readline("Insert $numRows rows into $table table? [Y/n] "), array('y', 'Y', '', true)))
		$dataFill->generate($numRows);
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
		},
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

function fillPgVisits($numRows = 20)
{
	global $pdo;
	$table = 'visits_server';
	
	$dataFill = new DataFill($pdo, $table, array(
		'link_id' => function($self) {
			return rand(250000, 500000);
		},
		'user_id' => function($self) {
			return rand(1, 10000);
		},
		'user_ip' => '127.0.0.1',

		'user_agent' => function($self) {
			$agents = array(
				'Mozilla/5.0 (compatible; TweetmemeBot/3.0; +http://tweetmeme.com/)',
				'Opera/9.80 (J2ME/MIDP; Opera Mini/4.2.23449/34.1088; U; ar) Presto/2.8.119 Version/11.10',
				'AddThis.com robot tech.support@clearspring.com');
			return $agents[ array_rand($agents) ];
		},
		'created_at' => function($self) {
			return $self->randomDateFromInterval('2014-01-01', '2014-01-31');
		},
		'create_date' => function($self) {
			$parts = explode(' ', $self->curRow['created_at']);
			return $parts[0];
		},
	));

	if (in_array(readline("Insert $numRows rows into $table table? [Y/n] "), array('y', 'Y', '', true)))
		$dataFill->generate($numRows);
}

fillPgVisits(50000000);

