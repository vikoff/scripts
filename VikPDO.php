<?php

class VikPDO {

	private static $_instance = null;

	private $_pdo = null;


	public static function create($dsn, $user = '', $pass = '')
	{
		self::$_instance = new self($dsn, $user, $pass);
		return self::$_instance;
	}

	public static function get()
	{
		return self::$_instance;
	}

	private function __construct($dsn, $user = '', $pass = '')
	{
		$this->_pdo = new PDO($dsn, $user, $pass);
	}


	/**
	 * @param $sql
	 * @param array $bind
	 * @return PDOStatement
	 */
	public function query($sql, $bind = array())
	{
		/** @var $stmt PDOStatement */
		$stmt = $this->_pdo->prepare($sql);
		if (!$stmt) {
			throw new Exception('database error');
		}
		$success = $stmt->execute($bind);
		if (!$success) {
			$errorInfo = $stmt->errorInfo();
			$erroStr = $errorInfo[2]."<br /><br />$sql";
			throw new Exception($erroStr);
		}

		return $stmt;
	}

	/**
	 * @param $table
	 * @param $fieldsValues
	 * @param $where
	 * @param array|mixed $whereBind
	 * @return int количество затронутых строк
	 */
	public function update($table, $fieldsValues, $where, $whereBind = array())
	{
		$update_arr = array();
		$bind_arr = array();
		foreach($fieldsValues as $field => $value) {
			if ($value instanceof VikPDO_Stmt) {
				$update_arr[] = '`'.$field.'` = '.$value;
			} else {
				$update_arr[] = '`'.$field.'` = ?';
				$bind_arr[] = $value;
			}
		}

		if ($whereBind)
			$bind_arr = array_merge($bind_arr, (array)$whereBind);

		$sql = 'UPDATE '.$table.' SET '.implode(', ',$update_arr).' WHERE '.$where;
		$result = $this->query($sql, $bind_arr);

		return $result->rowCount();
	}

	/**
	 * @param $table
	 * @param $fieldsValues
	 * @return int количество затронутых строк
	 */
	public function insert($table, $fieldsValues)
	{
		$fields_arr = array();
		$bind_arr = array();
		foreach($fieldsValues as $field => $value) {
			if ($value instanceof VikPDO_Stmt) {
				$fields_arr[] = '`'.$field.'` = '.$value;
			} else {
				$fields_arr[] = '`'.$field.'` = ?';
				$bind_arr[] = $value;
			}
		}

		$sql = 'INSERT INTO '.$table.' SET '.implode(', ',$fields_arr);
		$result = $this->query($sql, $bind_arr);

		return $result->rowCount();
	}

	public function fetchOne ($sql, $bind = array())
	{
		return $this->query($sql, $bind)->fetchColumn();
	}

	public function fetchRow ($sql, $bind = array())
	{
		return $this->query($sql, $bind)->fetch(PDO::FETCH_ASSOC);
	}

	public function fetchCol ($sql, $bind = array())
	{
		return $this->query($sql, $bind)->fetchAll(PDO::FETCH_COLUMN, 0);
	}
	
	public function fetchPairs($sql, $bind = array())
	{
		$rs = $this->query($sql, $bind);
		for ($data = array(); $row = $rs->fetch(PDO::FETCH_NUM); $data[ $row[0] ] = $row[1]);
		return $data;
	}

	public function fetchAll($sql, $bind = array())
	{
		return $this->query($sql, $bind)->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function fetchAssoc($sql, $index, $bind = array())
	{
		$rs = $this->query($sql, $bind);
		for ($data = array(); $row = $rs->fetch(PDO::FETCH_ASSOC); $data[ $row[$index] ] = $row);
		return $data;
	}

}

class VikPDO_Stmt {
	
	private $_statement = '';
	
	public static function create($statement) {
		
		return new self($statement);
	}
	
	public function __construct($statement) {
		
		$this->_statement = $statement;
	}
	
	public function __toString() {
		
		return $this->_statement;
	}
}
