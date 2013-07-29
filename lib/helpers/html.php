<?php

class Form{
	public $url;
	public $method;
	public $id;
	public $record;
	public $model;
	public function __construct($id,$url,$method){
		if(!$id) $id = 'form_'.time().'_'.((int)rand(0,999));
		$this->id = $id;
		$this->url = $url;
		$this->method = $method;
	}
	public function str_start(){
		return "<form action='$this->url' method='$this->method' id='$this->id'>";
	}
	public function start(){
		echo $this->str_start();
	}
	public function str_end(){
		return '</form>';
	}
	public function end(){
		echo $this->str_end();
	}
	private function get_input_type_for_field($field){
		if($field['type']=='text') return 'textarea';
		else return 'text';
	}
	public function str_input($name,$type=NULL,$value=NULL,$class=NULL){
		$field = NULL;
		if($this->record||$this->model){
			$fields = $this->record?$this->record->fields:$this->model->fields;
			foreach ($fields as $v) {
				if($v['name']==$name){
					$field = $v;
					break;
				}
			}
		}
		if($field){
			if(!$type) $type = $this->get_input_type_for_field($field);
			if($this->record&&!$value) $value = $this->record[$field['name']];
		}
		if(!$type) $type = 'text';
		$id = $this->id.'_'.$name;
		if($class) $class = " class='$class'";
		else $class = '';
		if(!$value) $value = '';
		if($type=='textarea')
			return "<textarea name='$name' id='$id'$class>$value</textarea>";
		else return "<input type='$type' name='$name' id='$id' value='$value'$class />";
	}
	public function input($name,$type=NULL,$value=NULL,$class=NULL){
		echo $this->str_input($name,$type,$value,$class);
	}
	public function str_yesno($name,$yes='Yes',$no='No',$value=TRUE,$class=NULL){
		if($this->record){
			$value = $this->record[$name]==1;
		}
		$idyes = $this->id.'_'.$name.'_yes';
		$idno = $this->id.'_'.$name.'_no';
		$checkedyes = $value?" checked='checked'":"";
		$checkedno = $value?"":" checked='checked'";
		return "<input type='radio' name='$name' id='$idyes' value='1'$class$checkedyes />".
			"<label for='$idyes'>$yes</label>".
			"<input type='radio' name='$name' id='$idno' value='0'$class$checkedno />".
			"<label for='$idno'>$no</label>";
	}
	public function yesno($name,$yes='Yes',$no='No',$value=TRUE,$class=NULL){
		echo $this->str_yesno($name,$yes,$no,$value,$class);
	}
	public function str_submit($value=NULL){
		if($value) $value = " value='$value'";
		$id = $this->id.'_submit';
		return "<input type='submit'$value id='$id' />";
	}
	public function submit($value=NULL){
		echo $this->str_submit($value);
	}
	public function str_reset($value=NULL){
		if($value) $value = " value='$value'";
		$id = $this->id.'_reset';
		return "<input type='reset'$value id='$id' />";
	}
	public function reset($value=NULL){
		echo $this->str_reset($value);
	}
	public function str_id(){
		if(!$this->record) return '';
		$res = array();
		foreach ($this->record->pks as $value) {
			$v = $this->record[$value];
			$id = $this->id.'_'.$value;
			array_push($res,"<input name='$value' type='hidden' id='$id' value='$v' />");
		}
		return implode('',$res);
	}
	public function id(){
		echo $this->str_id();
	}
}

class HtmlHelper{
	public function str_href($action=NULL,$params=NULL,$query=NULL){
		global $ROUTE;
		return $ROUTE->create_route(controller(),$action,$params,$query);
	}
	public function href($action=NULL,$params=NULL,$query=NULL){
		echo $this->str_href($action,$params,$query);
	}
	public function str_link($text,$action=NULL,$params=NULL,$query=NULL,$target=NULL){
		if($target) $target = " target='$target'";
		$href = $this->str_href($action,$params,$query);
		$a = "<a href='$href'$target>$text</a>";
		return $a;
	}
	public function link($text,$action=NULL,$params=NULL,$query=NULL,$target=NULL){
		echo $this->str_link($text,$action,$params,$query,$target);
	}
	public function str_href_shared($url){
		global $SHARED_URL;
		return $SHARED_URL."/$url";
	}
	public function href_shared($url){
		echo $this->str_href_shared($url);
	}
	public function str_link_shared($text,$url,$target=NULL){
		if($target) $target = " target='$target'";
		$url = $this->str_href_shared($url);
		return "<a href='$url'$target>$text</a>";
	}
	public function link_shared($text,$url,$target=NULL){
		echo $this->str_link_shared($text,$url,$target);
	}
	public function form($action='*',$params='*',$query='*',$method='post',$id=NULL){
		$url = $this->str_href($action,$params,$query);
		$form = new Form($id,$url,$method);
		return $form;
	}
}