<?php

require '/var/www/urlshortener/library/Zend/Config.php';
$configArr = require '/var/www/urlshortener/application/configs/compiled/application/novikov.php';

$configZend = new Zend_Config($configArr);
$configPhalcon = new \Phalcon\Config($configArr);


require __DIR__.'/_init.php';
$perfMeter = new PerformanceMeter();
$perfMeter->start();
#####################################################

// LONG KEY:  iterations: 100000 | time: 1.317 sec | cpu-time: 1.318 sec | memory diff: 0.00 kB
// SHORT KEY: iterations: 100000 | time: 0.447 sec | cpu-time: 0.447 sec | memory diff: 0.00 kB
function func1()
{
	global $configZend;
	// $data = $configZend->resources->frontController->controllerDirectory->default;
	$data = $configZend->compress_html;
}

// LONG KEY:  iterations: 100000 | time: 0.113 sec | cpu-time: 0.113 sec | memory diff: 0.00 kB
// SHORT KEY: iterations: 100000 | time: 0.102 sec | cpu-time: 0.103 sec | memory diff: 0.00 kB
function func2()
{
	global $configPhalcon;
	// $data = $configPhalcon->resources->frontController->controllerDirectory->default;
	$data = $configPhalcon->compress_html;
}

// LONG KEY:  iterations: 100000 | time: 0.114 sec | cpu-time: 0.114 sec | memory diff: 0.00 kB
// SHORT KEY: iterations: 100000 | time: 0.097 sec | cpu-time: 0.097 sec | memory diff: 0.00 kB
function func3()
{
	global $configArr;
	// $data = $configArr['resources']['frontController']['controllerDirectory']['default'];
	$data = $configArr['compress_html'];
}

$iterations = 100000;
for ($_i = 0; $_i < $iterations; $_i++) {
	// func1();
	// func2();
	func3();
}


#####################################################
$perfMeter->end($iterations);
