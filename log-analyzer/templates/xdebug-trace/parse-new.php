
<h1>Parse New Xdebug Trace</h1>

<ol class="breadcrumb">
	<li><a href="<?= href('/'); ?>">Home</a></li>
	<li><a href="<?= href('x-trace'); ?>">Xdebug Traces</a></li>
	<li class="active">Parse New Trace</li>
</ol>

<?= Messenger::get()->getAll(); ?>

<form action="" method="post" enctype="multipart/form-data" class="form-horizontal">
	<?= FORMCODE; ?>
	<input type="hidden" name="action" value="x-trace/parse-new" />

	<div class="form-group">
		<label for="f-comments" class="col-lg-2 control-label">Xdebug Trace File *</label>
		<div class="col-lg-5">
			<input type="file" class="form-control" id="f-file" name="file">
		</div>
	</div>

	<div class="form-group">
		<label for="f-application" class="col-lg-2 control-label">Project (app name)</label>
		<div class="col-lg-5">
			<?= Form::inputText(array('class' => 'form-control', 'id' => 'f-application',
									  'name' => 'application', 'value' => $this->application)); ?>
		</div>
	</div>

	<div class="form-group">
		<label for="f-request_url" class="col-lg-2 control-label">Request url</label>
		<div class="col-lg-5">
			<?= Form::inputText(array('class' => 'form-control', 'id' => 'f-request_url',
									  'name' => 'request_url', 'value' => $this->request_url)); ?>
		</div>
	</div>

	<div class="form-group">
		<label for="f-app_base_path" class="col-lg-2 control-label">App base path</label>
		<div class="col-lg-5">
			<?= Form::inputText(array('class' => 'form-control', 'id' => 'f-app_base_path',
									  'name' => 'app_base_path', 'value' => $this->app_base_path)); ?>
		</div>
	</div>

	<div class="form-group">
		<label for="f-comments" class="col-lg-2 control-label">Comments</label>
		<div class="col-lg-5">
			<?= Form::inputText(array('class' => 'form-control', 'id' => 'f-comments',
									  'name' => 'comments', 'value' => $this->comments)); ?>
		</div>
	</div>

	<div class="form-group">
		<div class="col-lg-offset-2 col-lg-5">
			<button type="submit" class="btn btn-default">Parse</button>
		</div>
	</div>

</form>