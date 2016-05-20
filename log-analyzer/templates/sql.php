
<h2>SQL-Log Sessions</h2>

<?php if ($this->sessions) { ?>

	<form action="" method="post">
		<?= FORMCODE; ?>
		<input type="hidden" name="action" value="sql/sess-multiact" />

		<table class="table table-bordered">
			<tr>
				<th></th>
				<th>Id</th>
				<th>Date First</th>
				<th>Date Last</th>
				<th>Total Queries</th>
				<th>Comment</th>
				<th>Created At</th>
				<th>Processed At</th>
				<th>Action</th>
			</tr>
			<?php foreach ($this->sessions as $sess) { ?>
				<?php $viewUrl = href('sql/view/'.$sess['id']); ?>
				<tr>
					<td><input type="checkbox" name="sess[]" value="<?= $sess['id']; ?>" /></td>
					<td><a href="<?= $viewUrl; ?>"><?= $sess['id']; ?></a></td>
					<td><?= $sess['date_first']; ?></td>
					<td><?= $sess['date_last']; ?></td>
					<td><?= $sess['total_queries']; ?></td>
					<td><?= $sess['comments']; ?></td>
					<td><?= $sess['created_at']; ?></td>
					<td><?= $sess['processed_at']; ?></td>
					<td><a href="<?= $viewUrl; ?>">View</a></td>
				</tr>
			<?php } ?>
		</table>

		<label>
		With selected:
		<select name="act">
			<option value=""></option>
			<option value="delete">Delete</option>
		</select>
		</label>
		&nbsp;
		<input type="submit" value="Submit"/>
	</form>

    <form action="" method="post" onsubmit="return confirm('Remove all data?')">
        <?= FORMCODE; ?>
        <input type="hidden" name="action" value="sql/remove-all" />
        <input type="submit" name="remove-all" value="Remove all"/>
    </form>

<?php } else { ?>

	<p>No saved sessions</p>

<?php } ?>