<?php

require __DIR__.'/_init.php';

$perfMeter = new PerformanceMeter();

$perfMeter->start();
$iterations = 10;


// ARRAY
// $arr = range(0, 500000);

// SPL-ARRAY
$arr = new SplFixedArray(500000);

for ($i = 0; $i < $iterations; $i++) {

	foreach ($arr as $k => $v) {
		$v = 1;
	}
}

$perfMeter->end($iterations);
