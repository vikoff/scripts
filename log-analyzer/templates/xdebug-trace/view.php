<?php
$textColor = '#777';
//$textColor = '#DDD';
$num = 10;

function generateLevelColors()
{
	$colors = array('44', '77', 'AA');
	$colorsHex = $colors;
	// foreach ($colors as $color) $colorsHex[] = dechex($color);

	$len = count($colors);
	$index = 1;

	for ($i = 1; $i <= 100; $i++) {
		$base = base_convert($i, 10, $len);
		$baseFixLen = sprintf('%03d', $base);
		if (strlen($baseFixLen) > 3)
			break;
		$color = '#'.strtr($baseFixLen, $colorsHex);
		$bg = $index % 2 ? '#F5F5F5' : '#FEFEFE';
		echo ".level-$index { border-left: solid 4px $color; border-right: solid 2px $color; background: $bg; }\n";
//		echo $i.' '.$baseFixLen.' <div class="block" style="background: '.$color.'">'.$color.'</div><br><br>';
		$index++;
	}

	return $index - 1;
}

?>
<style type="text/css">
	.outer-box{
		text-align: center;
	}
	#xdebug-trace-box{
		display: inline-block;
		text-align: left;
		padding-left: 10px;
		border-left: solid 2px #BBB;
		font-size: 10px;
	}
	#xdebug-trace-box a{
		/*color: #A0D5FF;*/
	}
	.call-item{
		margin: 1em 0;
	}
	.item-inner{
		background: #FAFAFA;
		border: solid 1px #DDD;
		/*display: inline-block;*/
		/*border-radius: 6px;*/
		padding-bottom: 2px;
	}
	.func-name{
		background: rgba(0,0,0,0.1);
		padding: 1px 10px;
		font-family: monospace;
		font-size: 12px;
		margin-bottom: 1px;
		/*color: #000;*/
	}
	.func-name a.func-details{
		color: #000;
		text-decoration: none;
	}
	.func-name a.func-details:hover{
		color: #004d96;
	}
	.func-place{
		padding: 0 10px;
	}
	.func-info{
		padding: 0 10px;
		color: <?= $textColor; ?>;
		line-height: 12px;
	}
	.level{
		/*background: rgba(0,0,255,0.1);*/
		padding: 0 1px;
	}
	.file{
		cursor: pointer;
	}
	.nested-num{
		color: <?= $textColor; ?>;
	}
	.nested-title{
		line-height: 12px;
		padding: 0 10px;
	}
	.nested-title .no{
		color: #A22626;
	}
	.nested-calls{
		padding: 0 20px;
	}

	.max-calls > .func-name{
		font-weight: bold;
	}
	.max-mem > .func-info > .mem{
		color: #9F4848;
	}
	.max-time > .func-info > .time{
		color: #8D7D2E;
	}

	#page-menu{
		display: none;
		position: fixed;
		top: 20px;
		right: 20px;
		padding: 5px 10px;
		background: #FFF;
		border: solid 1px #EEE;
	}

<?php $num = generateLevelColors(); ?>

</style>

<h1>View Xdebug Trace</h1>

<ol class="breadcrumb">
	<li><a href="<?= href('/'); ?>">Home</a></li>
	<li><a href="<?= href('x-trace'); ?>">Xdebug Traces</a></li>
	<li class="active">Trace [<?= $this->sessData['application']; ?>]</li>
</ol>

<?= Messenger::get()->getAll(); ?>

<div id="page-menu">

</div>

<table class="table table-bordered">
	<tr>
		<th>Id</th>
		<th>Application</th>
		<th>Request Url</th>
		<th>Memory</th>
		<th>Time</th>
		<th>Calls</th>
		<th>Comment</th>
		<th>Created</th>
	</tr>
		<tr>
			<td><?= $this->sessData['id']; ?></td>
			<td><?= $this->sessData['application']; ?></td>
			<td><?= $this->sessData['request_url']; ?></td>
			<td><?= $this->sessData['total_memory_str']; ?></td>
			<td><?= $this->sessData['total_time_str']; ?></td>
			<td><?= $this->sessData['total_calls']; ?></td>
			<td><?= $this->sessData['comments']; ?></td>
			<td><?= $this->sessData['created_at']; ?></td>
		</tr>
