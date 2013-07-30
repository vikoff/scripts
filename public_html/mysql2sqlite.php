<?
function convert($sql, $options){
	
	$sql = str_replace(
		array('`', 'UNSIGNED', 'AUTO_INCREMENT', 'IF EXISTS'),
		array("'", '',         '',               ''),
		$sql
	);
	
	$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
	$sql = preg_replace('/NOT NULL\s+PRIMARY KEY/', 'PRIMARY KEY', $sql);
	$sql = preg_replace('/\)\s*ENGINE[^;]*;/', ');', $sql);
	
	if (!empty($options['clear-drop']))
		$sql = preg_replace('/ IF EXISTS/i', '', $sql);
		
	if (!empty($options['clear-comments'])) {
		$sql = preg_replace('/\/\*(\s|.)*?\*\//m', '', $sql);
		$sql = preg_replace('/\s*--.*$/m', '', $sql);
	}
		
	return $sql;
}
header('content-type: text/html; charset=utf-8');
?>
<html>
<head>
	<title> mysql 2 sqlite </title>
	<style>
		body {
			font-family: tahoma, "sans-serif";
			min-height: 100%;
		}
		table{
			border-collapse: collapse;
		}
		td{
			padding: 5px;
		}
		.output{
			white-space: pre;
			padding: 1em;
			font-size: 12px;
			background-color: #F5F5F5;
			border: solid 1px #DDD;
			width: 100%;
			height:600px;
		}
	</style>
</head>
<body>

<table style="width: 100%; height 100%;" border>
<tr valign="top">
	<td style="width: 50%;">
		<form action="" method="post">
		<label>
			<input type="hidden" name="clear-drop" value="0" />
			<input type="checkbox" name="clear-drop" value="1"
				<?= !isset($_POST['clear-drop']) || !empty($_POST['clear-drop']) ? 'checked' : '' ?> />
			убрать DROP TABLE
		</label>
		|
		<label>
			<input type="hidden" name="clear-comments" value="0" />
			<input type="checkbox" name="clear-comments" value="1"
				<?= !isset($_POST['clear-comments']) || !empty($_POST['clear-comments']) ? 'checked' : '' ?> />
			убрать комментарии
		</label>
		<textarea name="sql" style="width: 100%; height: 550px;"><?= isset($_POST['sql']) ? htmlspecialchars($_POST['sql']) : ''; ?></textarea><br />
		<input type="submit" name="convert" value="Convert" />
		</form>
	</td>
	<td>
		<textarea class="output"><?= isset($_POST['sql']) ? convert($_POST['sql'], $_POST) : ''; ?></textarea>
	</td>
	
</tr>
</table>
	
</body>
</html>