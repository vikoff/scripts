<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?= $this->_getHtmlTitle(); ?></title>
	<base href="<?= $this->_getHtmlBaseHref(); ?>" />
	<link rel="icon" href="favicon.ico" type="image/x-icon">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<script type="text/javascript" src="<?= WWW_ROOT; ?>js/jquery-1.11.1.min.js"></script>
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
	<style>
	body {
		font-family: verdana, tahoma, sans-serif;
	}
	</style>
</head>
<body>

	<?= $this->_getHtmlContent(); ?>
	
</body>
</html>
