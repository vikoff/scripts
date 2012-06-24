<?php

header('content-type: text/plain; charset=utf-8');

$showCurPorts = TRUE;
$replacePorts = array();

define('NGINX_HOSTS_DIR', '/etc/nginx/sites-enabled/');
define('APACHE_HOSTS_DIR', '/etc/apache2/sites-enabled/');
define('APACHE_PORTS_FILE', '/etc/apache2/ports.conf');

define('BACKUP_DIR', dirname(__FILE__).'/backup/');

$helpArgFormat = "USAGE:
	for replace ports use command:
	sudo php ".__FILE__." server1:old_port-new_port [server2:old_port-new_port]
EXAMPLES:
	sudo php ".__FILE__." apache:80-8080 nginx:8080-80
	sudo php ".__FILE__." apache:8080-80 nginx:80-8080
	sudo php ".__FILE__." apache:80-8080
";

if (isset($argv) && $argc > 1) {
	
	foreach (array_slice($argv, 1) as $key) {
		if (!preg_match('/^(apache|nginx):(\d+)-(\d+)$/', $key, $matches)) {
			echo $helpArgFormat;
			exit;
		}
		$replacePorts[ $matches[1] ] = array('oldPort' => $matches[2], 'newPort' => $matches[3]);
	}
	
	if (!empty($replacePorts))
		$showCurPorts = FALSE;
}

$keys = array('server', 'place', 'line', 'port', 'comment');
$values = array();

function backup($file){
	
	static $dir = false;
	
	if (!$dir) {
		$dir = BACKUP_DIR.date('Y-m-d_H-i-s').'/';
		mkdir($dir, 0777, true) or die('could not create backup dir ['.$dir.']');
	}
	
	$dst = $dir.$file;
	$dstDir = dirname($dst);
	if (!is_dir($dstDir))
		mkdir($dstDir, 0777, true) or die('could not create dir ['.$dstDir.']');
	
	copy($file, $dst) or die('could not copy file ['.$file.'] to backup dir ['.$dir.']');
}

function logPlace($server, $place, $line, $port, $comment = ''){
	global $values;
	$values[] = array($server, $place, $line + 1, $port, $comment);
}

function logReplace($server, $place, $line, $oldPort, $newPort, $comment = ''){
	$line++;
	echo "\t$server: $oldPort->$newPort at {$place}[line $line] ($comment)\n";
}

function checkMaxWidth($values){
	
	$maxWidths = array();
	foreach ($values as $row)
		foreach ($row as $k => $v)
			$maxWidths[$k] = max(strlen($v), isset($maxWidths[$k]) ? $maxWidths[$k] : 0);
	return $maxWidths;
}

function printCurPorts(){
	
	global $keys, $values, $helpArgFormat;
	
	// nginx sites-enabled
	foreach (scandir(NGINX_HOSTS_DIR) as $name) {
		
		$fullname = NGINX_HOSTS_DIR.$name;
		
		if ($name == '.' || $name == '..' || is_dir($fullname))
			continue;
		
		$file = file($fullname);
		foreach ($file as $index => $row) {
			if (preg_match('/^[^#]*listen\D*(\d+)/i', $row, $matches)) {
				$port = $matches[1];
				logPlace('nginx', $name, $index, $port, 'host');
			}
		}
	}

	// apache ports.conf
	$file = file(APACHE_PORTS_FILE);
	foreach ($file as $index => $row) {
		if (preg_match('/^[^#]*Listen\D*(\d+)/i', $row, $matches)) {
			$port = $matches[1];
			logPlace('apache', 'ports.conf', $index, $port, 'Listen directive');
		}
		elseif (preg_match('/^[^#]*NameVirtualHost\D*(\d+)/i', $row, $matches)) {
			$port = $matches[1];
			logPlace('apache', 'ports.conf', $index, $port, 'NameVirtualHost directive');
		}
	}

	// apache sites-enabled
	foreach (scandir(APACHE_HOSTS_DIR) as $name) {
		
		$fullname = APACHE_HOSTS_DIR.$name;
		
		if ($name == '.' || $name == '..' || is_dir($fullname))
		continue;
		
		$file = file($fullname);
		foreach ($file as $index => $row) {
			if (preg_match('/^[^#]*VirtualHost\D*(\d+)/i', $row, $matches)) {
				$port = $matches[1];
				logPlace('apache', $name, $index, $port, 'host');
			}
		}
	}

	$maxWidths = checkMaxWidth(array_merge(array($keys), $values));
	$mask = "|";
	$output = '';

	foreach($maxWidths as $w)
		$mask .= " %-${w}s |";
	$mask .= "\n";

	// echo $mask ;die;
	$separator = str_repeat('-', array_sum($maxWidths) + count($keys) * 3 + 1)."\n";
	$output .= $separator.call_user_func_array('sprintf', array_merge(array($mask), $keys)).$separator;
	foreach ($values as $row)
		$output .= call_user_func_array('sprintf', array_merge(array($mask), $row));
	$output .= $separator;

	echo $output;
	echo $helpArgFormat;
}

