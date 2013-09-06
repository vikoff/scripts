<?php
/**
 * User: yuriy
 * Date: 14.08.13 13:36
 */

class SqlLogStat
{
	public function __construct()
	{

	}

	public function getStat()
	{
		$db = db::get();

		$queryTypes = $db->fetchPairs("SELECT sql_type, COUNT(1) cnt FROM sql_log WHERE sql_type IS NOT NULL
			GROUP BY sql_type ORDER BY CNT desc;");



		$stat = array(
			'dates' => $db->fetchRow("SELECT MIN(date) min_date, MAX(date) max_date FROM sql_log"),
			'queryTypes' => $queryTypes,
			'selectsStat' => $this->_getSelectsStat(),
			'selectsByTables' => $this->_getSelectsByTables(),
		);

		return $stat;
	}

	protected function _getSelectsStat()
	{
		$db = db::get();
		$step = 60; // 60 секунд в интервале

		$selectsStat = $db->fetchPairs("SELECT (FLOOR(UNIX_TIMESTAMP(date) / $step)) step, COUNT(1) cnt
			FROM sql_log
			WHERE sql_type='SELECT'
			GROUP BY step
			ORDER BY step
		");

		$allSteps = array_keys($selectsStat);
		$firstStep = $allSteps[0];
		$lastStep = $allSteps[ count($allSteps) - 1 ];

		$output = array(
			'dates' => array(),
			'values' => array(),
		);
		for ($i = $firstStep; $i <= $lastStep; $i++) {
			$output['dates'][] = date('Y-m-d H:i:s', $i * $step);
			$output['values'][] = isset($selectsStat[$i]) ? (int)$selectsStat[$i] : 0;
		}

		return $output;
	}

	protected function _getSelectsByTables()
	{
		$tablesStr = '_hihit
_profiling
backup_links
blacklist
clicks
feedback
filtered_urls
graylist_content
link_extra_data
link_processing
links
links_promo
links_share_stat
migration
page_visits_log
poll_answer_users
poll_answers
polls
user_sessions
user_themes_log
users
users_fb
users_tw';

		$db = db::get();
		$tables = explode("\n", $tablesStr);

		$tablesStat = array();
		foreach ($tables as $table) {
			$tablesStat[$table] = $db->fetchOne("
				SELECT COUNT(1) FROM sql_log
				WHERE date BETWEEN '2013-08-14 00:19:00' AND '2013-08-14 00:20:00'
					AND `sql` IS NOT NULL
					AND sql_type='SELECT'
					AND `sql` REGEXP '(FROM|from) `?$table(`| )'");
		}

		$tablesStat2 = array();
		foreach ($tables as $table) {
			$tablesStat2[$table] = $db->fetchOne("
				SELECT COUNT(1) FROM sql_log
				WHERE date BETWEEN '2013-08-14 00:20:00' AND '2013-08-14 00:21:00'
					AND `sql` IS NOT NULL
					AND sql_type='SELECT'
					AND `sql` REGEXP '(FROM|from) `?$table(`| )'");
		}

		arsort($tablesStat);
		arsort($tablesStat2);
		echo '<pre>'; print_r($tablesStat);
		echo "sum-19: ".array_sum(array_values($tablesStat)).PHP_EOL;
		echo '<pre>'; print_r($tablesStat2);
		echo "sum-20: ".array_sum(array_values($tablesStat2));
		die; // DEBUG
	}
}