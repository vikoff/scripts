<style>
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
	width: 98%;
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
</style>
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
<h3 style="text-align: center;">SQL-консоль <a class="small" href="<?= href(''); ?>">выбрать другую БД</a></h3>

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
				База данных</br />
				<select id="change-db" onchange="rebuildFormAction();">
					<? foreach ($this->dbs as $db) { ?>
						<option value="<?= $db; ?>" <?= $db == $this->curDb ? 'selected="selected"' : ''; ?>><?= $db; ?></option>
					<? } ?>
				</select>
			</label>

			<label title="Максимальная отображаемая длина строк. 0 - без ограничений.">
				Max длина строк
				<input type="text" id="strlen" onchange="rebuildFormAction();" value="<?= !empty($_GET['strlen']) ? $_GET['strlen'] : 0; ?>" size="4">
			</label>

			<label>
				<input id="explain" type="checkbox" onchange="rebuildFormAction();" <?= !empty($_GET['explain']) ? 'checked' : ''; ?>>
				показать EXPLAIN
			</label>

			<input style="padding: 15px;" type="submit" class="button" value="Выполнить запрос" />

			<div class="options">
				<a href="#" onclick="addToFavorites(); return false;">добавить в избранное</a><br />
				<a href="<?= href('history'); ?>" onclick="/*showHistory(); return false;*/">показать историю</a>
			</div>
		</td>
	</tr>
	</table>
</form>

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
$(function(){

	$('.set-title-btn').click(function(e){
		e.preventDefault();
		$(this).hide().next().show();
	})
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
			} else if (e.keyCode == 9) { // tab, shift + tab
				if (e.ctrlKey)
					return;
				e.preventDefault();
				if (e.shiftKey) {

				} else {
					insertAtCaret(this, "\t");
				}
			}
		});

	$(window)
		.keydown(function(e){
			if (e.keyCode == 13 && e.ctrlKey) { // ctrl + enter
				$('#sql-form').submit();
			}
		});

});

function rebuildFormAction() {

	var db = $('#change-db').val();

	var action = '?r=sql-console&db=' + db;

	var strlen = parseInt($('#strlen').val(), 10);
	if (strlen)
		action += '&strlen=' + strlen;

	var explain = $('#explain').is(':checked');
	if (explain)
		action += '&explain=1';

	$('#sql-form').attr('action', action);
}

</script>

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
