<?php

class MySQLConnector extends Connector{
	private $link;
	protected function a_connect(){
		$this->link = mysql_connect($this->connection_data['host'],
			$this->connection_data['user'],$this->connection_data['password']);
		if($this->link){
			if(mysql_select_db($this->connection_data['database'],$this->link))
				return TRUE;
			else throw new ConnectorException('Database not found');
		}else throw new ConnectorException('Could not connect to the host');
		return FALSE;
	}
	protected function a_close(){
		if($this->link) mysql_close($this->link);
	}
	protected function a_insert($q){
		mysql_query($q,$this->link);
		return mysql_insert_id($this->link);
	}
	protected function a_update($q){
		mysql_query($q,$this->link);
		return TRUE;
	}
	protected function a_select($q){
		$res = mysql_query($q,$this->link);
		$ar = array();
		if(is_resource($res)){
			while($row=mysql_fetch_assoc($res)){
				array_push($ar,$row);
			}
		}
		return $ar;
	}
	protected function a_show_tables(){
		$res = mysql_query('show tables',$this->link);
		$ar = array();
		if(is_resource($res)){
			while($row=mysql_fetch_array($res)){
				array_push($ar,$row[0]);
			}
		}
		return $ar;
	}
	public function transform_sql_keyword($keyword){
		switch ($keyword) {
			case 'autoincrement': $keyword = 'auto_increment';break;
			case 'integer': $keyword = 'int';break;
		}
		return $keyword;
	}
	public function create_table_header($table_name){
		return "create table if not exists $table_name";
	}
}

$CONNECTOR = new MySQLConnector();