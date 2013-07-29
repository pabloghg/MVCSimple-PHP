<?php

class Usuario extends Model{
	public $fields = array(
		'id'=>'integer not null autoincrement',
		'email'=>'varchar(255) not null',
		'clave'=>'varchar(50)',
		'nombre'=>'varchar(255) not null',
		'admin'=>'integer not null'
	);
	public $pks = 'id';
}