<?php
function prepareCellValue($val) {

	if ($val === NULL) $val = '<NULL>';
	elseif ($val === TRUE) $val = '<TRUE>';
	elseif ($val === FALSE) $val = '<FALSE>';

	$val = htmlspecialchars($val);

	if (!empty($_GET['strlen']) && mb_strlen($val, 'utf-8') > (int)$_GET['strlen']) {
		$val = substr($val, 0, (int)$_GET['strlen']).'...';
	}

	return $val;
}
?>

<style type="text/css">
table.grid { 
	text-align: center;
}
table { 
	border-collapse: collapse;
	width: inherit;
}
table.grid th { 
	background-color: #C5CFE7;
	border: 1px solid #FFF;
	font-size: 12px;
	padding: 5px;
}
table.grid td { 
	border: 1px solid #EEEEEE;
	font-size: 11px;
	padding: 5px;
	text-align: left;
	white-space: pre-wrap;
}
table.grid tr:nth-child(2n) td{
	background: #EEE;
}
.set-title-btn{
	font-size: 11px;
}
.page-title{
	width: 98%;
	font-family: monospace;
	font-size: 14px;
	font-weight: bold;
	padding: 2px 5px;
	background-color: #DFE4F7;
}
#sql-input {
	/*width: 98%;*/
	width: 500px;
	height: 250px;
	font-size: 14px;
	font-family: monospace;
	padding: 5px;
	-o-tab-size: 4;
	-moz-tab-size: 4;
}
.sql-error{
	white-space: pre-wrap;
	padding: 10px;
	margin: 5px 0px;
	font-size: 13px;
	border: solid 2px #cc0000;
	background-color: #ffeeee;
}
.sql-params{
	width: 160px; font-size: 12px; text-align: left;
}
.sql-params label{
	display: block;
	margin: 5px;
}
.options{
	margin-top: 15px;
	font-size: 11px;
}
.small{
	font-size: 11px;
	font-weight: normal;
}
input[type="radio"], input[type="checkbox"]{
	vertical-align: sub;
}
.CodeMirror {
	border: 1px solid black;
	font-size: 13px;
}
</style>

<link rel="stylesheet" href="<?= WWW_ROOT; ?>css/codemirror.css"/>
<link rel="stylesheet" href="<?= WWW_ROOT; ?>css/codemirror/neo.css"/>
<script type="text/javascript" src="<?= WWW_ROOT; ?>js/codemirror-compressed.js"></script>
<script type="text/javascript">

	var extraFormData = {};

</script>
<h3 style="text-align: center;">
	SQL-консоль

	<span class="small">
		<span>| Сервер БД:</span>
		<?php if (count($this->conns) == 1) { ?>
			<input type="hidden" name="conn" value="<?= key($this->conns); ?>"/>
			<?= current($this->conns); ?>
		<?php } else { ?>
			<?= Html::select(array('name' => 'conn', 'onchange' => 'changeConn()'), $this->conns, $this->curConn); ?>
		<?php } ?>

		| <a href="<?= $_SERVER['REQUEST_URI']; ?>" target="_blank">новая вкладка</a>
		| <a href="<?= href(''); ?>">назад</a>
	</span>
</h3>

