<?php

require __DIR__.'/_init.php';
$perfMeter = new PerformanceMeter();
$perfMeter->start();
#####################################################

$pdo = new PDO('mysql:host=localhost;dbname=urlshort', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// iterations: 100000 | time: 9.022 sec | cpu-time: 2.959 sec | memory diff: 0.00 kB
// iterations: 1000 | time: 0.091 sec | cpu-time: 0.031 sec | memory diff: 0.00 kB
function func1()
{
	global $pdo;

	$result = $pdo->query("SELECT * FROM releases ORDER BY id desc limit 1")->fetchAll(PDO::FETCH_COLUMN);
	// var_dump($result); die;
}

// iterations: 1000 | time: 3.559 sec | cpu-time: 0.751 sec | memory diff: 78.83 kB
function func2()
{
	$result = `cd /var/www/urlshortener; git rev-parse HEAD`;
	// var_dump($result); die;
}

// iterations: 1000 | time: 2.757 sec | cpu-time: 0.728 sec | memory diff: 78.85 kB
function func3()
{
	$result = `cat /var/www/urlshortener/.git/refs/heads/master`;
	// var_dump($result); die;
}

$iterations = 1000;
for ($_i = 0; $_i < $iterations; $_i++) {
	// func1();
	// func2();
	func3();
}


#####################################################
$perfMeter->end($iterations);
