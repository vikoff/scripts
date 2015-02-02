<?php

class SqlController extends Controller {

	/////////////////////
	////// DISPLAY //////
	/////////////////////
	
	public function display_index(){

		$curConn = getVar($_GET['conn'], 'default');
		try { $db = db::get($curConn); }
		catch (Exception $e) {
			$db = db::get();
		}

		$vars = array(
			'curConn' => $curConn,
			'db' => $db,
		);
		Layout::get()
			->setContentPhpFile('index.php', $vars)
			->render();
	}

	public function display_sql_console(){

		$query = getVar($_POST['query']);
		$title = getVar($_POST['title']);
		$explain = !empty($_GET['explain']);
		$curConn = getVar($_GET['conn']);
		$database = getVar($_GET['db']);
		$mode = getVar($_GET['mode'], 'grid'); // grid|explain|graph
		$limit = getVar($_GET['limit'], 100, 'int');

		try {
			$db = db::get($curConn);
		} catch (Exception $e) {
			$db = db::get();
		}

		if ($database)
			$db->selectDb($database);

		$result = $query ? $this->execSql($db, $query, $mode == 'explain', $limit) : array();

		$conns = array();
		foreach (db::getAllConnections() as $label => $conn)
			$conns[$label] = $label." - ".$conn->getConnHost();

		$vars = array(
			'query' => $query,
			'data' => $result,
			'title' => $title,
			'curDb' => $database,
			'dbs' => $db->showDatabases(),
			'curConn' => $curConn,
			'conns' => $conns,
			'mode' => $mode,
			'limit' => $limit,
		);

		Layout::get()
			->prependTitle($title)
			->setContentPhpFile('sql_console.php', $vars)
			->render();
	}

	public function display_get_databases()
	{
		$curConn = getVar($_GET['conn']);
		try {
			$db = db::get($curConn);
		} catch (Exception $e) {
			$db = db::get();
		}

		header('Content-type: application/json; charset=utf-8');
		echo json_encode($db->showDatabases());
	}

	public function display_history(){

		if (!isset($_SESSION['sql-history']))
			$_SESSION['sql-history'] = array();

		$vars = array(
			'history' => $_SESSION['sql-history'],
		);

		Layout::get()
			->prependTitle('history')
			->setContentPhpFile('history.php', $vars)
			->render();
	}
	
	public function display_404($method = ''){
		
		if(AJAX_MODE){
			echo 'Страница не найдена ('.$method.')';
		}else{
			Layout::get()
				->setContent('<h1 style="text-align: center;">Страница не найдена</h1> ('.$method.')')
				->render();
		}
		exit;
	}
	
	
	////////////////////
	////// ACTION //////
	////////////////////
	
	public function cli_fill_tables(){

		$db = db::get();
		$start = microtime(1);

		$str1 = "Hi all! How are you? The Public Folder lets you easily share single files in your Dropbox. Any file you put in this folder gets its own Internet link so that you can share it with others-- even non-Dropbox users!  These links work even if your computer’s turned off.";
		$str1Len = strlen($str1);

		for ($i = 0; $i < 100000; $i++) {
			$time = time();
			$md5 = md5($i . $time);
			$arr = explode(' ', $str1);
			shuffle($arr);
			
			$data = array(
				'f1' => substr($md5, 0, 4),
				'f2' => $md5,
				'f3' => substr($str1, $i % $str1Len, rand(10, 15)),
				'f4' => implode(' ', $arr),
				'i1' => $i,
				'i2' => $i % 23,
				'i3' => $time,
				'i4' => round(sin($i), 3),
			);

			$db->insert('index_test', $data);

			if ($i % 100 == 0) { echo "."; }
			if ($i && $i % 9999 == 0) { echo "\n"; }


		}


		printf("\nduration: %.3f sec\n", microtime(1) - $start);
	}
	
	
	////////////////////
	////// ACTION //////
	////////////////////
	
	
	////////////////////
	//////  AJAX  //////
	////////////////////

	public function ajax_add_sql_to_favorites() {

		$query = trim(getVar($_POST['val']));
		
	}
	

	////////////////////
	//////  MODEL  /////
	////////////////////
	
	// EXEC SQL (FORM SQL CONSOLE)
	public function execSql(DbAdapter $db, $inputSql, $execExplain = FALSE, $limit = 100)
	{
		$inputSql = trim($inputSql);

		if (!$inputSql)
			return array();

		$clearSql = $this->_getCleanSql($inputSql);

		if (!$clearSql)
			return array();

		$result = $this->_execOneSql($db, $clearSql, $limit);
		if ($result['success'])
			$this->_saveSqlHistory($inputSql, $result['numrows'], $result['time']);
		$resultExplain = $execExplain && empty($result['error']) && strtoupper(substr($clearSql, 0, 6)) == 'SELECT'
			? $this->_execOneSql($db, 'EXPLAIN '.$clearSql, $limit)
			: null;

		$results = array();
		if ($resultExplain)
			$results[] = $resultExplain;
		$results[] = $result;

		return $results;
	}

	private function _execOneSql(DbAdapter $db, $sql, $limit = 100)
	{
		// $sql = str_replace(array('\r', '\n'), array("\r", "\n"), $sql);
		try {
			/** @var PDOStatement $rs */
			$rs = $db->query($sql);
			$numRows = $rs->rowCount();
			$result = array();
			for ($i = 0, $len = min($numRows, $limit); $i < $len; $i++)
				$result[] = $rs->fetch(PDO::FETCH_ASSOC);
			return array_merge($db->getLastQueryInfo(), array('result' => $result, 'numrows' => $numRows, 'success' => 1));
		} catch (Exception $e) {
			return array('error' => $e->getMessage(), 'success' => 0);
		}
	}

	private function _getCleanSql($sql)
	{
		$sql = str_replace("\r\n", "\n", $sql);
		$rows = explode("\n", $sql);
		foreach ($rows as $i => $row) {
			if (substr($row, 0, 3) === '-- ')
				unset($rows[$i]);
		}

		$sql = trim(implode("\n", array_values($rows)));
		return $sql;
	}

	private function _saveSqlHistory($sql, $numRows, $duration) {

		if (!isset($_SESSION['sql-history']))
			$_SESSION['sql-history'] = array();

		$hash = md5(preg_replace('/\s/', '', $sql));

		$newItem = null;

		foreach ($_SESSION['sql-history'] as $i => $item) {
			if ($item['hash'] == $hash) {
				$newItem = $item;
				array_splice($_SESSION['sql-history'], $i, 1);
				$newItem['time'] = time();
				$newItem['execCnt']++;
				$newItem['numRows'] = $numRows;
				$newItem['duration'] = $duration;
				break;
			}
		}

		if (!$newItem) {

			$newItem = array(
				'hash' => $hash,
				'time' => time(),
				'execCnt' => 1,
				'sql' => $sql,
				'numRows' => $numRows,
				'duration' => $duration,
			);
		}

		$_SESSION['sql-history'][] = $newItem;
	}

}

?>