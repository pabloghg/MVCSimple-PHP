<?php
$html = helper('html');
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Test - <?php echo put('title') ?></title>
		<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
		<link rel="stylesheet" type="text/css" href="<?php $html->href_shared('estilos.css') ?>">
	</head>
	<body>
		<h1><?php put('title') ?></h1>
		<?php section('contenido') ?>
	</body>
</html>