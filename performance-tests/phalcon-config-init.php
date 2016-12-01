<?php

require '/var/www/urlshortener/library/Zend/Config.php';
$configArr = require '/var/www/urlshortener/application/configs/compiled/application/novikov.php';

require __DIR__.'/_init.php';
$perfMeter = new PerformanceMeter();
$perfMeter->start();
#####################################################

// iterations: 5000 | time: 2.666 sec | cpu-time: 2.669 sec | memory diff: 65.33 kB
function func1()
{
	global $configArr;
	$config = new Zend_Config($configArr);
}

// iterations: 5000 | time: 3.556 sec | cpu-time: 3.557 sec | memory diff: 33.63 kB
function func2()
{
	global $configArr;
	$config = new \Phalcon\Config($configArr);
}

$iterations = 5000;
for ($_i = 0; $_i < $iterations; $_i++) {
	// func1();
	func2();
}


#####################################################
$perfMeter->end($iterations);
