<?php

require __DIR__.'/_init.php';
$perfMeter = new PerformanceMeter();
$perfMeter->start();
#####################################################

$pdo = new PDO('mysql:host=localhost;dbname=test', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// iterations: 1000, time: 4.410 sec, memory diff: 0.00 kB
function func1()
{
	global $pdo;

	$pdo->exec("INSERT INTO ln_events1 (theme, version, category, action, label, user_type, locale, country, cnt, grouped, device, data, followers, force_auth, created_at) VALUES (null, null, null, 123, null, 'user', 'en', null, 1, 0, 'desktop', null, null, 'no', NOW())");
}

// 
function func2()
{
	global $pdo;
	$pdo->exec("UPDATE ln_events2 SET cnt=cnt+1 WHERE id=1355");
}

$iterations = 1000;
for ($_i = 0; $_i < $iterations; $_i++) {
	// func1();
	func2();
}


#####################################################
$perfMeter->end($iterations);

Memcache->set('a=action, lable=label, adsfadsfadsf,asdf,adsf,adf' => 123