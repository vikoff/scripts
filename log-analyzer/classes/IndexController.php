<?php

class IndexController extends Controller
{

	public function display_index()
	{
		Layout::get()
			->setContentPhpFile('index.php')
			->render();
	}

	public function display_sql()
	{
		$statClass = new SqlLogStat();
//		$stat = array();
		$stat = $statClass->getStat();

//		echo '<pre>'; var_dump($stat); die; // DEBUG

		Layout::get()
			->setContentPhpFile('sql.php', $stat)
			->render();
	}

	public function ajax_xdebug_trace_get_children()
	{
		$sessId = getVar($_GET['sess'], 0, 'int');
		$id = getVar($_GET['id'], 0, 'int');
		if (!$sessId || !$id) {
			Layout::get()->renderJson(array('success' => 0, 'error' => 'Invalid input data'));
			return;
		}

		$calls = Xdebug_TraceStat::load()->getFuncChildren($sessId, $id);
		Layout::get()->renderJson(array('success' => 1, 'data' => $calls));
	}

	public function display_xdebug_trace_func_details($funcId = null)
	{
		$funcId = (int)$funcId;
		$funcData = Xdebug_TraceStat::load()->getFuncDetails($funcId);

		echo '<pre>'; print_r($funcData); echo '</pre>';
	}

	public function cli_index()
	{
		$appCall = "php index.php";
		echo "AVAILABLE COMMANDS\n"
			."$appCall parse-sql 'path/to/sql-log-file'\n"
			."$appCall x-trace/parse 'path/to/trace-file' ['json-options']\n"
			."    'json-options' may contain such keys: 'application', 'request_url', 'app_base_path', 'comments'\n"
			."    EXAMPLE: php index.php x-trace/parse trace.xt '{\"application\":\"homework\","
			." \"request_url\":\"/teacher/home\", \"app_base_path\":\"/var/www/homework/\", \"comments\":\"test run\"}'\n"
		;
	}

	public function cli_parse_sql()
	{
		global $argv;
		if (empty($argv[2]))
			exit("sql log file not specified\n");

		try {
			$parser = new ParserSqlLog($argv[2]);
			$parser->parse();
		} catch (Exception $e) {
			echo "ERROR: ".$e->getMessage()."\n";
		}
	}

}