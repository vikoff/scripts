<?php
/**
 * User: yuriy
 * Date: 19.08.14 14:40
 */

class HihitController extends Controller
{
	public function display_index()
	{
		$jsonData = file_get_contents(FS_ROOT.'data/hihit2014-08-19_15-22-24.json');
		Layout::get()
			->setContentPhpFile('hihit.php', array('jsonData' => $jsonData))
			->render();
	}

	public function cli_parse()
	{
		global $argv;
		if (empty($argv[2]))
			exit("data file not specified\n");

		$file = $argv[2];
		try {
			if (!file_exists($file))
				throw new Exception("file '$file' is not exists");

			$content = file_get_contents($file);
			if ($content && preg_match('@<data>(.+)</data>@s', $content, $matches)) {
				$all = array();
				foreach (explode("\n", $matches[1]) as $row) {
					if (trim($row)) {
						$rowArr = explode(';', $row);
						$all[] = $rowArr;
					}
				}
				$name = FS_ROOT.'data/hihit'.date('Y-m-d_H-i-s').'.json';
				$all = array_reverse($all);
				file_put_contents($name, json_encode($all));
				echo "\nparse hihit data to file $name\n";
			} else {
				throw new Exception('invalid file data format');
			}
		} catch (Exception $e) {
			echo "ERROR: ".$e->getMessage()."\n";
		}
	}
}
 