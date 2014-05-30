<?php
$menuItems = array(
	'main' => array('title' => 'Main', 'url' => href('sql/view/'.$this->session['id'])),
	'group' => array('title' => 'Grouping', 'url' => href('sql/view/'.$this->session['id'].'/group')),
	'can-group' => array('title' => 'Cannonical Grouping', 'url' => href('sql/view/'.$this->session['id'].'/can-group')),
);
$menuHtml = array();
foreach ($menuItems as $key => $data) {
	$isActive = $this->page == $key;
	$menuHtml[] = '<a href="'.$data['url'].'" '.($isActive ? 'class="active"' : '').'>'.$data['title'].'</a>';
}

?>
<style type="text/css">
nav{
	margin: 1em 0;
}
a.active{
	font-weight: bold;
	border-bottom: solid 2px blue;
}
td.sql{
	white-space: pre-wrap;
	font-family: monospace;
	word-break: break-all;
}
table tbody td.date{
	width: 100px;
	padding: 2px 5px;
}
</style>
<h1>View Sql-Log Details</h1>

<nav>
	<?= implode(' | ', $menuHtml); ?>
</nav>

<?php if ($this->page == 'main') { ?>

<?php } elseif ($this->page == 'main') { ?>
<?php } elseif ($this->page == 'group' || $this->page == 'can-group') { ?>

	<?php if ($this->data) { ?>

		<table class="table table-bordered">
			<tr>
				<th></th>
				<th>Count</th>
				<th>SQL</th>
				<th>First Date</th>
				<th>Last Date</th>
			</tr>
			<?php foreach ($this->data as $index => $row) { ?>
				<tr>
					<td><?= $index + 1; ?></td>
					<td><?= $row['cnt']; ?></td>
					<td class="sql"><?= $row['sql']; ?></td>
					<td class="date"><?= $row['min_date']; ?></td>
					<td class="date"><?= $row['max_date']; ?></td>
				</tr>
			<?php } ?>
		</table>

	<?php } else { ?>
		<p>No Data</p>
	<?php } ?>

<?php } ?>
