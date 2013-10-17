

<div class="popup-tabs">
	<ul>
		<li><a href="#tab-details">Func Details</a></li>
		<li><a href="#tab-stack">Func Callstack</a></li>
	</ul>
	<div id="tab-details">
		<h1>Function Details</h1>
		<pre><?= htmlspecialchars(print_r($this->funcData, 1)); ?></pre>
	</div>

	<div id="tab-stack">
		<h1>Function Call Stack</h1>
		<div class="calltrace">
			<?php foreach ($this->parents as $parent) { ?>
				<div class="item">
					<div class="row1"><?= "#$parent[level] $parent[func_name]($parent[args_str])"; ?></div>
					<div class="row2"><?= "at <span>$parent[call_file]:$parent[call_line]</span>"; ?></div>
				</div>
			<?php } ?>
		</div>
		<pre><?= htmlspecialchars(print_r($this->parents)); ?></pre>
	</div>
</div>

<script>
$(function() {
	$( ".popup-tabs" ).tabs();
});
</script>