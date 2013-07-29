<?php

class Controller{
	public $request;
	public $errors;
	public $route;
	public $is_post;
	public $url_params;
	public $action;
	public $model;
	public $models;
	public function __construct($action){
		global $REQUEST;
		global $ROUTE;
		global $ERRORS;
		$this->action = $action;
		$this->request = $REQUEST;
		$this->route = $ROUTE;
		$this->errors = $ERRORS;
		$this->is_post = $this->request->is_post();
		$this->url_params = array();
		for($i=2;$i<count($this->request->url_params);$i++){
			array_push($this->url_params,$this->request->url_params[$i]);
		}
		$this->initialize();
	}
	protected function initialize(){
	}
	public function add_model($model=NULL){
		global $MODEL;
		if($model instanceof Model) $res = $this->add_model_instance($model);
		elseif($model!=NULL) $res = $this->add_model_instance($MODEL->get_model($model));
		else $res = $this->add_model_instance($MODEL->get_model_for_controller($this));
		return $res;
	}
	private function add_model_instance($model){
		$this->models[$model->name] = $model;
		if($model->name==$this->action->controller)
			$this->model = $model;
		return $model;
	}
	public function get_model($name=NULL){
		if($name){
			$model = $this->models[$name];
			if(!$model) $model = $this->add_model($name);
			return $model;
		}else{
			$model = $this->model;
			if(!$model) $model = $this->add_model();
			return $model;
		}
	}
	public function get_param($pos,$method=NULL){
		if(is_numeric($pos)) return $this->url_params[$pos];
		else return $this->request->get_param($pos,$method);
	}
	public function create_view($data=NULL){
		global $VIEW;
		$VIEW->create_view($this,$data);
	}
	public function content_type($ct){
		global $CONTENT_TYPE;
		$CONTENT_TYPE = $ct;
	}
	public function redirect($action,$params=NULL,$query=NULL){
		global $REDIRECT;
		global $ROUTE;
		$REDIRECT = $ROUTE->create_route($this,$action,$params,$query);
	}
}