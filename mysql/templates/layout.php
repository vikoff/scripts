<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
	<title><?= $this->_getHtmlTitle(); ?></title>
	<base href="<?= $this->_getHtmlBaseHref(); ?>" />

	<link rel="icon" href="favicon.ico" type="image/x-icon">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/bootstrap-theme.min.css" />

    <meta name="viewport" content="width=device-width, initial-scale=1">

	<script type="text/javascript" src="<?= WWW_ROOT; ?>js/jquery-1.11.1.min.js"></script>
	<script type="text/javascript" src="<?= WWW_ROOT; ?>js/bootstrap.min.js"></script>
	<script type="text/javascript" src="<?= WWW_ROOT; ?>js/debug.js"></script>
	<script type="text/javascript">
	
		function href(href){
			return 'index.php?r=' + href;
		}
		
		$(function(){
			
			// отлов ajax-ошибок
			$.ajaxSetup({
				error: function(xhr){
					trace(xhr.responseText);
					return true;
				}
			});
		});
	
	</script>
</head>
<body>

<div class="container-fluid">
	<?= $this->_getHtmlContent(); ?>
</div>

</body>
</html>
