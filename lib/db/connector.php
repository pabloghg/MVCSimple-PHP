<?php

class ConnectorException extends Exception{}

class Connector{
	public $connection_data;
	public $connected = FALSE;
	public function __construct(){
		global $DB_CONFIG;
		$this->connection_data = $DB_CONFIG;
	}
	public function __destruct(){
		if(!$this->connected) return;
		try{
			$this->close();
		}catch(Exception $e){}
	}
	protected function a_connect(){
		return FALSE;
	}
	public function connect(){
		try{
			$this->connected = $this->a_connect();
		}catch(Exception $e){
			new_error($e,$this);
		}
		if(!$this->connected){
			new_error(new ConnectorException('Undefined connect DB exception'),$this);
		}
	}
	protected function a_close(){
	}
	public function close(){
		try{
			$this->a_close();
		}catch(Exception $e){
			new_error($e,$this);
		}
	}
	protected function a_insert($q){
		return 0;
	}
	protected function a_update($q){
		return FALSE;
	}
	protected function a_select($q){
		return array();
	}
	public function insert($q){
		if(!$this->connected){
			new_error(new ConnectorException('Connector is not connected'),$this);
			return 0;
		}
		try{
			return $this->a_insert($q);
		}catch(Exception $e){
			new_error($e,$this);
			return 0;
		}
	}
	public function update($q){
		if(!$this->connected){
			new_error(new ConnectorException('Connector is not connected'),$this);
			return FALSE;
		}
		try{
			return $this->a_update($q);
		}catch(Exception $e){
			new_error($e,$this);
			return FALSE;
		}
	}
	public function select($q){
		if(!$this->connected){
			new_error(new ConnectorException('Connector is not connected'),$this);
			return array();
		}
		try{
			return $this->a_select($q);
		}catch(Exception $e){
			new_error($e,$this);
			return array();
		}
	}
	protected function a_show_tables(){
		return array();
	}
	public function show_tables(){
		if(!$this->connected){
			new_error(new ConnectorException('Connector is not connected'),$this);
			return array();
		}
		try{
			return $this->a_show_tables();
		}catch(Exception $e){
			new_error($e,$this);
			return array();
		}
	}
	public function transform_sql_text($text){
		return str_replace("'","\\'",$text);
	}
	public function transform_sql_value($value){
		if(gettype($value)=='string')
			return "'".$this->transform_sql_text($value)."'";
		else return $value;
	}
	public function transform_sql_keyword($keyword){
		return $keyword;
	}
	public function create_table_header($table_name){
		return "create table $table_name";
	}
}

function get_dbdriver_file($engine){
	global $LIB_PATH;
	$file = $LIB_PATH.'/db/driver/'.$engine.'.php';
	if(file_exists($file)) return $file;
	else return NULL;
}

$FILE_DBDRIVER = get_dbdriver_file($DB_CONFIG['engine']);
if($FILE_DBDRIVER){
	require $FILE_DBDRIVER;
}