<?php
if (!empty($_REQUEST['ajax'])) {
	if ($_REQUEST['ajax'] == 'save') {
		$data = array();
		foreach ($_POST['data'] as $item) {
			$data[] = array('val' => $item['val'], 'isConst' => (int)$item['isConst']);
		}
		file_put_contents('data.txt', json_encode($data));
		echo 'ok';
	} elseif ($_REQUEST['ajax'] == 'clear') {
		file_put_contents('data.txt', '');
		echo 'ok';
	}
	exit;
}
?>
<!DOCTYPE HTML>
<html>
<head>
	<meta charset="utf-8">
	<title>Sudoku</title>
	<script src="jquery.min.js"></script>
	<link rel="stylesheet" href="style.css"/>
	<script type="text/javascript" src="scripts.js"></script>
	<script type="text/javascript">

	var sudoku;

	$(function(){

		sudoku = new Sudoku('.board');
		sudoku.load();

		$('#btn-construct-mode').change(function(){ sudoku.setConstructMode(this.checked); });
		$('#toggle-candidates').click(function(){ sudoku.toggleCandidates(); });
		$('#btn-exclude').click(function(){ sudoku.enableExcludeMode(); });

		$('#btn-search-unique').click(function(){ sudoku.searchUnique(); });

		$('#btn-save').click(function(){ sudoku.save(); });
		$('#btn-load').click(function(){ sudoku.load(); });
		$('#btn-clear').click(function(){
			if (confirm('Удалить сохраненные данные?')) {
				$.get('index.php?ajax=clear', function(response){
					alert(response);
				});
			}
		});
	});

	</script>
</head>
<body>

<div class="content">
	<div class="board-box">
		<table class="board"></table>
		<div id="controls">
			<label><input type="checkbox" id="btn-construct-mode" /> construct mode</label>
			<button id="toggle-candidates">Candidates</button>
			<button id="btn-exclude">Exclude</button>
			<p>
				<button id="btn-search-unique">Search unique</button>
			</p>
			<p>
				<button id="btn-save">Save</button>
				<button id="btn-load">Load</button>
				<button id="btn-clear">Clear saved</button>
			</p>
		</div>
	</div>

	<div class="log-box">
		<div id="log">
		</div>
		<button onclick="$('#log').empty();">Clear</button>
	</div>
</div>
</body>
</html>