<style>
.control-panel {
	width: 300px;
	margin: 0 auto;
	padding: 20px 10px;
	text-align: left;
}
.control-panel form {
	margin: 1em 0;
}
.db-list a{
	color: #333;
	text-decoration: none;
	font-size: 12px;
}
.db-list a:hover{
	text-decoration: underline;
}
</style>

<?php

/** @var DbAdapter[] $conns */
$conns = db::getAllConnections();

?>
<div class="control-panel">

	<h3>Сервера БД</h3>
	<?php if (count($conns) == 1) { ?>
		<p><?= current($conns)->getConnHost(); ?></p>
	<?php } else { ?>
		<?php
		$opts = array_map(function(DbAdapter $c){ return $c->getConnHost(); }, $conns);

		?>
		<form action="" method="get">
			<?= Html::select(array('name' => 'conn', 'onchange' => 'this.form.submit()'), $opts, $this->curConn); ?>
			<input type="submit" value="Выбрать"/>
		</form>
	<?php } ?>

	<h3>База данных</h3>
	<ul class="db-list">
		<?php foreach ($this->db->showDatabases() as $db) { ?>
			<li><a href="<?= href("sql-console?conn=$this->curConn&db=$db"); ?>"><?= $db; ?></a></li>
		<?php } ?>
	</ul>

</div>