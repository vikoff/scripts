<?

class FrontController extends Controller{
	
	const DEFAULT_CONTROLLER = 'sql';

	private static $_instance = null;
	
	public $requestMethod = null;
	public $requestParams = array();
	
	private $_defaultControllerInstance = null;

	/** контейнер обмена данными между методами */
	public $data = array();
	
	
	/** получение экземпляра FrontController */
	public static function get(){
		
		if(is_null(self::$_instance))
			self::$_instance = new FrontController();
		
		return self::$_instance;
	}
	
	/**
	 * Приватный конструктор.
	 * Доступ к объекту осуществляется через статический метод self::get()
	 * Выполняет примитивную авторизацию пользователя.
	 * Парсит полученную query string.
	 * @access private
	 */
	private function __construct(){
		
		// авторизация
		$this->_checkAuth();
		
		// парсинг запроса
		$requestStr = CLI_MODE 
			? (isset($GLOBALS['argv'][1]) ? $GLOBALS['argv'][1] : '')
			: (isset($_GET['r']) ? $_GET['r'] : '');

		$request = explode('/', $requestStr);
		$_rMethod = array_shift($request);
		
		$this->requestMethod = !empty($_rMethod) ? $_rMethod : 'index';
		$this->requestParams = $request;
	}

	public function getDefaultController() {

		if ($this->_defaultControllerInstance === null) {
			if (self::DEFAULT_CONTROLLER) {
				$class = $this->getControllerClassName(self::DEFAULT_CONTROLLER);
				if (!$class)
					throw new Exception('default controller not found');
				$this->_defaultControllerInstance = new $class;
			} else {
				$this->_defaultControllerInstance = $this;
			}
		}

		return $this->_defaultControllerInstance;
	}
	
	/** запуск приложения */
	public function run(){
		
		$this->_checkAction();
		
		if($this->_checkDisplay())
			exit;
		
		$this->display_404();
	}
	
	/** запуск приложения в ajax-режиме */
	public function run_ajax(){
		
		if($this->_checkAction())
			exit;
			
		if($this->_checkAjax())
			exit;
		
		if($this->_checkDisplay())
			exit;
		
		$this->display_404();
	}

	public function run_cli(){

		if (!$this->_checkCli())
			echo "ERROR: method '$this->requestMethod' not found\n";
	}
	
	/** проверка авторизации */
	private function _checkAuth(){
		
		if(getVar($_POST['action']) == 'login')
			$this->action_login();
		
		// if(empty($_SESSION['logged']))
			// $this->display_login();
	}
	
	/** проверка необходимости выполнения действия */
	private function _checkAction(){
		
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
			$controllerClass = $this->getControllerClassName($controller);
			
			if(empty($controllerClass)){
				$this->display_404('action '.$controllerClass.'/'.$action.' not found');
				exit;
			}
			
			$instance = new $controllerClass();
			return $instance->action($action, getVar($_POST['redirect']));
		}
		// если action вида 'action'
		else{
			return $this->getDefaultController()->action($action, getVar($_POST['redirect']));
		}
	}
	
	/** проверка необходимости выполнения отображения */
	private function _checkDisplay(){

		return $this->getDefaultController()->display($this->requestMethod, $this->requestParams);
	}
	
	/** проверка необходимости выполнения ajax */
	private function _checkAjax(){
		
		return $this->getDefaultController()->ajax($this->requestMethod, $this->requestParams);
	}
	
	/** проверка необходимости выполнения cli */
	private function _checkCli(){
		
		return $this->getDefaultController()->cli($this->requestMethod, $this->requestParams);
	}
	
	
	

}
