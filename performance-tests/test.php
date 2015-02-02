<?php

require __DIR__.'/_init.php';
$perfMeter = new PerformanceMeter();
#####################################################

function func1()
{
	$data = sys_getloadavg();
}

function func2()
{
	$date = date('Y-m-d H:i:s');
}

#####################################################

$iterations = 100000;
$perfMeter->start();

for ($i = 0; $i < $iterations; $i++) {
	func1();
	// func2();
}
$perfMeter->end($iterations);
