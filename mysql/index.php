<?php
session_start();

// обозначение корня ресурса
define('CLI_MODE', PHP_SAPI == 'cli');
define('AJAX_MODE', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if (CLI_MODE) {
	define('WWW_ROOT', '');
	define('WWW_URI', '');
} else {
	$_url = dirname($_SERVER['SCRIPT_NAME']);
	define('WWW_ROOT', 'http://'.$_SERVER['SERVER_NAME'].(strlen($_url) > 1 ? $_url : '').'/');
	define('WWW_URI', 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
}
define('FS_ROOT', dirname(__FILE__).'/');

// определение ajax-запроса

// отправка Content-type заголовка
header('Content-Type: text/html; charset=utf-8');

// подключение файлов CMF
require_once(FS_ROOT.'setup.php');

// выполнение приложения
if (CLI_MODE)
	FrontController::get()->run_cli();
elseif(AJAX_MODE)
	FrontController::get()->run_ajax();
else
	FrontController::get()->run();