
<?php if ($this->files) { ?>

	<h2>Hihit parsings list</h2>
	<ul>
	<?php foreach ($this->files as $file) { ?>
		<li><a href="<?= href('hihit/view?file='.$file); ?>"><?= $file; ?></a></li>
	<?php } ?>
	</ul>
<?php } else { ?>

	<p>Nothing found</p>
<?php } ?>

<code>
"http://hits.informer.com/" + decodeURIComponent($('#flashcontent embed').attr('flashvars').match(/settings_file=(.+?)&/)[1]).substr(3)
</code>