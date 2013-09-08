<?php


class XTraceController extends Controller
{
	public function display_index()
	{
		$list = Xdebug_TraceStat::load()->getTracesList();
		$vars = array('list' => $list);
		Layout::get()
			->setContentPhpFile('xdebug-trace/list.php', $vars)
			->render();
	}

	public function display_view($sessId = null)
	{
		$sessId = (int)$sessId;

		$data = Xdebug_TraceStat::load()->getFirstLevelCalls($sessId);
		$vars = array(
			'calls' => $data['calls'],
			'basePath' => $data['sessData']['app_base_path'],
			'sessId' => $sessId,
			'sessData' => $data['sessData'],
		);
		Layout::get()
			->setContentPhpFile('xdebug-trace/view.php', $vars)
			->render();
	}

	public function display_remove($sessId)
	{
		$sessId = (int)$sessId;

		$data = Xdebug_TraceStat::load()->getSessData($sessId);
		$vars = array(
			'sessData' => $data,
		);
		Layout::get()
			->setContentPhpFile('xdebug-trace/remove.php', $vars)
			->render();
	}

	public function display_parse_new()
	{
		Layout::get()
			->setContentPhpFile('xdebug-trace/parse-new.php', $_POST)
			->render();
	}

	public function display_func_details($funcId = null)
	{
		$funcId = (int)$funcId;
		$funcData = Xdebug_TraceStat::load()->getFuncDetails($funcId);

		echo '<pre>'; print_r($funcData); echo '</pre>';
	}

	public function display_get_children()
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

	public function display_load_func_tree()
	{
		$funcId = getVar($_GET['id'], 0, 'int');
		$calls = Xdebug_TraceStat::load()->getFuncTree($funcId);
		Layout::get()->renderJson(array('success' => 1, 'data' => $calls));
	}

	public function action_parse_new()
	{
		if (empty($_FILES['file']['tmp_name'])) {
			Messenger::get()->addError('file not uploaded');
			return FALSE;
		}

		$dstFile = FS_ROOT.'tmp/'.$_FILES['file']['name'];
		$moved = move_uploaded_file($_FILES['file']['tmp_name'], $dstFile);

		if (!$moved) {
			Messenger::get()->addError('Unable to upload file');
			return FALSE;
		}
		chmod($dstFile, 0777);

		$options = array('remove_after' => true);
		$keys = array('application', 'request_url', 'app_base_path', 'comments');
		foreach ($keys as $key)
			if (isset($_POST[$key]))
				$options[$key] = $_POST[$key];

		$command = 'php '.FS_ROOT.'index.php x-trace/parse '
			.escapeshellarg($dstFile).' '.escapeshellarg(json_encode($options));

		if (!empty($_POST['generate_command'])) {
			Messenger::get()->addInfo("Command to process trace:<br><pre>$command</pre>");
			redirect('x-trace');
		} else {
			exec("$command > /dev/null 2>&1 &");
			Messenger::get()->addSuccess("Parse process started with command<br><pre>$command</pre>");
			redirect('x-trace');
		}
		return TRUE;
	}

	public function action_remove()
	{
		$id = getVar($_POST['id'], 0, 'int');
		$numAffected = db::get()->delete('xdebug_trace_sessions', 'id=?', $id);
		if ($numAffected) {
			Messenger::get()->addSuccess("Xdebug trace removed");
			return TRUE;
		} else {
			Messenger::get()->addError("Unable to remove Xdebug trace");
			return FALSE;
		}
	}


	public function cli_parse($file = null, $jsonOptions = null)
	{
		if ($jsonOptions) {
			$options = json_decode($jsonOptions, true);
			if (!$options) {
				throw new Exception('Options must be in json format');
			}
		} else {
			$options = array();
		}

		$parser = new Xdebug_TraceParser($options);
		$parser->parse($file);
	}

	public function cli_integrity_check()
	{
		Xdebug_TraceParser::integrityCheck();
	}

}