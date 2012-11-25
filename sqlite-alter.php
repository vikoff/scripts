<?

function getVar(&$varname, $defaultVal = '', $type = ''){
	if (!isset($varname)) return $defaultVal;
	if (!empty($type)) {
		$copy = $varname;
		settype($copy, $type);
		return $copy;
	} else {
		return $varname;
	}
}
function convert($sql){
	$sql = str_replace(
		array('`', 'UNSIGNED', 'AUTO_INCREMENT', 'IF EXISTS'),
		array("'", '',         '',               ''),
		$sql
	);
	$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
	$sql = preg_replace('/NOT NULL\s+PRIMARY KEY/', 'PRIMARY KEY', $sql);
	$sql = preg_replace('/\)[^)]*\z/m', ');', $sql);
	return $sql;
}

$section = isset($_GET['section']) ? $_GET['section'] : 'add-column';
?>

<? if ($section == 'add-column') { ?>
	
	<form action="" method="post">
		<textarea name="create-table" style="width: 400px; height: 300px;"><?= getVar($_POST['create-table']); ?></textarea>
		<br />
		<input type="submit" name="add" value="Добавить" />
	</form>
	
	<?
	if (!empty($_POST)) {
		$sql = getVar($_POST['create-table']);
		$sql = convert($sql);
		echo '<pre>'.$sql.'</pre>';
		
		// 'BEGIN TRANSACTION;
		// CREATE TEMPORARY TABLE t1_backup(a,b);
		// INSERT INTO t1_backup SELECT a,b FROM t1;
		// DROP TABLE t1;
		// CREATE TABLE t1(a,b);
		// INSERT INTO t1 SELECT a,b FROM t1_backup;
		// DROP TABLE t1_backup;
		// COMMIT;';
	}
	?>

<? } ?>