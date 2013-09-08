<?php

abstract class Controller
{
	/** выполнение действия */
	public function action($methodIdentifier, $redirect)
	{
		$method = $this->getActionMethodName($methodIdentifier);
			
		if(!method_exists($this, $method)){
			$this->display_404("Method '$methodIdentifier' not found in controller.");
			exit;
		}
		
		if($this->$method($method, $redirect))
			if(!empty($redirect))
				redirect($redirect);
		
		return TRUE;
	}
	
	/** выполнение отображения */
	public function display($methodIdentifier, $params)
	{
		$method = $this->getDisplayMethodName($methodIdentifier);
			
		if(!method_exists($this, $method))
			return FALSE;

		call_user_func_array(array($this, $method), $params);
		return TRUE;
	}
	
	/** выполнение ajax */
	public function ajax($methodIdentifier, $params)
	{
		$method = $this->getAjaxMethodName($methodIdentifier);
			
		if(!method_exists($this, $method))
			return FALSE;

		call_user_func_array(array($this, $method), $params);
		return TRUE;
	}
	
	/** выполнение command line (cli) */
	public function cli($methodIdentifier, $params)
	{
		$method = $this->getCliMethodName($methodIdentifier);
			
		if(!method_exists($this, $method))
			return FALSE;

		call_user_func_array(array($this, $method), $params);
		return TRUE;
	}

	/** получить имя метода действия по идентификатору */
	public function getActionMethodName($method){
	
		// преобразует строку вида 'any-Method-name' в 'any_method_name'
		$method = 'action_'.strtolower(str_replace('-', '_', $method));
		return $method;
	}
	
	/** получить имя метода отображения по идентификатору */
	public  function getDisplayMethodName($method)
	{
		// преобразует строку вида 'any-Method-name' в 'any_method_name'
		$method = 'display_'.($method ? strtolower(str_replace('-', '_', $method)) : 'index');
		return $method;
	}
	
	/** получить имя ajax метода по идентификатору */
	public function getAjaxMethodName($method){
	
		// преобразует строку вида 'any-Method-name' в 'any_method_name'
		$method = 'ajax_'.strtolower(str_replace('-', '_', $method));
		return $method;
	}
	
	/** получить имя ajax метода по идентификатору */
	public function getCliMethodName($method){
	
		// преобразует строку вида 'any-Method-name' в 'any_method_name'
		$method = 'cli_'.strtolower(str_replace('-', '_', $method));
		return $method;
	}
	
	
	/////////////////////
	////// DISPLAY //////
	/////////////////////
	
	public function display_404($error = ''){
		
		if(AJAX_MODE){
			echo 'Страница не найдена ('.$error.')';
		}else{
			Layout::get()
				->setContent('<h1 style="text-align: center;">Страница не найдена</h1> <p>'.$error.'</p>')
				->render();
		}
		exit;
	}

	public function display_error($error, Exception $e = null)
	{
		if(AJAX_MODE){
			echo 'Ошибка выполнения: '.$error;
		}else{
			$content = '<h1>Ошибка выполнения</h1>
				<p>'.$error.'</p>'
				.(Config::get('show_exceptions') ? '<p>'.print_r($e, 1).'</p>' : '');
			Layout::get()
				->setContent($content)
				->render();
		}
		exit;
	}

}
