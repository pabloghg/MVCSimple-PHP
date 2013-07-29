<?php
$REDIRECT = NULL;
$CONTENT_TYPE = 'text/html';
$CONFIG_PATH = $APP_PATH.'/config';
require $CONFIG_PATH.'/app.php';
require $CONFIG_PATH.'/db.php';
require $CONFIG_PATH.'/route.php';
require $LIB_PATH.'/core/errors.php';
require $LIB_PATH.'/db/connector.php';
require $LIB_PATH.'/core/controller.php';
require $LIB_PATH.'/core/request.php';
require $LIB_PATH.'/core/route.php';
require $LIB_PATH.'/core/view.php';
require $LIB_PATH.'/core/model.php';

$result = $ACTION->execute();
if($REDIRECT){
	header("location: $REDIRECT");
	exit();
}else echo $result;
