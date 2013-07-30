<style>
.control-panel {
	width: 300px;
	margin: auto;
	padding: 20px 10px;
	text-align: left;
}
.control-panel form {
	margin: 1em 0;
}
.gray{
	color: #999;
	font-size: 12px;
}
</style>

<div class="control-panel">

	<h3>База данных</h3>
	<ul>
		<?php foreach (db::get()->showDatabases() as $db) { ?>
			<li><a href="<?= href('sql-console?db='.$db); ?>"><?= $db; ?></a></li>
		<?php } ?>
	</ul>

</div>