function replaceApache($oldPort, $newPort){
	
	$globalHasChanges = false;
	
	// apache ports.conf
	$hasChanges = false;
	$file = file(APACHE_PORTS_FILE);
	foreach ($file as $index => & $row) {
		if (preg_match("/^[^#]*Listen\D*$oldPort\D*/i", $row)) {
			logReplace('apache', 'ports.conf', $index, $oldPort, $newPort, 'Listen directive');
			$row = preg_replace("/^([^#]*Listen\D*)$oldPort(\D*)/i", '${1}'.$newPort.'${2}', $row);
			$hasChanges = true;
		}
		elseif (preg_match("/^[^#]*NameVirtualHost\D*$oldPort\D*/i", $row)) {
			logReplace('apache', 'ports.conf', $index, $oldPort, $newPort, 'NameVirtualHost directive');
			$row = preg_replace("/^([^#]*NameVirtualHost\D*)$oldPort(\D*)/i", '${1}'.$newPort.'${2}', $row);
			$hasChanges = true;
		}
	}
	unset($row);
	
	if ($hasChanges) {
		$globalHasChanges = true;
		backup(APACHE_PORTS_FILE);
		file_put_contents(APACHE_PORTS_FILE, implode("", $file));
	}
	
	// apache sites-enabled
	foreach (scandir(APACHE_HOSTS_DIR) as $name) {
		
		$fullname = APACHE_HOSTS_DIR.$name;
		
		if ($name == '.' || $name == '..' || is_dir($fullname))
		continue;
		
		$hasChanges = false;
		$file = file($fullname);
		foreach ($file as $index => & $row) {
			if (preg_match("/^[^#]*VirtualHost\D*$oldPort\D*/i", $row)) {
				$row = preg_replace("/^([^#]*VirtualHost\D*)$oldPort(\D*)/i", '${1}'.$newPort.'${2}', $row);
				logReplace('apache', $name, $index, $oldPort, $newPort, 'host');
				$hasChanges = true;
			}
		}
		unset($row);
		
		if ($hasChanges) {
			$globalHasChanges = true;
			backup($fullname);
			file_put_contents($fullname, implode("", $file));
		}
	}
	
	return $globalHasChanges;
}

function replaceNginx($oldPort, $newPort){
	
	$globalHasChanges = false;
	
	foreach (scandir(NGINX_HOSTS_DIR) as $name) {
	
		$fullname = NGINX_HOSTS_DIR.$name;
		
		if ($name == '.' || $name == '..' || is_dir($fullname))
		continue;
		
		$hasChanges = false;
		$file = file($fullname);
		foreach ($file as $index => & $row) {
			if (preg_match("/^[^#]*listen\D*$oldPort\D*/i", $row, $matches)) {
				$row = preg_replace("/^([^#]*listen\D*)$oldPort(\D*)/i", '${1}'.$newPort.'${2}', $row);
				logReplace('nginx', $name, $index, $oldPort, $newPort, 'host');
				$hasChanges = true;
			}
		}
		unset($row);
		
		if ($hasChanges) {
			$globalHasChanges = true;
			backup($fullname);
			file_put_contents($fullname, implode("", $file));
		}
	}
	
	return $globalHasChanges;
}

if ($showCurPorts)
	printCurPorts();

$restartApache = false;
$restartNginx = false;

// apache
if (isset($replacePorts['apache'])) {
	$restartApache = replaceApache($replacePorts['apache']['oldPort'], $replacePorts['apache']['newPort']);
}

// nginx
if (isset($replacePorts['nginx'])) {
	$restartNginx = replaceNginx($replacePorts['nginx']['oldPort'], $replacePorts['nginx']['newPort']);
}

if ($restartApache) {
	echo "stop apache\n";
	`/etc/init.d/apache2 stop`;
}
if ($restartNginx) {
	echo "stop nginx\n";
	`/etc/init.d/nginx stop`;
}

if ($restartApache) {
	echo "start apache\n";
	`/etc/init.d/apache2 start`;
}
if ($restartNginx) {
	echo "start nginx\n";
	`/etc/init.d/nginx start`;
}
