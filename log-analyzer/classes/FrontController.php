<?php

class FrontController
{
	const DEFAULT_CONTROLLER = '';

	protected static $_instance = null;
	
	public $requestAction = null;
	public $requestParams = array();

	protected $_controllerInstances = array();

	/** контейнер обмена данными между методами */
	public $data = array();
	
	
	/** @return FrontController */
	public static function get()
	{
		if(!self::$_instance)
			self::$_instance = new self();
		
		return self::$_instance;
	}
	
	/**
	 * Приватный конструктор.
	 * Доступ к объекту осуществляется через статический метод self::get()
	 * Выполняет примитивную авторизацию пользователя.
	 * Парсит полученную query string.
	 * @access protected
	 */
	protected function __construct()
	{
		// авторизация
		$this->_checkAuth();

		if (CLI_MODE) {
			$argv = $GLOBALS['argv'];
			$request = array_slice($argv, 2);
			$action = isset($GLOBALS['argv'][1]) ? $GLOBALS['argv'][1] : '';
		} else {
			$requestStr = isset($_GET['r']) ? $_GET['r'] : '';
			$request = explode('/', $requestStr);
			$action = array_shift($request);
		}

		$this->requestAction = $action;
		$this->requestParams = $request;
	}

	public function getDefaultController()
	{
		$controllerKey = self::DEFAULT_CONTROLLER ? self::DEFAULT_CONTROLLER : 'index';
		$instance = $this->_getController($controllerKey);
		return $instance;
	}
	
	/** запуск приложения */
	public function run()
	{
		try {
			$this->_checkAction();

			if($this->_checkDisplay())
				return;

			$this->getDefaultController()->display_404($this->requestAction);

		} catch (Exception $e) {
			$this->getDefaultController()->display_error($e->getMessage(), $e);
		}
	}
	
	/** запуск приложения в ajax-режиме */
	public function run_ajax()
	{
		try {
			if($this->_checkAction())
				return;

			if($this->_checkAjax())
				return;

			if($this->_checkDisplay())
				return;

			$this->getDefaultController()->display_404();

		} catch (Exception $e) {
			$this->getDefaultController()->display_error($e->getMessage(), $e);
		}
	}

	public function run_cli()
	{
		try {
			if (!$this->_checkCli())
				throw new Exception("method '$this->requestAction' not found\n");
		} catch (Exception $e) {
			echo "ERROR: ".$e->getMessage()."\n";
		}
	}
	
	/** проверка авторизации */
	protected function _checkAuth(){
		
//		if(getVar($_POST['action']) == 'login')
//			$this->action_login();
		
		// if(empty($_SESSION['logged']))
			// $this->display_login();
	}

	/**
	 * получить экземпляр контроллера по идентификатору
	 * @param string $controllerKey - идентификатор контроллера
	 * @param bool $supressExceptionIfNotFound
	 * @throws Exception
	 * @return Controller|null
	 */
	protected function _getController($controllerKey, $supressExceptionIfNotFound = FALSE)
	{
		do {
			// если идентификатор контроллера не передан, вернем null
			if(!$controllerKey)
				break;

			if (isset($this->_controllerInstances[$controllerKey]))
				return $this->_controllerInstances[$controllerKey];

			// если идентификатор контроллера содержит недопустимые символы, вернем null
			if(!preg_match('/^[\w\-]+$/', $controllerKey))
				break;

			// преобразует строку вида 'any-class-name' в 'AnyClassNameController'
			$controller = str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($controllerKey)))).'Controller';
			if (!class_exists($controller))
				break;

			$instance = new $controller;
			$this->_controllerInstances[$controllerKey] = $instance;
			return $instance;

		} while (FALSE);

		if ($supressExceptionIfNotFound)
			return null;
		else
			throw new Exception("Controller $controllerKey not found");
	}
	
	/** проверка необходимости выполнения действия */
	protected function _checkAction()
	{
		if(!isset($_POST['action']) || !checkFormDuplication())
			return FALSE;
		
		if (is_array($_POST['action'])) {
			reset($_POST['action']);
			$action = key($_POST['action']);
		} else {
			$action = $_POST['action'];
		}

		if (!is_string($action))
			return FALSE;
		
		// если action вида 'controller/action'
		if(strpos($action, '/')){
			list($controller, $action) = explode('/', $action);
			return $this->_getController($controller)->action($action, getVar($_POST['redirect']));
		}
		// если action вида 'action'
		else{
			return $this->getDefaultController()->action($action, getVar($_POST['redirect']));
		}
	}

	/** проверка необходимости выполнения отображения */
	protected function _checkDisplay()
	{
		// первая попытка - дефолтный контроллер и передано лишь имя экшена
		$defaultCall = $this->getDefaultController()->display($this->requestAction, $this->requestParams);
		if ($defaultCall)
			return TRUE;

		// вторая попытка - передан controller/action
		$controller = $this->requestAction;
		$params = $this->requestParams;
		$action = array_shift($params);
		$controllerInstance = $this->_getController($controller, TRUE);
		if ($controllerInstance)
			return $controllerInstance->display($action, $params);

		return FALSE;
	}

	/** проверка необходимости выполнения ajax */
	protected function _checkAjax()
	{
		// первая попытка - дефолтный контроллер и передано лишь имя экшена
		$defaultCall = $this->getDefaultController()->ajax($this->requestAction, $this->requestParams);
		if ($defaultCall)
			return TRUE;

		$controller = $this->requestAction;
		$params = $this->requestParams;
		$action = array_shift($params);
		$controllerInstance = $this->_getController($controller, TRUE);
		if ($controllerInstance)
			return $controllerInstance->ajax($action, $params);

		return FALSE;
	}

	/** проверка необходимости выполнения cli */
	protected function _checkCli()
	{
		// если запрос вида controller/action
		if (strpos($this->requestAction, '/')) {
			list($controller, $action) = explode('/', $this->requestAction, 2);
			return $this->_getController($controller)->cli($action, $this->requestParams);
		}
		// если запрос вида 'action'
		else{
			return $this->getDefaultController()->cli($this->requestAction, $this->requestParams);
		}
	}

}
