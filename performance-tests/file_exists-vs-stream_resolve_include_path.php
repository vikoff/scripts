<?php

require __DIR__.'/_init.php';

$perfMeter = new PerformanceMeter();

$perfMeter->start();
$iterations = 1000000;



for ($i = 0; $i < $iterations; $i++) {

	// stream_resolve_include_path
	// iterations: 1000000, time: 1.112 sec, memory diff: 0.00 kB
	$path = stream_resolve_include_path('/proc/cpuinfo');

	// file_exists
	// iterations: 1000000, time: 2.759 sec, memory diff: 0.00 kB
	// $path = file_exists('/proc/cpuinfo');
}

$perfMeter->end($iterations);
