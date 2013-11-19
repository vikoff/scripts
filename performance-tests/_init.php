<?php

class PerformanceMeter
{
	public $startTime;
	public $startMem;

	public $timeDiff;
	public $memDiff;

	public function start()
	{
		$this->startTime = microtime(1);
		$this->startMem = memory_get_peak_usage();
	}

	public function end($numCycleIterations)
	{
		$this->timeDiff = microtime(1) - $this->startTime;
		$this->memDiff = memory_get_peak_usage() - $this->startMem;

		$this->log(sprintf("iterations: %d, time: %.3f sec, memory diff: %.2f kB\n", $numCycleIterations, $this->timeDiff, $this->memDiff / 1000));
	}

	public function log($msg)
	{
		$msg .= "\n";
		echo PHP_SAPI == 'cli' ? $msg : "<pre>$msg</pre>";

		
	}
}