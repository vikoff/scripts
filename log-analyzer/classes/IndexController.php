<?php

class IndexController extends Controller
{

	public function display_index()
	{
		Layout::get()
			->setContentPhpFile('index.php')
			->render();
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