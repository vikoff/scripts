<?php

$startTime = microtime(1);
$startMem = memory_get_usage();
#####################################################

$arr = range(1, 50000);
// $search = range(4000, 6000);
$search = array(2, 50, 1000, 9999, 4999);

// iterations: 10000, time: 1.508 sec, memory diff: 1068.38 kB
function func1($arr, $search)
{
	$matches = array();
	foreach ($search as $i) {
		$matches[$i] = in_array($i, $arr);
	}
}

// iterations: 10000, time: 6.258 sec, memory diff: 1068.38 kB
function func2($arr, $search)
{
	$matches = array();
	$invArr = array_flip($arr);
	foreach ($search as $i) {
		$matches[$i] = isset($arr[$i]);
	}
}

$iterations = 1000;
for ($_i = 0; $_i < $iterations; $_i++) {
	func1($arr, $search);
	// func2($arr, $search);
}


#####################################################
$endTime = microtime(1);
$endMem = memory_get_peak_usage();

if (PHP_SAPI != 'cli')
	echo '<pre>';

printf("iterations: %d, time: %.3f sec, memory diff: %.2f kB\n", $iterations, $endTime - $startTime, ($endMem - $startMem) / 1000);
?>