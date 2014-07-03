<?

class Html {

	public static function openTag($attrs){

		$method = isset($attrs['method']) ? strtolower($attrs['method']) : 'get';
		$action = isset($attrs['action']) ? strtolower($attrs['action']) : '';
		$r = '';
		if ($method == 'get' && !empty($action)) {
			$r = "\n".'<input type="hidden" name="r" value="'.$action.'" />';
			$action = '';
		}
		$formcode = $method == 'post' ? "\n".FORMCODE : '';
		unset($attrs['method']);
		unset($attrs['action']);

		$attrsStr = '';
		foreach ($attrs as $k => $v)
			$attrsStr .= ' '.$k.'="'.$v.'"';

		return '<form action="'.href($action).'" method="'.$method.'"'.$attrsStr.'>'.$r.$formcode;
	}

	public static function closeTag(){
		return '</form>';
	}

	/**
	 * сгегерировать html input
	 * @param array $attrs - все параметры инпута вида 'параметр' => 'значение'
	 * @return string html input
	 */
	public static function input($attrs){
		
		if(!empty($attrs['value']))
			$attrs['value'] = htmlspecialchars($attrs['value']);
			
		$preparedAttrs = array();
		foreach($attrs as $k => &$v)
			$preparedAttrs[] = $k.'="'.$v.'"';
		
		return '<input '.implode(' ', $preparedAttrs).' />';
	}
	
	/**
	 * сгегерировать html input type="text"
	 * @param array $attrs - все параметры инпута вида 'параметр' => 'значение'
	 * @return string html input type=text
	 */
	public static function inputText($attrs){
		
		if(!empty($attrs['value']))
			$attrs['value'] = htmlspecialchars($attrs['value']);
			
		$preparedAttrs = array();
		foreach($attrs as $k => &$v)
			$preparedAttrs[] = $k.'="'.$v.'"';
		
		return '<input type="text" '.implode(' ', $preparedAttrs).' />';
	}
	
	/**
	 * сгегерировать html input type="checkbox"
	 * @param array $attrs - все параметры чекбокса вида 'параметр' => 'значение'
	 *                       ВАЖНО: параметр 'checked' нужно передавать в виде bool
	 * @return string html input type=checkbox
	 */
	public static function checkbox($attrs){
		
		if(!empty($attrs['checked']))
			$attrs['checked'] = 'checked';
		else
			unset($attrs['checked']);
			
		$preparedAttrs = array();
		foreach($attrs as $k => &$v)
			$preparedAttrs[] = $k.'="'.$v.'"';
		
		return '<input type="checkbox" '.implode(' ', $preparedAttrs).' />';
	}
	
	/**
	 * сгегерировать html input type="radio"
	 * @param array $attrs - все параметры радио-кнопки вида 'параметр' => 'значение'
	 *                       ВАЖНО: параметр 'checked' нужно передавать в виде bool
	 * @return string html input type=radio
	 */
	public static function radio($attrs){
		
		if(!empty($attrs['checked']))
			$attrs['checked'] = 'checked';
		else
			unset($attrs['checked']);
			
		$preparedAttrs = array();
		foreach($attrs as $k => &$v)
			$preparedAttrs[] = $k.'="'.$v.'"';
		
		return '<input type="radio" '.implode(' ', $preparedAttrs).' />';
	}
	
	/**
	 * сгенерировать html select
	 * @param array|false $selectAttrs - атрибуты тега selelect. Если FALSE, тогда генерируются только опции
	 * @param array $optionsArr - ассоциативный массив, $value => $title
	 *              или массив-список $title, $title
	 * @param string|null $active - выбранный элемент списка
	 * @param array $params - списко дополнительных параметров
	 *                     'keyEqVal' => bool - использовать value, такое же как и title
	 * @return string html select
	 */
	public static function select($selectAttrs, $optionsArr, $active = null, $params = array()){
		
		$options = '';
		$isArr = is_array($active);
		
		foreach($optionsArr as $k => $v){
			$key = !empty($params['keyEqVal']) ? $v : $k;
			$sel = ($isArr && in_array($key, $active)) || (!$isArr && $key == $active) ? ' selected="selected"' : '';
			$options .= '<option value="'.$key.'"'.$sel.'>'.$v.'</option>';
		}
		
		if(is_array($selectAttrs)){
			$select = '<select';
			foreach($selectAttrs as $k => $v)
				$select .= ' '.$k.'="'.$v.'"';
			$select .= '>'.$options.'</select>';
			
			return $select;
		}
		else{
			return $options;
		}
	}
	
	/**
	 * сгегерировать html textarea
	 * @param array $attrs - все параметры инпута вида 'параметр' => 'значение', включая 'value'
	 * @return string html textarea
	 */
	public static function textarea($attrs){
		
		$value = '';
		if(isset($attrs['value'])){
			$value = htmlspecialchars($attrs['value']);
			unset($attrs['value']);
		}
			
		$preparedAttrs = array();
		foreach($attrs as $k => &$v)
			$preparedAttrs[] = $k.'="'.$v.'"';
		
		return '<textarea '.implode(' ', $preparedAttrs).'>'.$value.'</textarea>';
	}
	
}