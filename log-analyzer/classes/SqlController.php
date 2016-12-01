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

	public function display_view($sid = null)
	{
		$session = $this->_findSession($sid);

		$vars = array(
			'session' => $session,
			'page' => 'main',
		);

		Layout::get()->setContentPhpFile('sql/overview.php', $vars)->render();
	}

	public function display_view_charts($sid = null)
	{
		$session = $this->_findSession($sid);
        $db = db::get();

        $step = $this->_calcChartStep($session);

        $summary = $db->fetchAll("
            SELECT FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(`date`) / $step ) * $step) AS `date`,
                COUNT(1) cnt_total,
            SUM(IF(sql_type='SELECT', 1, 0)) cnt_select,
            SUM(IF(sql_type='DELETE', 1, 0)) cnt_delete,
            SUM(IF(sql_type='INSERT', 1, 0)) cnt_insert,
            SUM(IF(sql_type='UPDATE', 1, 0)) cnt_update
            FROM sql_log
            WHERE sess_id=? AND command='query'
            GROUP BY FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(`date`) / $step ) * $step)
        ", $sid);

		Layout::get()->setContentPhpFile('sql/charts.php', [
            'session' => $session,
            'page' => 'charts',
            'summary' => $summary,
        ])->render();
	}


    public function display_view_group($sid)
    {
        $session = $this->_findSession($sid);
        $db = db::get();

        $data = $db->fetchAll("
            SELECT `sql`, COUNT(1) cnt, MIN(`date`) min_date, MAX(`date`) max_date
            FROM sql_log WHERE sess_id=? AND `sql` IS NOT NULL GROUP BY `sql` ORDER BY cnt DESC LIMIT 500
        ", $sid);

        $vars = array(
            'session' => $session,
            'data' => $data,
            'page' => 'group',
        );

        Layout::get()->setContentPhpFile('sql/view-group.php', $vars)->render();
	}

    public function display_view_can_group($sid)
    {
        $session = $this->_findSession($sid);
        $db = db::get();

        $data = $db->fetchAll("
            SELECT `sql_reduced` `sql`, COUNT(1) cnt, MIN(`date`) min_date, MAX(`date`) max_date
            FROM sql_log WHERE sess_id=? AND `sql_reduced` IS NOT NULL GROUP BY `sql_reduced` ORDER BY cnt DESC LIMIT 500
        ", $sid);

        $vars = array(
            'session' => $session,
            'data' => $data,
            'page' => 'can-group',
        );

        Layout::get()->setContentPhpFile('sql/view-group.php', $vars)->render();
	}

    private function _findSession($id = null)
    {
        $session = db::get()->fetchRow("SELECT * FROM sql_log_sessions WHERE id=?", $id);
        if (!$session)
            throw new Exception('Session not found');

        return $session;
	}

    private function _calcChartStep($session)
    {
        return 60;
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

    public function action_remove_all()
    {
        if (empty($_POST['remove-all']))
            return;

        $db = db::get();

        $db->truncate('sql_log');
        $db->delete('sql_log_sessions', '1');

        Messenger::get()->addSuccess("All data removed!");
    }
}