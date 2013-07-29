<?php

class ModelException extends Exception{}

class ModelNotFoundException extends ModelException{
	public function __construct($model,$code=0){
		parent::__construct("Model not found: $model",$code);
	}
}

class ModelClassNotFoundException extends ModelException{
	public function __construct($model,$modelClass,$code=0){
		parent::__construct("Model class $modelClass for model $model not found",$code);
	}
}

class ModelFieldTypeNotFoundException extends ModelException{
	public function __construct($model,$field,$code=0){
		parent::__construct("Field $field in model $model is untyped",$code);
	}
}

class ModelRelationNotFoundException extends ModelException{
	public function __construct($search,$mode,$code=0){
		parent::__construct("Model $mode $search not found",$code);
	}
}

class ModelRequiredException extends ModelException{
	public function __construct($code=0){
		parent::__construct("Model is required",$code);
	}
}

class ModelUnrelatedException extends ModelException{
	public function __construct($tmodel,$model,$code=0){
		parent::__construct("Model $model is unrelated with $tmodel",$code);
	}
}

class ModelUpdateNeedsPKException extends ModelException{
	public function __construct($model,$code=0){
		parent::__construct("New record in $model needs PK",$code);
	}
}

class Record extends ArrayObject{
	public $model;
	public $modified;
	public $for_insert = FALSE;
	public $fields;
	public $pks;
	public function __construct($model,$data=NULL){
		parent::__construct();
		$this->model = $model;
		$this->fields = $this->model->fields;
		$this->pks = $this->model->pks;
		if($data) $this->import_data($data);
		$this->modified = FALSE;
	}
	public function import_data($data){
		foreach ($data as $key => $value) {
			$this[$key] = $value;
		}
	}
	public function offsetSet($i,$v){
		parent::offsetSet($i,$v);
		$this->modified = TRUE;
	}
	public function get_keys(){
		$res = array();
		foreach ($this->pks as $pk) {
			array_push($res,$this[$pk]);
		}
		return $res;
	}
	public function get_key(){
		return $this->get_keys()[0];
	}
	public function get_key_value($glue=' '){
		return implode($glue,$this->get_keys());
	}
	public function to_array(){
		$res = array();
		foreach ($this->fields as $field) {
			$key = $field['name'];
			$res[$key] = $this[$key];
		}
		return $res;
	}
	public function save(){
		$this->model->save_record($this);
	}
	public function delete(){
		$this->model->delete_record($this);
	}
	public function cancel(){
		if($this->for_insert) $this->delete();
	}
	public function join_parent($parent,$field){
		$this[$field.'_instance'] = $parent;
		if(!$parent[$this->model->name.'s']) $parent[$this->model->name.'s'] = array();
		array_push($parent[$this->model->name.'s'],$this);
		$parent[$this->model->name.'s_count'] = count($parent[$this->model->name.'s']);
	}
}

