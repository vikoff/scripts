<style type="text/css">
	.list li{
		border: solid 1px #CCC;
		background: #FAFAFA;
		padding: 5px 10px;
		margin: 1em 0;
	}
</style>
<h1>Saved Xdebug Traces</h1>
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
		<th>Created</th>
		<th>Options</th>
	</tr>
	<?php foreach ($this->list as $sess) { ?>
		<?php $url = href('xdebug-trace/'.$sess['id']); ?>
		<tr>
			<td><a href="<?= $url; ?>"><?= $sess['id']; ?></a></td>
			<td><a href="<?= $url; ?>"><?= $sess['application']; ?></a></td>
			<td><?= $sess['request_url']; ?></td>
			<td><?= $sess['total_memory_str']; ?></td>
			<td><?= $sess['total_time_str']; ?></td>
			<td><?= $sess['total_calls']; ?></td>
			<td><?= $sess['comments']; ?></td>
			<td><?= $sess['created_at']; ?></td>
			<td><a href="<?= $url; ?>">view</a></td>
		</tr>
	<?php } ?>
	</table>
<?php } else { ?>
	<p>No xdebug traces saved</p>
<?php } ?>