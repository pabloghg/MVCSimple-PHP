<?php

class ViewException extends Exception{
	public $controller;
	public function __construct($message,$controller,$code=0){
		parent::__construct($message,$code);
		$this->controller = $controller;
	}
}

class ViewFileNotFoundException extends ViewException{
	public function __construct($controller,$code=0){
		parent::__construct("File view for controller not found: ".$controller->action,$controller,$code);
	}
}

class ViewManager{
	public function get_file_for_controller($controller){
		return $this->get_file_for_action($controller->action);
	}
	public function get_file_for_action($action){
		return $this->get_file_for_function($action->controller,$action->function);
	}
	public function get_file_for_function($controller,$function){
		global $APP_PATH;
		$controller_section = $APP_PATH.'/views/'.$controller;
		$function_section = $controller_section.'.'.$function.'.php';
		$controller_section.='.php';
		if(file_exists($function_section)) return $function_section;
		elseif(file_exists($controller_section)) return $controller_section;
		else return NULL;
	}
	private function get_file_path($view_file){
		global $APP_PATH;
		$path = $APP_PATH.'/views/'.$view_file.'.php';
		if(file_exists($path)) return $path;
		else return NULL;
	}
	public function create_view($c,$d=NULL){
		global $REDIRECT;
		if($REDIRECT) return;
		global $data;
		global $controller;
		global $model;
		global $models;
		global $CONTENT_TYPE;
		global $helpers;
		header("content-type:$CONTENT_TYPE");
		$controller = $c;
		$model = $controller->model;
		$models = $controller->models;
		$data = $d;
		$helpers = array();
		if(!$data) $data = array();
		function section($section){
			global $data;
			if(function_exists($section))
				return call_user_func($section);
			else return "";
		}
		function controller(){
			global $controller;
			return $controller;
		}
		function data(){
			global $data;
			return $data;
		}
		function get($key){
			return data()[$key];
		}
		function set($key,$value){
			global $data;
			$data[$key] = $value;
		}
		function put($key){
			echo get($key);
		}
		function model($name=NULL){
			global $model;
			global $models;
			if($name==NULL) return $model;
			else return $models[$name];
		}
		function helper($name){
			global $helpers;
			global $LIB_PATH;
			if($helpers[$name]) return $helpers[$name];
			$file = $LIB_PATH."/helpers/$name.php";
			if(file_exists($file)){
				require $file;
				$helper_class = ucfirst($name).'Helper';
				if(class_exists($helper_class)){
					$instance = new $helper_class();
					$helpers[$name] = $instance;
					return $instance;
				}else return NULL;
			}else return NULL;
		}
		$file = $this->get_file_for_controller($controller);
		if(!$file) new_error(new ViewFileNotFoundException($controller),$this);
		require $file;
		while(isset($extend)&&$extend){
			$file = $this->get_file_path($extend);
			$extend = NULL;
			if($file) require $file;
		}
	}
}

$VIEW = new ViewManager();