<?php

/**
 * Класс для работы с сообщениями для пользователей.
 * @using nothing
 */
class Messenger {
	
	/* используемый namespace */
	private $_ns = 'default';
	
	/* контейнер сообщений */
	private $_messages = array('error' => array(), 'info' => array(), 'success' => array());
	
	/** экземпляр Messenger */
	private static $_instance = null;
	
	/**
	 * точка входа в класс
	 * @param string $ns - namespace
	 * @return Messenger
	 */
	public static function get($ns = 'default'){
		
		if(is_null(self::$_instance))
			self::$_instance = new Messenger();
		
		self::$_instance->ns($ns);
		
		return self::$_instance;
	}
	
	/* конструктор */
	private function __construct(){
		
		if (!empty($_SESSION['_messengerData'])) {
			$this->_messages = $_SESSION['_messengerData'];
			$_SESSION['_messengerData'] = array();
		}
	}
	
	/** задать namespace */
	public function ns($ns){
		
		$this->_ns = $ns;
		return $this;
	}

	public function addSuccess($msg)
	{
		$this->_addMessage('success', $msg);
		return $this;
	}

	public function addInfo($msg)
	{
		$this->_addMessage('info', $msg);
		return $this;
	}

	public function addError($msg)
	{
		$this->_addMessage('error', $msg);
		return $this;
	}

	protected function _addMessage($type, $message)
	{
		if (!isset($_SESSION['_messengerData']))
			$_SESSION['_messengerData'] = array();

		$_SESSION['_messengerData'][$this->_ns][$type][] = $message;
	}

	/** получить все пользовательские сообщения */
	public function getAll()
	{
		$output = '';
		$nsClass = 'msgblock-ns-'.$this->_ns;
		$separator = '<br>';
		$messages = isset($this->_messages[$this->_ns]) ? $this->_messages[$this->_ns] : array();

		if (!empty($messages['success'])) {
			$output .= '<div class="alert alert-success '.$nsClass.'">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				'.implode($separator, $messages['success']).'
			</div>';
		}

		if (!empty($messages['error'])) {
			$output .= '<div class="alert alert-danger '.$nsClass.'">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				'.implode($separator, $messages['error']).'
			</div>';
		}

		if (!empty($messages['info'])) {
			$output .= '<div class="alert alert-info '.$nsClass.'">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				'.implode($separator, $messages['info']).'
			</div>';
		}

		return $output;
	}

}
