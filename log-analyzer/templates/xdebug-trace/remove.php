
<h1>Remove Xdebug Trace</h1>

<ol class="breadcrumb">
	<li><a href="<?= href('/'); ?>">Home</a></li>
	<li><a href="<?= href('x-trace'); ?>">Xdebug Traces</a></li>
	<li class="active">Remove Trace [<?= $this->sessData['application']; ?>]</li>
</ol>

<?= Messenger::get()->getAll(); ?>

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

<form action="" method="post">
	<?= FORMCODE; ?>
	<input type="hidden" name="action" value="x-trace/remove"/>
	<input type="hidden" name="redirect" value="<?= href('x-trace'); ?>" />
	<input type="hidden" name="id" value="<?= $this->sessData['id']; ?>" />
	<p>Delete this trace?</p>
	<p>
		<input type="submit" value="Delete" class="btn btn-primary" />
		<a class="btn btn-default" href="<?= href('x-trace'); ?>">Cancel</a>

	</p>
</form>