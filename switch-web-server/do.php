<?php

header('content-type: text/plain; charset=utf-8');

define('NGINX_HOSTS_DIR', '/etc/nginx/sites-enabled/');
define('APACHE_HOSTS_DIR', '/etc/apache2/sites-enabled/');
define('APACHE_PORTS_FILE', '/etc/apache2/ports.conf');

define('BACKUP_DIR', dirname(__FILE__).'/backup/');

class PortSwitcher {

	public static $keys = array('server', 'place', 'line', 'port', 'comment');
	public $occurrences = array();
	public $defaultServer = null;

	public function __construct()
	{
		$this->_fetchOccurrences();
		$this->_defineDefaultServer();
	}

	public function runInteractive()
	{
		$this->_printCurPorts();
		$this->_printActions();
	}

	private function _fetchOccurrences()
	{
		$this->occurrences = array();

		// nginx sites-enabled
		foreach (scandir(NGINX_HOSTS_DIR) as $name) {
			
			$fullname = NGINX_HOSTS_DIR.$name;
			
			if ($name == '.' || $name == '..' || is_dir($fullname))
				continue;
			
			$file = file($fullname);
			foreach ($file as $index => $row) {
				if (preg_match('/^[^#]*listen\D*(\d+)/i', $row, $matches)) {
					$port = $matches[1];
					$this->_fetchOccurrence('nginx', $name, $index, $port, 'host');
				}
			}
		}

		// apache ports.conf
		$file = file(APACHE_PORTS_FILE);
		foreach ($file as $index => $row) {
			if (preg_match('/^[^#]*Listen\D*(\d+)/i', $row, $matches)) {
				$port = $matches[1];
				$this->_fetchOccurrence('apache', 'ports.conf', $index, $port, 'Listen directive');
			}
			elseif (preg_match('/^[^#]*NameVirtualHost\D*(\d+)/i', $row, $matches)) {
				$port = $matches[1];
				$this->_fetchOccurrence('apache', 'ports.conf', $index, $port, 'NameVirtualHost directive');
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
					$this->_fetchOccurrence('apache', $name, $index, $port, 'host');
				}
			}
		}
	}

	private function _defineDefaultServer()
	{
		$default = array('apache' => 0, 'nginx' => 0);
		$keyServer = 0;
		$keyPort = 3;

		foreach ($this->occurrences as $item) {
			if ($item[$keyPort] == 80) {
				$default[ $item[$keyServer] ]++;
			}
		}

		if (!$default['apache'] && !$default['nginx']) {
			$this->defaultServer = null;
		} elseif ($default['apache'] && $default['nginx']) {
			$this->defaultServer = null;
		} else {
			$this->defaultServer = $default['apache'] > $default['nginx'] ? 'apache' : 'nginx';
		}
	}

	private function _fetchOccurrence($server, $place, $line, $port, $comment = ''){
		$this->occurrences[] = array($server, $place, $line + 1, $port, $comment);
	}

	private function _flipPorts()
	{
		$ports = array(
			'apache' => array('old' => null, 'new' => null),
			'nginx'  => array('old' => null, 'new' => null),
		);
		$newDefaultServer = null;

		if ($this->defaultServer == 'apache') {
			$ports = array(
				'apache' => array('old' => 80, 'new' => 8080),
				'nginx'  => array('old' => 8080, 'new' => 80),
			);
			$newDefaultServer = 'nginx';
		} elseif ($this->defaultServer == 'nginx') {
			$ports = array(
				'apache' => array('old' => 8080, 'new' => 80),
				'nginx'  => array('old' => 80, 'new' => 8080),
			);
			$newDefaultServer = 'apache';
		} else {
			echo "ERROR: unable to define default server";
			exit;
		}

		$this->_replaceApache($ports['apache']['old'], $ports['apache']['new']);
		$this->_replaceNginx($ports['nginx']['old'], $ports['nginx']['new']);

		echo "\nCOMPLETE. Now default server is $newDefaultServer\n\n";
		echo "restarting web servers...\n";
		echo "stop apache\n";
		`/etc/init.d/apache2 stop`;
		echo "stop nginx\n";
		`/etc/init.d/nginx stop`;
		echo "start apache\n";
		`/etc/init.d/apache2 start`;
		echo "start nginx\n";
		`/etc/init.d/nginx start`;

	}

	private function _printCurPorts()
	{
		$maxWidths = $this->_checkMaxWidth(array_merge(array(self::$keys), $this->occurrences));
		$mask = "|";
		$output = '';

		foreach($maxWidths as $w)
			$mask .= " %-${w}s |";
		$mask .= "\n";

		$separator = str_repeat('-', array_sum($maxWidths) + count(self::$keys) * 3 + 1)."\n";
		$output .= $separator.call_user_func_array('sprintf', array_merge(array($mask), self::$keys)).$separator;
		foreach ($this->occurrences as $row)
			$output .= call_user_func_array('sprintf', array_merge(array($mask), $row));
		$output .= $separator."\n";

		echo $output;
		// echo $helpArgFormat;
	}

	private function _printActions()
	{
		echo "now default server is $this->defaultServer\n\n";
		echo "please, select an action:\n\n";
		echo "    [1] switch default server to ".($this->defaultServer == 'apache' ? 'nginx' : 'apache')."\n";
		echo "    [2] show help\n";
		echo "    [3] exit\n";
		echo "\nyour choice [1]: ";
		$ans = trim(fgets(STDIN));
		if (!strlen($ans)) $ans = '1';

		switch ($ans) {
			case '1':
				echo "flip ports\n";
				$this->_flipPorts();
				break;
			case '2':
				echo "not today\n";
				break;
			case '3':
			default:
				echo "exit\n";
				break;
		}
	}

	private function _replaceApache($oldPort, $newPort){
		
		$globalHasChanges = false;
		
		// apache ports.conf
		$hasChanges = false;
		$file = file(APACHE_PORTS_FILE);
		foreach ($file as $index => & $row) {
			if (preg_match("/^[^#]*Listen\D*$oldPort(\D|$)/i", $row)) {
				$this->_logReplace('apache', 'ports.conf', $index, $oldPort, $newPort, 'Listen directive');
				$row = preg_replace("/^([^#]*Listen\D*)$oldPort(\D|$)/i", '${1}'.$newPort.'${2}', $row);
				$hasChanges = true;
			}
			elseif (preg_match("/^[^#]*NameVirtualHost\D*$oldPort(\D|$)/i", $row)) {
				$this->_logReplace('apache', 'ports.conf', $index, $oldPort, $newPort, 'NameVirtualHost directive');
				$row = preg_replace("/^([^#]*NameVirtualHost\D*)$oldPort(\D|$)/i", '${1}'.$newPort.'${2}', $row);
				$hasChanges = true;
			}
		}
		unset($row);
		
		if ($hasChanges) {
			$globalHasChanges = true;
			$this->_backup(APACHE_PORTS_FILE);
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
				if (preg_match("/^[^#]*VirtualHost\D*$oldPort(\D|$)/i", $row)) {
					$row = preg_replace("/^([^#]*VirtualHost\D*)$oldPort(\D|$)/i", '${1}'.$newPort.'${2}', $row);
					$this->_logReplace('apache', $name, $index, $oldPort, $newPort, 'host');
					$hasChanges = true;
				}
			}
			unset($row);
			
			if ($hasChanges) {
				$globalHasChanges = true;
				$this->_backup($fullname);
				file_put_contents($fullname, implode("", $file));
			}
		}
		
		return $globalHasChanges;
	}

	private function _replaceNginx($oldPort, $newPort){
		
		$globalHasChanges = false;
		
		foreach (scandir(NGINX_HOSTS_DIR) as $name) {
		
			$fullname = NGINX_HOSTS_DIR.$name;
			
			if ($name == '.' || $name == '..' || is_dir($fullname))
			continue;
			
			$hasChanges = false;
			$file = file($fullname);
			foreach ($file as $index => & $row) {
				if (preg_match("/^[^#]*listen\D*$oldPort(\D|$)/i", $row, $matches)) {
					$row = preg_replace("/^([^#]*listen\D*)$oldPort(\D|$)/i", '${1}'.$newPort.'${2}', $row);
					$this->_logReplace('nginx', $name, $index, $oldPort, $newPort, 'host');
					$hasChanges = true;
				}
			}
			unset($row);
			
			if ($hasChanges) {
				$globalHasChanges = true;
				$this->_backup($fullname);
				file_put_contents($fullname, implode("", $file));
			}
		}
		
		return $globalHasChanges;
	}

	private function _backup($file){
		
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

	private function _logReplace($server, $place, $line, $oldPort, $newPort, $comment = ''){
		$line++;
		echo "\t$server: $oldPort->$newPort at {$place}[line $line] ($comment)\n";
	}

	private function _checkMaxWidth($values){
		
		$maxWidths = array();
		foreach ($values as $row)
			foreach ($row as $k => $v)
				$maxWidths[$k] = max(strlen($v), isset($maxWidths[$k]) ? $maxWidths[$k] : 0);
		return $maxWidths;
	}

}

$switcher = new PortSwitcher();
$switcher->runInteractive();
die;
print_r($switcher->defaultServer);
echo "\n";
die;
$defaultAction = TRUE;
$replacePorts = array();

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
		$defaultAction = FALSE;
}

$keys = array('server', 'place', 'line', 'port', 'comment');
$values = array();

if ($defaultAction) {
	runInteractive();
}

$restartApache = false;
$restartNginx = false;

// apache
if (isset($replacePorts['apache'])) {
	$restartApache = _replaceApache($replacePorts['apache']['oldPort'], $replacePorts['apache']['newPort']);
}

// nginx
if (isset($replacePorts['nginx'])) {
	$restartNginx = _replaceNginx($replacePorts['nginx']['oldPort'], $replacePorts['nginx']['newPort']);
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
