<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?= $this->_getHtmlTitle(); ?></title>

	<link rel="icon" href="favicon.ico" type=""/>
	<link rel="stylesheet" href="css/bootstrap.min.css"/>
	<link rel="stylesheet" href="css/bootstrap-theme.min.css"/>
	<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css"/>
	<link rel="stylesheet" href="css/styles.css"/>

	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript">
	
		function href(href){
			return 'index.php?r=' + href;
		}
		
		$(function(){
			
			// отлов ajax-ошибок
			$.ajaxSetup({
				error: function(xhr){
					console.log(xhr.responseText);
					return true;
				}
			});
		});
	
	</script>
</head>
<body>
	<div class="container">
		<?= Messenger::get()->getAll(); ?>

		<?= $this->_getHtmlContent(); ?>
	</div>
</body>
</html>
