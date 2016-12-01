<?php

class PerformanceMeter
{
	public $startTime;
	public $startMem;
	public $startCpu;

	public $timeDiff;
	public $memDiff;
	public $cpuDiff;

	public function start()
	{
		$this->startTime = microtime(1);
		$this->startMem = memory_get_peak_usage();
		$this->startCpu = $this->_getCpuTime();
	}

	public function end($numCycleIterations)
	{
		$this->timeDiff = microtime(1) - $this->startTime;
		$this->memDiff = memory_get_peak_usage() - $this->startMem;
		$this->cpuDiff = $this->_getCpuTime() - $this->startCpu;

		$this->log(sprintf("iterations: %d | time: %.3f sec | cpu-time: %.3f sec | memory diff: %.2f kB\n",
			$numCycleIterations,
			$this->timeDiff,
			$this->cpuDiff,
			$this->memDiff / 1000
		));
	}

	public function log($msg)
	{
		$msg .= "\n";
		echo PHP_SAPI == 'cli' ? $msg : "<pre>$msg</pre>";

		
	}

    private function _getCpuTime()
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

}