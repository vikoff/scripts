<?

class FrontController extends Controller{
	
	const DEFAULT_CONTROLLER = 'Front';

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
	private function __construct() {
		
		// авторизация
		$this->_checkAuth();

		if (CLI_MODE) {
			$argv = $GLOBALS['argv'];
			$request = array_slice($argv, 2);
			$_rMethod = isset($GLOBALS['argv'][1]) ? $GLOBALS['argv'][1] : '';
		} else {
			$requestStr = isset($_GET['r']) ? $_GET['r'] : '';
			$request = explode('/', $requestStr);
			$_rMethod = array_shift($request);
		}

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

		try {
			$this->_checkAction();

			if($this->_checkDisplay())
				return;

			$this->display_404();

		} catch (Exception $e) {
			$this->display_error($e->getMessage());
		}
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

		try {
			if (!$this->_checkCli())
				throw new Exception("method '$this->requestMethod' not found\n");
		} catch (Exception $e) {
			echo "ERROR: ".$e->getMessage()."\n";
		}
	}
	
	/** проверка авторизации */
	private function _checkAuth(){
		
//		if(getVar($_POST['action']) == 'login')
//			$this->action_login();
		
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

	/////////////////////
	////// DISPLAY //////
	/////////////////////
       
	public function display_index()
	{
		Layout::get()
			->setContentPhpFile('index.php')
			->render();
	}

	public function display_sql()
	{
		$statClass = new SqlLogStat();
//		$stat = array();
		$stat = $statClass->getStat();

//		echo '<pre>'; var_dump($stat); die; // DEBUG

		Layout::get()
			->setContentPhpFile('sql.php', $stat)
			->render();
	}

	public function display_xdebug_trace($sessId = null)
	{
		$sessId = (int)$sessId;

		if (!$sessId) {

			$list = StatXdebugTrace::load()->getTracesList();
			$vars = array('list' => $list);
			Layout::get()
				->setContentPhpFile('xdebug-trace-list.php', $vars)
				->render();

		} else {

			$data = StatXdebugTrace::load()->getFirstLevelCalls($sessId);
			$vars = array(
				'calls' => $data['calls'],
				'basePath' => $data['sessData']['app_base_path'],
				'sessId' => $sessId,
				'sessData' => $data['sessData'],
			);
			Layout::get()
				->setContentPhpFile('xdebug-trace.php', $vars)
				->render();
		}
	}

	public function ajax_xdebug_trace_get_children()
	{
		$sessId = getVar($_GET['sess'], 0, 'int');
		$id = getVar($_GET['id'], 0, 'int');
		if (!$sessId || !$id) {
			Layout::get()->renderJson(array('success' => 0, 'error' => 'Invalid input data'));
			return;
		}

		$calls = StatXdebugTrace::load()->getFuncChildren($sessId, $id);
		Layout::get()->renderJson(array('success' => 1, 'data' => $calls));
	}

	public function display_xdebug_trace_func_details($funcId = null)
	{
		$funcId = (int)$funcId;
		$funcData = StatXdebugTrace::load()->getFuncDetails($funcId);

		echo '<pre>'; print_r($funcData); echo '</pre>';
	}

	/////////////////////
	//////// CLI ////////
	/////////////////////

	public function cli_index()
	{
		$appCall = "php index.php";
		echo "AVAILABLE COMMANDS\n"
			."$appCall parse-sql 'path/to/sql-log-file'\n"
			."$appCall parse-xdebug-trace 'path/to/trace-file' ['json-options']\n"
			."    'json-options' may contain such keys: 'db_table', 'application', 'request_url', 'app_base_path', 'comments'\n"
			."    EXAMPLE: php index.php parse-xdebug-trace trace.xt '{\"db_table\":\"xdebug_trace1\", \"application\":\"homework\","
			." \"request_url\":\"/teacher/home\", \"app_base_path\":\"/var/www/homework/\", \"comments\":\"test run\"}'\n"
		;
	}

	public function cli_parse_sql()
	{
		global $argv;
		if (empty($argv[2]))
			exit("sql log file not specified\n");

		try {
			$parser = new ParserSqlLog($argv[2]);
			$parser->parse();
		} catch (Exception $e) {
			echo "ERROR: ".$e->getMessage()."\n";
		}
	}

	public function cli_parse_xdebug_trace($file = null, $jsonOptions = null)
	{
		if ($jsonOptions) {
			$options = json_decode($jsonOptions, true);
			if (!$options) {
				throw new Exception('Options must be in json format');
			}
		} else {
			$options = array();
		}

		$parser = new ParserXdebugTrace($options);
		$parser->parse($file);
	}

	
	////////////////////
	////// ACTION //////
	////////////////////
	

	////////////////////
	//////  AJAX  //////
	////////////////////
	

	////////////////////
	//////  MODEL  /////
	////////////////////
	
}
