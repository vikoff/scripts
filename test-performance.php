<?php

function getCpuElapsedTime()
{
    if (function_exists("getrusage")) {
        $data = getrusage();
        $userTime = $data['ru_utime.tv_sec'] + $data['ru_utime.tv_usec'] / 1000000;
        $systemTime = $data['ru_stime.tv_sec'] + $data['ru_stime.tv_usec'] / 1000000;
        $totalTime = $userTime + $systemTime;

        return $totalTime;
    }

    return 0;
}

$startCpu = getCpuElapsedTime();
$startTime = microtime(1);
$startMem = memory_get_usage();
#####################################################

$iterations = 1000;
for ($_i = 0; $_i < $iterations; $_i++) {

	// iterations: 1000, time: 0.273 sec, memory diff: 22.94 kB, cpu: 0.151 sec
	file_get_contents('http://localhost');  







	// iterations: 1000, time: 0.298 sec, memory diff: 19.06 kB, cpu: 0.166 sec
        // $curl = curl_init('http://localhost');
        // curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        // curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);

        // curl_exec($curl);
        // curl_close($curl);

}


#####################################################
$endTime = microtime(1);
$endMem = memory_get_peak_usage();

if (PHP_SAPI != 'cli')
	echo '<pre>';

printf("iterations: %d, time: %.3f sec, memory diff: %.2f kB, cpu: %.3f sec\n",
	$iterations,
	$endTime - $startTime,
	($endMem - $startMem) / 1000,
	getCpuElapsedTime() - $startCpu
);
?>