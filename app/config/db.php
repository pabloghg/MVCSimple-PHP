<?php
$DB_CONFIG_DEV = array(
	'engine'=>'mysql',
	'host'=>'localhost',
	'user'=>'root',
	'password'=>'pablo',
	'database'=>'easf'
);
$DB_CONFIG_PROD = array(
	'engine'=>'mysql',
	'host'=>'localhost',
	'user'=>'root',
	'password'=>'pablo',
	'database'=>'easf'
);
$DB_CONFIG = $APP_STAGE=='dev'?$DB_CONFIG_DEV:$DB_CONFIG_PROD;