</table>

<div class="outer-box">
	<div id="xdebug-trace-box"></div>
</div>

<div class="modal fade" id="func-details-modal" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title">Function call details</h4>
			</div>
			<div class="modal-body"></div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script type="text/javascript">

function drawLevel(box, levelData)
{
	if (!levelData)
		return;

	box = $(box);

	for (var i = 0; i < levelData.length; i++)
		drawFunc(box, levelData[i]);
}

function drawFunc(box, callData)
{
	var file = callData['call_file'].replace(baseFilesPath, '');
	var level = callData['level'];
	var nestedCalls = parseInt(callData['num_nested_calls']) || 0;
	var levelClass = (level - 1) % numLevelColors + 1;
	var classes = [];
	if (callData['max_mem']) classes.push('max-mem');
	if (callData['max_time']) classes.push('max-time');
	if (callData['max_calls']) classes.push('max-calls');

	var funcName = callData['func_name'] + '(' + callData['args_str'] + ')';
	var funcUrl = href('x-trace/func-details/'+callData['id']);

	var html = ''
		+ '<div class="call-item" data-id="' + callData['id'] + '">'
		+     '<div class="item-inner level-' + levelClass + ' '+ classes.join(' ') +'">'
		+         '<div class="func-name"><a href="' + funcUrl + '" class="func-details">' + funcName + '</a></div>'
		+         '<div class="func-info">'
		+             '<span class="level" title="level ' + level + '">->' + level + '</span> '
		+             '<span class="file" onclick="selectText(this);">' + file + ':' + callData['call_line'] + '</span> '
		+             '<span class="sep">|</span> '
		+             '<span class="mem">mem: ' + callData['mem_diff_str'] + '</span> '
		+             '<span class="sep">|</span> '
		+             '<span class="time">time: ' + callData['time_diff'] + '</span> '
		+         '</div>'
//		+         '<div class="func-place">' + file + ':' + callData['call_line'] + '</div>'
//		+         '<div class="func-info">level: ' + level + ', nested calls: ' + nestedCalls + '</div>'
		+ (nestedCalls
		    ?
		          '<div class="nested-title">'
		+             '<a href="#" class="show-nested-btn">show nested calls</a> '
		+             '<span class="nested-num">(' + nestedCalls + ')</span> '
		+         '</div>'
		+         '<div class="nested-calls"></div>'
		    :     ''
//		+         '<div class="nested-title"><span class="no">no nested calls</span></div>'
		)
		+     '</div>'
		+ '</div>';

	box.append(html);
}

function selectText(elm) {
	if (document.selection) {
		var range = document.body.createTextRange();
		range.moveToElementText(elm);
		range.select();
	} else if (window.getSelection) {
		var range = document.createRange();
		range.selectNode(elm);
		window.getSelection().addRange(range);
	}
}

var sessIndex = <?= json_encode($this->sessId); ?>;
var firstLevelCalls = <?= json_encode($this->calls); ?>;
var baseFilesPath = <?= json_encode($this->basePath); ?>;
var numLevelColors = <?= $num; ?>;

$(function(){

	drawLevel('#xdebug-trace-box', firstLevelCalls);

	// show/hide nested functions
	$('body').on('click', '.show-nested-btn', function(e){
		e.preventDefault();
		var $this = $(this);
		var box = $(this).closest('.call-item');

		if ($this.data('showed')) {
			$this.text('show nested calls');
			$this.data('showed', false);
			box.find('.nested-calls').empty();
			return;
		}

		$this.data('showed', true);
		$this.text('hide nested calls');
		var id = box.data('id');
		$.get('?r=x-trace/get-children', {sess: sessIndex, id: id}, function(response){
			if (response.success) {
				drawLevel(box.find('.nested-calls'), response.data);
			} else {
				alert(response.error || response);
			}
//			var_dump(response, 'd=3');
		});
	});

	$('body').on('click', 'a.func-details', function(e){
		e.preventDefault();
		$.get(this.href, function(response){
			$('#func-details-modal').modal()
				.find('.modal-body').html(response);
		})
	});
});

</script>