class Model{
	public $connector;
	public $name;
	public $table_name;
	public $fields;
	public $pks;
	public $fks;
	public $select;
	public $update;
	public $insert;
	public $delete;
	public $drop;
	public $create;
	public $records;
	public $record_class;
	public $orderby;
	public $position = 0;
	public function __construct($name){
		global $MODEL;
		global $RELATIONS;
		$this->connector = $MODEL->connector;
		$this->name = $name;
		$this->record_class = ucfirst($this->name).'Record';
		if(!class_exists($this->record_class))
			$this->record_class = 'Record';
		$fields = array();
		foreach ($this->fields as $key => $value) {
			$ar = array();
			$ar['name'] = $key;
			$exp = explode(' ',$value);
			$ar['type'] = $exp[0];
			if(!$ar['type']){
				new_error(new ModelFieldTypeNotFoundException($this->name,$key),$this);
			}elseif($ar['type']=='string') $ar['type'] = 'varchar(255)';
			elseif(strpos($ar['type'],'varchar')===0){
				if(strpos($ar['type'],'(')===FALSE)
					$ar['type'].='(255)';
			}
			$ar['null'] = strpos($value,'not null')===FALSE;
			$ar['autoincrement'] = strpos($value,'autoincrement')>0;
			array_push($fields,$ar);
		}
		$this->fields = $fields;
		if(!$this->pks) $this->pks = $this->fields[0]['name'];
		elseif(is_string($this->pks))
			$this->pks = explode(',',$this->pks);
		foreach ($this->pks as $value) {
			for($i=0;$i<count($this->fields);$i++){
				if($this->fields[$i]['name']==$value)
					$this->fields[$i]['pk'] = TRUE;
			}
		}
		if($this->fks&&is_string($this->fks)){
			$this->fks = explode(',',$this->fks);
		}
		if(count($this->fks)>0){
			$fks = array();
			foreach ($this->fks as $value) {
				$ar = explode('=',$value);
				if(count($ar)==2){
					$field = $ar[0];
					$ar = explode('.',$ar[1]);
					if(count($ar)==2){
						$table = $ar[0];
						$table_field = $ar[1];
						$fks[$field] = array($table,$table_field);
						for($i=0;$i<count($this->fields);$i++){
							if($this->fields[$i]['name']==$field){
								$this->fields[$i]['fk'] = array($table,$table_field);
								$RELATIONS->add_relation($this->name,$field,$table,$table_field);
								break;
							}
						}
					}
				}
			}
			$this->fks = $fks;
		}
		if(!$this->table_name) $this->table_name = $this->name.'s';
		if(!$this->select){
			$this->select = "select * from $this->table_name";
		}
		if(!$this->insert){
			$this->insert = "insert into $this->table_name";
			$fields = array();
			$values = array();
			foreach ($this->fields as $value) {
				$key = $value['name'];
				if(!$value['autoincrement']){
					array_push($fields,$key);
					array_push($values,"{{$key}}");
				}
			}
			$this->insert.=" (".implode(',',$fields).") values (".implode(',',$values).")";
		}
		if(!$this->update){
			$this->update = "update $this->table_name set";
			$fields = array();
			foreach ($this->fields as $value) {
				$key = $value['name'];
				if(!$value['pk']&&!$value['autoincrement']){
					array_push($fields,"$key={{$key}}");
				}
			}
			$this->update.=" ".implode(', ',$fields)." where";
			$fields = array();
			foreach ($this->pks as $value) {
				array_push($fields,"$value={{$value}}");
			}
			$this->update.=" ".implode(' and ',$fields);
		}
		if(!$this->delete){
			$this->delete = "delete from $this->table_name where";
			$fields = array();
			foreach ($this->pks as $value) {
				array_push($fields,"$value={{$value}}");
			}
			$this->delete.=" ".implode(' and ',$fields);
		}
		if(!$this->drop) $this->drop = "drop table $this->table_name";
		if(!$this->create){
			$this->create = $this->connector->create_table_header($this->table_name)."(\n";
			$fields = array();
			foreach ($this->fields as $value) {
				$s = $value['name'].' '.$this->connector->transform_sql_keyword($value['type']);
				if(!$value['null']) $s.=' not null';
				if($value['autoincrement']) $s.=' '.$this->connector->transform_sql_keyword('autoincrement');
				array_push($fields,$s);
			}
			$this->create.=implode(",\n",$fields)."\nprimary key(".implode(',',$this->pks).")\n";
			if(count($this->fks)>0){
				foreach ($this->fks as $key => $value) {
					$this->create.="foreign key ($key) references ".$value[0]."(".$value[1].")\n";
				}
			}
			$this->create.=")";
		}
		$this->records = array();
	}
	public function read_only(){
		return $this->insert=='no'||$this->update=='no';
	}
	private function create_where($where){
		if(!$where) return '';
		elseif(is_array($where)){
			$ar = array();
			foreach ($where as $key => $value) {
				if(!$value) continue;
				$f = $value[0];
				if($f!='='&&$f!='<'&&$F!='>')
					$value = "=".$this->connector->transform_sql_value($value);
				else{
					$value = substr($value,1);
					$value = $f.$this->connector->transform_sql_value($value);
				}
				array_push($ar,"$key$value");
			}
			return "where ".implode(' and ',$ar);
		}else return "where $where";
	}
	private function array_to_records($array){
		$this->records = array();
		foreach ($array as $row) {
			$record = new $this->record_class($this,$row);
			array_push($this->records,$record);
		}
	}
	public function select_query($where=NULL,$orderby=NULL,$q=NULL){
		if(!$orderby) $orderby = $this->orderby;
		if(!$q) $q = $this->select;
		if($where) $q.=' '.$this->create_where($where);
		if($orderby) $q.=" order by $orderby";
		if(!$this->connector->connected)
			$this->connector->connect();
		$this->array_to_records($this->connector->select($q));
		return $this->records;
	}
	public function all(){
		return $this->select_query();
	}
	public function filter($where,$orderby=NULL){
		return $this->select_query($where,$orderby);
	}
	public function get($pos){
		return $this->records[$pos];
	}
	public function count(){
		return count($this->records);
	}
	public function first(){
		return $this->get(0);
	}
	public function last(){
		return $this->get($this->count()-1);
	}
	public function range($init,$finish=NULL){
		if(!$finish||$finish>$this->count()) $finish = $this->count();
		$ar = array();
		for($i=$init;$i<$finish;$i++){
			array_push($ar,$this->records[$i]);
		}
		return $ar;
	}
	public function next(){
		if($this->position>=$this->count())
			return NULL;
		else{
			$rec = $this->records[$this->position];
			$this->position++;
			return $rec;
		}
	}
	public function reset_position(){
		$this->position = 0;
	}
	public function set_position($pos){
		$this->position = $pos;
	}
	public function has_next(){
		if($this->records[$this->position])
			return TRUE;
		else return FALSE;
	}
	public function next_range($size){
		if($this->position>=$this->count())
			return array();
		else{
			$ar = $this->range($this->position,$this->position+$size);
			$this->position+=$size;
			return $ar;
		}
	}
	public function get_hash_key(){
		$ar = array();
		foreach ($this->records as $value) {
			$ar[$value->get_key_value()] = $value;
		}
		return $ar;
	}
	public function get_hash_fields($fields,$glue=' '){
		if(is_string($fields))
			$fields = explode(',',$fields);
		$res = array();
		foreach ($this->records as $record) {
			$vals = array();
			foreach ($fields as $field) {
				array_push($vals,$record[$field]);
			}
			$vals = implode($glue,$vals);
			if(!$res[$vals]) $res[$vals] = array();
			array_push($res[$vals],$record);
		}
		return $res;
	}
	public function new_record($data=NULL){
		$rec = new $this->record_class($this,$data);
		$rec->modified = TRUE;
		$rec->for_insert = TRUE;
		array_push($this->records,$rec);
		return $rec;
	}
	private function replace_values($txt,$rec){
		foreach ($this->fields as $value) {
			$key = $value['name'];
			$val = $this->connector->transform_sql_value($rec[$key]);
			if(!$val) $val = 'NULL';
			$txt = str_replace("{{$key}}",$val,$txt);
		}
		return $txt;
	}
	public function save_record($rec){
		if($rec->for_insert){
			$q = $this->insert;
			$q = $this->replace_values($q,$rec);
			if(!$this->connector->connected)
				$this->connector->connect();
			$id = $this->connector->insert($q);
			$rec->for_insert = FALSE;
			foreach ($this->fields as $value) {
				if($value['autoincrement']){
					$key = $value['name'];
					$rec[$key] = $id;
					break;
				}
			}
		}else{
			$q = $this->update;
			$q = $this->replace_values($q,$rec);
			if(!$this->connector->connected)
				$this->connector->connect();
			$this->connector->update($q);
		}
		$rec->modified = FALSE;
	}
	public function save(){
		foreach ($this->records as $record) {
			if($record->modified)
				$this->save_record($record);
		}
	}
	public function delete_record($rec){
		if(!$rec->for_insert){
			$q = $this->delete;
			$q = $this->replace_values($q,$rec);
			if(!$this->connector->connected)
				$this->connector->connect();
			$this->connector->update($q);
			$pos = array_search($rec,$this->records);
			if(!($pos===FALSE)){
				array_splice($this->records,$pos,1);
			}
		}
	}
	public function create_table(){
		$q = $this->create;
		if(!$this->connector->connected)
			$this->connector->connect();
		$this->connector->update($q);
	}
	public function to_array(){
		$res = array();
		foreach ($this->records as $record) {
			array_push($res,$record->to_array());
		}
		return $res;
	}
	public function get_children_relations(){
		global $RELATIONS;
		return $RELATIONS->get_childs($this->name);
	}
	public function get_parents_relations(){
		global $RELATIONS;
		return $RELATIONS->get_parents($this->name);
	}
	private function join_model_relation($rel,$mode){
		global $MODEL;
		if(!$rel->master_model) $rel->master_model = $MODEL->get_model($rel->master);
		if(!$rel->master_model){
			new_error(new ModelRelationNotFoundException($rel->master,'master'),$this);
			return;
		}
		if(!$rel->child_model) $rel->child_model = $MODEL->get_model($rel->child);
		if(!$rel->child_model){
			new_error(new ModelRelationNotFoundException($rel->child,'child'),$this);
			return;
		}
		if($mode=='child'){
			$m = $rel->child_model;
		}else{
			$m = $rel->master_model;
		}
		$m->all();
		$this->join_with_model($m,$mode,$rel);
	}
	public function join_model($model,$mode=NULL){
		if($model instanceof Model) $model = $model->name;
		if($mode=='parent'||$mode==NULL){
			$parents = $this->get_parents_relations();
			foreach ($parents as $value) {
				if($value->master==$model){
					$this->join_model_relation($value,'parent');
					break;
				}
			}
		}
		if($mode=='child'||$mode==NULL){
			$children = $this->get_children_relations();
			foreach ($variable as $value) {
				if($value->child==$model){
					$this->join_model_relation($value,'child');
					break;
				}
			}
		}
	}
	public function join_all($mode=NULL){
		if($mode=='parent'||$mode==NULL){
			$parents = $this->get_parents_relations();
			foreach ($parents as $value) {
				$this->join_model_relation($value,'parent');
			}
		}
		if($mode=='child'||$mode==NULL){
			$children = $this->get_children_relations();
			foreach ($children as $value) {
				$this->join_model_relation($value,'child');
			}
		}
	}
	protected function join_with_parent($parent,$field,$parent_field){
		$hash = $this->get_hash_fields($field);
		foreach ($parent->records as $value) {
			$ar = $hash[$value[$parent_field]];
			foreach ($ar as $record) {
				$record->join_parent($value,$field);
			}
		}
	}
	private function join_with_model($model,$mode,$rel){
		if($mode=='child'){
			$model->join_with_parent($this,$rel->child_field,$rel->master_field);
		}else{
			$this->join_with_parent($model,$rel->child_field,$rel->master_field);
		}
	}
	public function join_with($model){
		if(!($model instanceof Model)){
			new_error(new ModelRequiredException(),$this);
			return;
		}
		$parents = $this->get_parents_relations();
		$children = $this->get_children_relations();
		if(array_key_exists($model->name,$parents)){
			$mode = 'parent';
			$rel = $parents[$model->name];
		}elseif(array_key_exists($model->name,$children)){
			$mode = 'child';
			$rel = $children[$model->name];
		}else{
			new_error(new ModelUnrelatedException($this,$model),$this);
			return;
		}
		$this->join_with_model($model,$mode,$rel);
	}
	public function use_post($mode='insert'){
		$fields = array();
		foreach ($this->fields as $value) {
			$key = $value['name'];
			if(array_key_exists($key,$_POST)){
				$fields[$key] = $_POST[$key];
			}
		}
		$record = NULL;
		if($mode=='update'){
			$has_pk = FALSE;
			$where = array();
			foreach ($this->pks as $value) {
				if(array_key_exists($value,$fields)){
					$where[$value] = $fields[$value];
					$has_pk = TRUE;
				}
			}
			if($has_pk){
				$this->filter($where);
				$record = $this->first();
				foreach ($fields as $key => $value) {
					if(!array_key_exists($key,$where)){
						$record[$key] = $value;
					}
				}
				$record->save();
			}else new_error(new ModelUpdateNeedsPKException($this->name),$this);
		}else{
			$record = $this->new_record($fields);
			$record->save();
		}
		return $record;
	}
}

