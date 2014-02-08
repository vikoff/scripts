<style type="text/css">
	.list li{
		border: solid 1px #CCC;
		background: #FAFAFA;
		padding: 5px 10px;
		margin: 1em 0;
	}
</style>

<h1>Saved Xdebug Traces</h1>

<ol class="breadcrumb">
	<li class="pull-right">
		<a href="<?= href('x-trace/parse-new'); ?>" class="btn btn-default btn-xs">Parse new trace</a>
		<a href="<?= href('x-trace/help'); ?>" class="btn btn-default btn-xs">Help</a>
	</li>
	<li><a href="<?= href('/'); ?>">Home</a></li>
	<li>Xdebug Traces</li>
</ol>

<?= Messenger::get()->getAll(); ?>
<?php if ($this->list) { ?>
	<table class="table table-bordered">
	<tr>
		<th>Id</th>
		<th>Application</th>
		<th>Request Url</th>
		<th>Memory</th>
		<th>Time</th>
		<th>Calls</th>
		<th>Comment</th>
		<th>Status</th>
		<th>Created</th>
		<th>Options</th>
	</tr>
	<?php foreach ($this->list as $sess) { ?>
		<?php
		$urlView = href('x-trace/view/'.$sess['id']);
		$urlEdit = href('x-trace/edit/'.$sess['id']);
		$urlDelete = href('x-trace/remove/'.$sess['id']);
		?>
		<tr>
			<td><a href="<?= $urlView; ?>"><?= $sess['id']; ?></a></td>
			<td><a href="<?= $urlView; ?>"><?= $sess['application']; ?></a></td>
			<td><?= $sess['request_url']; ?></td>
			<td><?= $sess['total_memory_str']; ?></td>
			<td><?= $sess['total_time_str']; ?></td>
			<td><?= $sess['total_calls']; ?></td>
			<td><?= $sess['comments']; ?></td>
			<td><?= $sess['process_percent'] == '100' ? '<span class="green">ready</span>' : $sess['process_percent'].'%'; ?></td>
			<td><?= $sess['created_at']; ?></td>
			<td>
				<div class="btn-group">
					<a href="<?= $urlView; ?>" class="btn btn-default btn-xs" title="view"><span class="glyphicon glyphicon-search"></span></a>
					<a href="<?= $urlEdit; ?>" class="btn btn-default btn-xs" title="edit"><span class="glyphicon glyphicon-edit"></span></a>
					<a href="<?= $urlDelete; ?>" class="btn btn-default btn-xs" title="remove"><span class="glyphicon glyphicon-remove"></span></a>
				</div>
			</td>
		</tr>
	<?php } ?>
	</table>
<?php } else { ?>
	<p>No xdebug traces saved</p>
<?php } ?>