
<h1>Help</h1>

<ol class="breadcrumb">
	<li class="pull-right">
		<a href="<?= href('x-trace/parse-new'); ?>" class="btn btn-default btn-xs">Parse new trace</a>
	</li>
	<li><a href="<?= href('/'); ?>">Home</a></li>
	<li><a href="<?= href('x-trace'); ?>">Xdebug Traces</a></li>
	<li>Help</li>
</ol>

<h2>About</h2>

<h2>How to use</h2>
To use this visualizer you need to make two steps
<ol>
	<li>Make xdebug trace file;</li>
	<li>Load xdebug trace file into visualizer (<a href="<?= href('x-trace/parse-new'); ?>">here</a>) and wait while it will be parsed.</li>
</ol>

<h2>Xdebug config</h2>
To generate xdebug functions trace file you may use this snippet of xdebug config.

<pre>
;;;;;;;;;;;;;;;;;;;;;
;; FUNC CALL TRACE ;;
;;;;;;;;;;;;;;;;;;;;;
xdebug.auto_trace=0;
; use GET/POST/COOKIE param XDEBUG_TRACE to log func call trace
xdebug.trace_enable_trigger=1;
xdebug.show_mem_delta=1
xdebug.collect_return=1
;xdebug.trace_output_dir=/tmp/xdebug-trace/
xdebug.trace_output_dir=/tmp/xdebug/traces/
; 0 - human readable format
; 1 - machine readable format
; 2 - html format
xdebug.trace_format=1
xdebug.trace_output_name=trace.%t.%R
</pre>

In debian based systems xdebug.ini can be found here:
<code> /etc/php5/conf.d/xdebug.ini </code>