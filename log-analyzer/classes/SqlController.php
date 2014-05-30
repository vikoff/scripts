<?php

class SqlController extends Controller
{
	public function display_index()
	{
		$db = db::get();

		$sessions = $db->fetchAll("SELECT * FROM sql_log_sessions ORDER BY id");

		$vars = array(
			'sessions' => $sessions,
		);

		Layout::get()
			->setContentPhpFile('sql.php', $vars)
			->render();
	}

	public function display_view($sid = null, $page = null)
	{
		$sid = (int)$sid;
		$db = db::get();
		$session = $db->fetchRow("SELECT * FROM sql_log_sessions WHERE id=?", $sid);
		if (!$session) {
			$this->display_404('session not found');
			return;
		}

		$page = in_array($page, array('main', 'group', 'can-group')) ? $page : 'main';
		$data = array();
		if ($page == 'main') {

		} elseif ($page == 'main') {
		} elseif ($page == 'group') {

			$data = $db->fetchAll("
				SELECT `sql`, COUNT(1) cnt, MIN(`date`) min_date, MAX(`date`) max_date
				FROM sql_log WHERE sess_id=? AND `sql` IS NOT NULL GROUP BY `sql` ORDER BY cnt DESC LIMIT 500
			", $sid);

		} elseif ($page == 'can-group') {

			$data = $db->fetchAll("
				SELECT `sql_reduced` `sql`, COUNT(1) cnt, MIN(`date`) min_date, MAX(`date`) max_date
				FROM sql_log WHERE sess_id=? AND `sql_reduced` IS NOT NULL GROUP BY `sql_reduced` ORDER BY cnt DESC LIMIT 500
			", $sid);

		}
		$vars = array(
			'session' => $session,
			'page' => $page,
			'data' => $data,
		);

		Layout::get()->setContentPhpFile('sql_view.php', $vars)->render();
	}

	public function action_sess_multiact()
	{
		$availActs = array('delete');
		$act = in_array(getVar($_POST['act']), $availActs) ? $_POST['act'] : null;
		if (!$act) {
			Messenger::get()->addError('invalid action');
			return;
		}
		if (!getVar($_POST['sess']) || !is_array($_POST['sess'])) {
			return;
		}

		$ids = array();
		foreach ($_POST['sess'] as $sid)
			if ($sid = (int)$sid)
				$ids[] = $sid;

		if (!$ids)
			return;

		if ($act == 'delete') {
			$affeted = db::get()->delete('sql_log_sessions', 'id IN('.implode(',', $ids).')');
			Messenger::get()->addSuccess("$affeted sessions was deleted");
		}
		reload();
	}
}