<form id="sql-form" action="<?= $_SERVER['REQUEST_URI']; ?>" method="post">
	<table style="width: 100%; text-align: center;">
	<tr>
		<td style="text-align: left; padding-left: 5px;">
			<div style="text-align: left; margin-bottom: 3px;">
				<? if (!$this->title) { ?> <a class="set-title-btn" href="#" onclick="return false">установить заголовок</a> <? } ?>
				<input class="page-title" type="text" name="title" <?=!$this->title?'style="display: none;"':'';?> value="<?= htmlspecialchars($this->title); ?>" /><br />
			</div>
			<textarea id="sql-input" name="query" spellcheck="false"><?=$this->query;?></textarea>
		</td>
		<td class="sql-params">

			<label>
				База данных<br />
				<select id="change-db" onchange="rebuildFormAction();">
					<? foreach ($this->dbs as $db) { ?>
						<option value="<?= $db; ?>" <?= $db == $this->curDb ? 'selected="selected"' : ''; ?>><?= $db; ?></option>
					<? } ?>
				</select>
			</label>

			<label>
				Лимит строк
				<input type="text" id="limit" onchange="rebuildFormAction();" value="<?= $this->limit; ?>" size="4">
			</label>

			<label title="Максимальная отображаемая длина текста. 0 - без ограничений.">
				Maкс длина текста
				<input type="text" id="strlen" onchange="rebuildFormAction();" value="<?= getVar($_GET['strlen'], 0); ?>" size="4">
			</label>

			<div class="mode" style="width: 200px;">
				<label>
					<input type="radio" name="mode" value="grid" onchange="rebuildFormAction();" <?= $this->mode == 'grid' ? 'checked' : ''; ?> />
					показать&nbsp;результаты
				</label>
				<label>
					<input type="radio" name="mode" value="explain" onchange="rebuildFormAction();" <?= $this->mode == 'explain' ? 'checked' : ''; ?> />
					показать EXPLAIN
				</label>
				<label title="Для графика нужен результат с одной или двумя колонками. Первая - лейбл X (не обязательно), вторая - значение Y.">
					<input type="radio" name="mode" value="graph" onchange="rebuildFormAction();" <?= $this->mode == 'graph' ? 'checked' : ''; ?> />
					построить график
				</label>
			</div>
			<input style="padding: 15px;" type="submit" class="button" value="Выполнить запрос" />

			<div class="options">
				<a href="#" onclick="addToFavorites(); return false;">добавить в избранное</a><br />
				<a href="<?= href('history'); ?>" onclick="/*showHistory(); return false;*/">показать историю</a>
			</div>
		</td>
	</tr>
	</table>

</form>

