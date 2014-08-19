<?php
/**
 * User: yuriy
 * Date: 19.08.14 14:40
 */

class HihitController extends Controller
{
	public function display_index()
	{
		$filesRaw = glob(FS_ROOT.'data/hihit/*');
		$files = array();

		foreach ($filesRaw as $file) {
			$data = pathinfo($file);
			if ($data['extension'] == 'json')
				$files[] = $data['filename'];
		}
		Layout::get()
			->setContentPhpFile('hihit/index.php', array('files' => $files))
			->render();
	}

	public function display_view()
	{
		$file = getVar($_GET['file']);
		if (!$file || !preg_match('@^[\d-_]+$@', $file))
			throw new Exception('file not found');

		$jsonData = file_get_contents(FS_ROOT.'data/hihit/'.$file.'.json');
		Layout::get()
			->setContentPhpFile('hihit/view.php', array('jsonData' => $jsonData))
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
				$name = FS_ROOT.'data/hihit/'.date('Y-m-d_H-i-s').'.json';
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
 