class Relation{
	public $master_model;
	public $master;
	public $master_field;
	public $child_model;
	public $child;
	public $child_field;
	public function __construct($master,$master_field,$child,$child_field){
		$this->master = $master;
		$this->master_field = $master_field;
		$this->child = $child;
		$this->child_field = $child_field;
		$this->verify_instances();
	}
	public function verify_instances(){
		global $MODEL;
		if(!$this->master_model&&array_key_exists($this->master,$MODEL->loaded_models))
			$this->master_model = $MODEL->loaded_models[$this->master];
		if(!$this->child_model&&array_key_exists($this->child,$MODEL->loaded_models))
			$this->child_model = $MODEL->loaded_models[$this->child];
	}
}

class RelationManager{
	public $relations;
	public $parents;
	public $children;
	public function __construct(){
		$this->relations = array();
		$this->parents = array();
		$this->children = array();
	}
	public function add_relation($child,$child_field,$master,$master_field){
		$rel = new Relation($master,$master_field,$child,$child_field);
		array_push($this->relations,$rel);
		if(!array_key_exists($rel->master,$this->parents))
			$this->parents[$rel->master] = array();
		array_push($this->parents[$rel->master],$rel);
		if(!array_key_exists($rel->child,$this->children))
			$this->children[$rel->child] = array();
		array_push($this->children[$rel->child],$rel);
	}
	public function get_childs($parent){
		$res = $this->parents[$parent];
		if(!$res) return array();
		foreach ($res as $value) {
			$value->verify_instances();
		}
		return $res;
	}
	public function get_parents($child){
		$res = $this->children[$child];
		if(!$res) return array();
		foreach ($res as $value) {
			$value->verify_instances();
		}
		return $res;
	}
}

class ModelManager{
	public $connector;
	public $loaded_models;
	public function __construct(){
		global $CONNECTOR;
		$this->connector = $CONNECTOR;
		$this->loaded_models = array();
	}
	public function get_model_file_path($model){
		global $APP_PATH;
		$file = $APP_PATH.'/models/'.$model.'.php';
		if(file_exists($file)) return $file;
		else return NULL;
	}
	public function model_exists($model){
		return $this->get_model_file_path($model)!=NULL;
	}
	public function get_model($model){
		if(array_key_exists($model,$this->loaded_models))
			return $this->loaded_models[$model];
		$file = $this->get_model_file_path($model);
		if($file){
			require $file;
			$modelClass = ucfirst($model);
			if(class_exists($modelClass)){
				$instance = new $modelClass($model);
				$this->loaded_models[$model] = $instance;
				return $instance;
			}else new_error(new ModelClassNotFoundException($model,$modelClass),$this);
		}else new_error(new ModelNotFoundException($model),$this);
	}
	public function get_model_for_controller($controller){
		if($this->model_exists($controller->action->controller))
			return $this->get_model($controller->action->controller);
		else return NULL;
	}
}

$MODEL = new ModelManager();
$RELATIONS = new RelationManager();