<?php if ($this->mode == 'graph') { ?>
	<?php if (!empty($this->data[0]['result'])) { ?>
		<?php if (count($this->data[0]['result'][0]) > 1) { ?>
<div id="chart"></div>
<div style="text-align: center; font-size: 11px;">
	<?php
	$checboxes = array(
		'c-step'   => array('label' => 'use line step', 'default' => 0),
		'c-marker' => array('label' => 'show markers', 'default' => 1),
		'c-f0'     => array('label' => 'start from 0', 'default' => 1),
		'c-cross'  => array('label' => 'crosshair', 'default' => 0),
	);
	foreach ($checboxes as $name => $data) {
		if (isset($_GET[$name])) {
			echo '<script type="text/javascript">extraFormData["'.$name.'"] = '.(int)$_GET[$name].';</script>';
		}
		$checked = (isset($_GET[$name]) ? (int)$_GET[$name] : $data['default']) ? 'checked="checked"' : '';
		echo '<label><input type="checkbox" name="'.$name.'" value="1" '.$checked.' /> '.$data['label'].'</label> ';
	}
	?>
</div>
<script type="text/javascript" src="<?= WWW_ROOT; ?>js/highcharts/highcharts.js"></script>
<script type="text/javascript" src="<?= WWW_ROOT; ?>js/highcharts/modules/data.js"></script>
<script type="text/javascript" src="<?= WWW_ROOT; ?>js/highcharts/modules/exporting.js"></script>
<script type="text/javascript">

	var Chart = {
		lastOpts: null,
		init: function()
		{
			$('[name="c-step"]').change(function(){ Chart.setLineStep(this); return false; });
			$('[name="c-marker"]').change(function(){ Chart.setMarkers(this); return false; });
			$('[name="c-f0"]').change(function(){ Chart.setMin(this); return false; });
			$('[name="c-cross"]').change(function(){ Chart.setCrosshair(this); return false; });
			return this;
		},
		draw: function(data)
		{
			if (!data)
				return;

			// не отображать колонки, имя которых начинается со знака '-'
			var keys = [];
			for (i in data[0]) {
				if (i.substr(0, 1) != '-')
					keys.push(i);
			}

			var categoryKey = keys.shift();
			var categories = [];
			var allSeries = [];
			var i, j, series;
			for (j = 0; j < keys.length; j++) {
				series = [];
				for (i = 0; i < data.length; i++) {
					series.push(parseFloat(data[i][keys[j]]));
					if (j == 0)
						categories.push(data[i][categoryKey]);
				}
				allSeries.push({data: series, name: keys[j]})
			}

			var step = $('[name="c-step"]').is(':checked') ? 'left' : null;
			var marker = $('[name="c-marker"]').is(':checked') ? 1 : 0;
			var min = $('[name="c-f0"]').is(':checked') ? 0 : undefined;
			var crosshair = $('[name="c-cross"]').is(':checked') ? {color: '#D25F4B'} : false;

			this.lastOpts = {
				chart: {animation: false, height: 500},
				plotOptions: { series: { allowPointSelect: true, step: step, marker: { enabled: marker }, stickyTracking: false } },
				xAxis: { categories: categories, title: { text: categoryKey }, offset: 0 },
				yAxis: { title: { text: 'values' }, min: min },
				tooltip: { shared: true, crosshairs: [true, crosshair] },
				series: allSeries
			};
			$('#chart').highcharts(this.lastOpts);
		},

		setLineStep: function(elm)
		{
			var stepVal = elm.checked ? 'left' : null;
			this._updateFormData(elm);
			this._updateSeries({step: stepVal})
		},

		setMarkers: function(elm)
		{
			var enabled = elm.checked ? 1 : 0;
			this._updateFormData(elm);
			this._updateSeries({marker: {enabled: enabled}});
		},

		setMin: function(elm)
		{
			this._updateFormData(elm);
			$('#chart').highcharts().yAxis[0].update({min: elm.checked ? 0 : undefined});
		},

		setCrosshair: function(elm)
		{
			var val = elm.checked ? {color: '#D25F4B'} : false;
			this._updateFormData(elm);
			this.lastOpts.tooltip.crosshairs = [true, val];
			this.redraw();
		},

		_updateSeries: function(options)
		{
			var chart = $('#chart').highcharts();
			for (var i = 0; i < chart.series.length; i++) {
				chart.series[i].update(options);
			}
		},
		redraw: function()
		{
			$('#chart').highcharts(this.lastOpts);
		},
		_updateFormData: function(elm)
		{
			extraFormData[ elm.name ] = elm.checked ? 1 : 0;
			rebuildFormAction();
		}
	};

	$(function(){
		Chart.init().draw(<?= json_encode($this->data[0]['result']); ?>);
	});
</script>
		<?php } else { ?>
			<p>
				Для графика нужен результат с двумя или более колонками. Первая - лейбл X (не обязательно),
				каждая остальная колонка - отдельный график. Если имя колонки начинается со знака '-',
				то по этой колонке график строиться не будет.
			</p>
		<?php } ?>

	<?php } else { ?>
		<p>Нет данных для построения графика</p>
	<?php } ?>

<?php } ?>

