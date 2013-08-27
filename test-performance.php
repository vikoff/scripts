<?php

$startTime = microtime(1);
$startMem = memory_get_usage();

for ($i = 0; $i < 1000000; $i++) {

$str = 'hello';

$pos = substr($str, 0, 1); // time: 1.216 sec, memory diff: 1.16 kB
// $pos = $str[0]; // time: 0.503 sec, memory diff: 1.15 kB



}

$endTime = microtime(1);
$endMem = memory_get_usage();

if (PHP_SAPI != 'cli')
	echo '<pre>';

printf('time: %.3f sec, memory diff: %.2f kB', $endTime - $startTime, ($endMem - $startMem) / 1000);
?>