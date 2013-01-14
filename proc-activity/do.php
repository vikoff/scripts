<?php

header('Content-type: text/plain; charset=utf-8');

$config = array(
	'application' => 'ScreenshotServer',
	'interval' => 1,
	'work_delta' => 5,
);

$application = $config['application'];
$pid = trim(`pidof $application`);
if (!$pid) exit ('process not found');

// echo "pid of $application: $pid\n";

function getProcessInfo($pid, $procTimeOnly = false) {

	$data = file_get_contents('/proc/'.$pid.'/stat');
	$procValues = preg_split('/\s+/', trim($data));
	$keys = array(
		0 => 'pid',
		1 => 'comm',
		2 => 'state',
		3 => 'ppid',
		4 => 'pgrp',
		5 => 'session',
		6 => 'tty_nr',
		7 => 'tpgid',
		8 => 'flags',
		9 => 'minflt',
		10 => 'cminflt',
		11 => 'majflt',
		12 => 'cmajflt',
		13 => 'utime', // Amount of time that this process has been scheduled in user mode, measured in clock ticks
		14 => 'stime', // Amount of time that this process has been scheduled in kernel mode, measured in clock ticks
	);

	$output = array();
	foreach ($keys as $index => $k)
		$output[$k] = $procValues[$index];

	return $procTimeOnly
		? $output['utime'] + $output['stime']
		: $output;
}

function getCpuInfo() {

	$output = array();
	$data = file_get_contents('/proc/stat');

	$output['cpu_num'] = count(preg_grep('/^processor\s+:/', file('/proc/cpuinfo')));

	$output['cpu_total_time'] = preg_match('/^cpu\s+(.+)$/m', $data, $matches)
		? array_sum(preg_split('/\s+/', trim($matches[1])))
		: 0;

	$output['cpus_time'] = array();
	for ($i = 0; $i < $output['cpu_num']; $i++) {
		$output['cpus_time'][$i] = preg_match('/^cpu'.$i.'\s+(.+)$/m', $data, $matches)
			? array_sum(preg_split('/\s+/', trim($matches[1])))
			: 0;
	}

	return $output;
}

function secondsDiff($s1, $s2) {
	$sub = abs($s2 - $s1);
	return min($sub, abs($sub - 60));
}

function secondsLeft($workSeconds, $curSeconds) {
	return $workSeconds < $curSeconds
		? $workSeconds + 60 - $curSeconds
		: $workSeconds - $curSeconds;
}

function printLn($str) {
	printf("\r  %-80s\r", $str);
}

function _test() {
	$cpu1 = getCpuInfo();
	$procTime1 = getProcessInfo($pid, true);

	sleep($config['interval']);

	$cpu2 = getCpuInfo();
	$procTime2 = getProcessInfo($pid, true);

	echo "interval: {$config['interval']} sec\n";
	echo "proc delta: ".($procTime2 - $procTime1)."\n";
	echo "total cpu delta: ".($cpu2['cpu_total_time'] - $cpu1['cpu_total_time'])."\n";
	foreach ($cpu2['cpus_time'] as $index => $time)
		echo "cpu $index delta: ".($time - $cpu1['cpus_time'][$index])."\n";
}

$workSeconds = null;
$secondsLeft = null;
$isWorkCaught = null;

if (PHP_SAPI == 'cli') {
	
	while (1) {
		$curSeconds = (int)ltrim(date('s'), '0');
		$secondsLeft = $workSeconds !== null ? secondsLeft($workSeconds, $curSeconds) : null;
		$checkCpuTime = $workSeconds === null || $secondsLeft <= 5 || $secondsLeft >= 55;
		$startWorkingCheck = $secondsLeft === 5;
		$endWorkingCheck = $secondsLeft === 55;

		if ($startWorkingCheck)
			$isWorkCaught = false;

		if ($workSeconds !== null) {
			printLn("$secondsLeft (cur: $curSeconds, work: $workSeconds)");
		}

		if ($checkCpuTime)
			$procTime1 = getProcessInfo($pid, true);

		sleep($config['interval']);

		if ($checkCpuTime) {
			$procTime2 = getProcessInfo($pid, true);
			$delta = $procTime2 - $procTime1;
			if ($workSeconds === null) {
				printLn("checking".str_repeat('.', ($curSeconds % 3) + 1));
			}
			if ($delta > $config['work_delta']) {
				$workSeconds = $curSeconds; // DEBUG
				$isWorkCaught = TRUE;
			}
		}

		if ($endWorkingCheck) {
			if (!$isWorkCaught) {
				$workSeconds = null;
				echo "process did not work at the expected time. Restart Catching";
			}
			$isWorkCaught = FALSE;
		}
	}

}


