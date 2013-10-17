<?php

$startTime = microtime(1);
$startMem = memory_get_usage();


$blacklist = array();
$domain = 'www.ololo.accounts.google.com';

function func1(){
	$blacklist = array();
	$domain = 'www.ololo.accounts.google.com';
	$parts = explode('.', $domain);
	for ($i = count($parts) - 2; $i >=0; $i--) {
		$snippet = implode('.', $parts);
		array_shift($parts);
		isset($blacklist[$snippet]);
		// echo $snippet;
		// echo "<br>";
	}
}

function func2(){
	$domain = 'www.ololo.accounts.google.com';
	$parts = explode('.', $domain);
	$count = count($parts);
	for ($i = 0; $i <= $count - 2; $i++) {
		$snippet = implode('.', array_slice($parts, $i));
		isset($blacklist[$snippet]);
		// echo $snippet;
		// echo "<br>";
	}
}

for ($_i = 0; $_i < 100000; $_i++) {
	// func1();
	func2();
}

$endTime = microtime(1);
$endMem = memory_get_usage();

if (PHP_SAPI != 'cli')
	echo '<pre>';

printf("time: %.3f sec, memory diff: %.2f kB\n", $endTime - $startTime, ($endMem - $startMem) / 1000);
?>