<? if(isset($this->data) && is_array($this->data)): ?>
	<? foreach($this->data as $index => $result): ?>

		<div class="paragraph">
			
			<? if (!empty($result['error'])) { ?>
				Ошибка выполнения запроса:
				<div class="sql-error"><?= trim($result['error']); ?></div>
			<? } else { ?>
			
				<div style="border: solid 1px #EED; background: #FFFFF6; margin: 15px 0 4px;padding: 2px 6px;">
					<div style="font-size: 11px; color: #777;">Запрос #<?= $index; ?> (<?= round($result['time'], 4); ?> сек.) <?= $result['numrows']; ?> строк</div>
					<?php if (count($this->data) > 1) { ?>
						<div style="white-space: pre; font-family: monospace; font-size: 13px;" ><?= $result['sql']; ?></div>
					<?php } ?>
				</div>
				
				<? if ($result['numrows']) { ?>
					<table class="grid" style="margin: 0px;">
					<thead class="thead-floatblock">
						<tr>
						<? foreach($result['result'][0] as $field => $val)
							echo '<th>'.$field.'</th>'; ?>
						</tr>
					</thead>
					<tbody>
					
					<? foreach($result['result'] as $row): ?>
						<tr>
						<? foreach($row as $val): ?>
							<td><?= prepareCellValue($val); ?></td>
						<? endforeach; ?>
						</tr>
					<? endforeach; ?>
					</tbody>
					</table>

				<? } else { ?>
					
					Запрос #<?= $index; ?> вернул пустой результат.
					
				<? } ?>

			<? } ?>
			
		</div>

	<? endforeach; ?>
<? endif; ?>


<script type="text/javascript">

	var IS_POST = <?= !empty($_POST) ? 'true' : 'false'; ?>;

	function addToFavorites() {
		var val = $.trim($('#sql-input').val());
		if (!val) {
			return false;
		}
		$.post(href('add-sql-to-favorites'), {val: val}, function(response) {

		});
	}
	function showHistory() {

	}
	function insertAtCaret(element, text) {
		if (document.selection) {
			element.focus();
			var sel = document.selection.createRange();
			sel.text = text;
			element.focus();
		} else if (element.selectionStart || element.selectionStart === 0) {
			var startPos = element.selectionStart;
			var endPos = element.selectionEnd;
			var scrollTop = element.scrollTop;
			element.value = element.value.substring(0, startPos) + text + element.value.substring(endPos, element.value.length);
			element.focus();
			element.selectionStart = startPos + text.length;
			element.selectionEnd = startPos + text.length;
			element.scrollTop = scrollTop;
		} else {
			element.value += text;
			element.focus();
		}
	}

	function changeConn()
	{
		var conn = $('[name="conn"]').val();
		var dbSelect = $('#change-db');
		dbSelect.empty().hide();
		$.get('?r=get-databases&conn=' + conn, function(response){
			for (var i = 0; i < response.length; i++) {
				dbSelect.append('<option value="' + response[i] + '">' + response[i] + '</option>');
			}
			dbSelect.show();
		});
	}

	function rebuildFormAction()
	{
		var conn = $('[name="conn"]').val();
		var db = $('#change-db').val();
		var mode = $('[name="mode"]:checked').val() || 'grid';

		var action = '?r=sql-console&conn=' + conn + '&db=' + db + '&mode=' + mode;

		var strlen = parseInt($('#strlen').val(), 10);
		if (strlen)
			action += '&strlen=' + strlen;

		var limit = $('#limit').val();
		if (limit != 100)
			action += '&limit=' + limit;

		for (var i in extraFormData) {
			if (extraFormData.hasOwnProperty(i)) {
				action += '&' + i + '=' + encodeURIComponent(extraFormData[i]);
			}
		}

		$('#sql-form').attr('action', action);
	}

	$(function(){

		$('.set-title-btn').click(function(e){
			e.preventDefault();
			$(this).hide().next().show();
		});

		CodeMirror.fromTextArea(document.getElementById('sql-input'), {
			mode: 'text/x-mysql',
			lineNumbers: 1,
			autofocus: 1,
			dragDrop: 0,
			theme: 'neo'
		});
		$('#sql-input')
			.focus()
			.keydown(function(e){
				if(e.keyCode == 116){ // F5
					if (IS_POST) {
						return true;
					}
					if($(this).val().length && confirm('Выполнить запрос?')){
						$(this.form).submit();
					}else{
						location.href = location.href;
					}
					return false;
				}
			});

		$(window)
			.keydown(function(e){
				if (e.keyCode == 13 && e.ctrlKey) { // ctrl + enter
					$('#sql-form').submit();
				}
			});

	});

</script>
