<?php

define('BACKUP_DIR', dirname(__FILE__).'/backup/');
define('MYSQL_CONFIG_FILE', '/etc/mysql/my.cnf');

if (PHP_SAPI != 'cli')
	exit('command line run only');

function backup($file){
	
	static $dir = false;
	
	if (!$dir) {
		$dir = BACKUP_DIR.date('Y-m-d_H-i-s').'/';
		mkdir($dir, 0777, true) or die('could not create backup dir ['.$dir.']');
	}
	
	$dst = $dir.basename($file);
	$dstDir = dirname($dst);
	if (!is_dir($dstDir))
		mkdir($dstDir, 0777, true) or die('could not create dir ['.$dstDir.']');
	
	copy($file, $dst) or die('could not copy file ['.$file.'] to backup dir ['.$dir.']');
}

if (!file_exists(MYSQL_CONFIG_FILE))
	exit("ERROR: mysql config file not found at {MYSQL_CONFIG_FILE}\n");

$file = file(MYSQL_CONFIG_FILE);


$mysqlLogFile = null;
$mysqlLogEnable = null;

foreach ($file as $line => $string) {
	if (preg_match('/^\s*(#)?\s*general_log_file\s*=\s*(.*)\s*$/', $string, $matches)) {
		$mysqlLogFile = array(
			'line' => $line,
			'commented' => !empty($matches[1]),
			'path' => $matches[2],
		);
	}
	if (preg_match('/^\s*(#)?\s*general_log\s*=\s*(.*)\s*$/', $string, $matches)) {
		$mysqlLogEnable = array(
			'line' => $line,
			'commented' => !empty($matches[1]),
			'value' => (int)$matches[2],
		);
		$mysqlLogEnable['enabled'] = !$mysqlLogEnable['commented'] && $mysqlLogEnable['value'];
	}
	elseif (preg_match('/\bgeneral_log\s*=/', $string))
		$mysqlLogEnable = array('line' => $line, 'string' => $string);
	elseif ($mysqlLogFile && $mysqlLogEnable)
		break;
}

if (empty($mysqlLogFile))
	exit("ERROR: no 'general_log_file' key found in mysql config file\n");

if (empty($mysqlLogEnable))
	exit("ERROR: no 'general_log' key found in mysql config file\n");

echo "CURRENT STATE: ".($mysqlLogEnable['enabled'] ? 'enabled' : 'disabled')
	.", log file: {$mysqlLogFile['path']} (line {$mysqlLogFile['line']}".($mysqlLogFile['commented'] ? ', directive commented' : '').")
";

if ($argc < 2) {
	echo " USAGE:
	use flags 'enable', 'disable', 'clear', 'cat', 'watch'
	FOR EXAMPLE: do.php enable             # to enable logging
	             do.php enable cat         # to enable logging and then cat log to screen
	             do.php enable clear watch # to enable logging, clear log and show it in watch
	             do.php disable            # to disable logging
";
	exit;
}

// сбор опций
$options = array('enable' => 0, 'disable' => 0, 'clear' => 0, 'cat' => 0, 'watch' => 0);
foreach (array_slice($argv, 1) as $o)
	$options[strtolower($o)] = 1;

// DISABLE
if ($options['disable']) {

	echo "disable mysql log\n";
	if (!is_writeable(MYSQL_CONFIG_FILE))
		exit("ERROR: mysql config file is not writeable\n");

	backup(MYSQL_CONFIG_FILE);
	$file[ $mysqlLogEnable['line'] ] = "general_log      = 0\n";
	$file[ $mysqlLogFile['line'] ]   = "general_log_file = {$mysqlLogFile['path']}\n";
	file_put_contents(MYSQL_CONFIG_FILE, implode('', $file));
	echo `service mysql restart`;
	echo "COMPLETE!\n";
}
// ENABLE
elseif ($options['enable']) {

	echo "enable mysql log\n";
	if (!is_writeable(MYSQL_CONFIG_FILE))
		exit("ERROR: mysql config file is not writeable\n");

	backup(MYSQL_CONFIG_FILE);
	$file[ $mysqlLogEnable['line'] ] = "general_log      = 1\n";
	$file[ $mysqlLogFile['line'] ]   = "general_log_file = {$mysqlLogFile['path']}\n";
	file_put_contents(MYSQL_CONFIG_FILE, implode('', $file));
	echo `service mysql restart`;
	echo "COMPLETE!\n";
}

//CLEAR
if ($options['clear']) {
	echo "clear mysql log file\n";
	file_put_contents($mysqlLogFile['path'], '');
}

// CAT
if ($options['cat']) {
	$command = "cat {$mysqlLogFile['path']}";
	echo $command."\n";
	passthru($command);
}
// WATCH
elseif ($options['watch']) {
	$command = "tail -f -n 100 {$mysqlLogFile['path']}";
	echo $command."\n";
	passthru($command);
}


