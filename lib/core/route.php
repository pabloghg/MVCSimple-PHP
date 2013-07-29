<?php

class ActionException extends Exception{
	public $action;
	public function __construct($message,$action,$code=0){
		parent::__construct($message,$code);
		$this->action = $action;
	}
}

class ActionFileNotFoundException extends ActionException{
	public function __construct($file,$action,$code=0){
		parent::__construct("File not found for controller: $file",$action,$code);
	}
}

class ActionFunctionNotFoundException extends ActionException{
	public function __construct($function,$action,$code=0){
		parent::__construct("Function not found for controller: $function",$action,$code);
	} 
}

class ActionClassNotFoundException extends ActionException{
	public function __construct($class,$action,$code=0){
		parent::__construct("Class not found for controller: $class",$action,$code);
	} 
}

class Action{
	public $controller;
	public $controller_name;
	public $function;
	public $function_name;
	public $instanceController;
	public function __construct($controller,$function){
		global $ROUTE;
		$this->controller_name = $controller;
		$this->function_name = $function;
		$this->controller = $this->controller_name;
		$this->function = $this->function_name;
		$ar = $ROUTE->custom_routes[$this->controller_name];
		if($ar){
			$this->controller = $ar['controller'];
			$arf = $ar['functions'][$this->function_name];
			if($arf){
				$this->controller = $arf[0];
				$this->function = $arf[1];
			}
		}
	}
	public function __toString(){
		return $this->controller.'.'.$this->function;
	}
	public function get_controller_file(){
		global $APP_PATH;
		return $APP_PATH.'/controllers/'.$this->controller.'.php';
	}
	public function get_controller_class_name(){
		return ucfirst($this->controller).'Controller';
	}
	public function create_instances(){
		if(!$this->instanceController){
			$file = $this->get_controller_file();
			$class = $this->get_controller_class_name();
			if(file_exists($file)){
				require $file;
				if(class_exists($class)){
					try{
						$this->instanceController = new $class($this);
					}catch(Exception $e){
						new_error($e,$this);
					}
				}else new_error(new ActionClassNotFoundException($class,$this),$this);
			}else new_error(new ActionFileNotFoundException($file,$this),$this);
		}
		if($this->instanceController){
			if(!method_exists($this->instanceController,$this->function))
				new_error(new ActionFunctionNotFoundException($this->function,$this),$this);
		}
	}
	public function execute(){
		$this->create_instances();
		if($this->instanceController)
			return call_user_func_array(array($this->instanceController,$this->function),
				$this->instanceController->url_params);
		else return NULL;
	}
}

class Route{
	public $sections;
	public $custom_routes;
	public function __construct(){
		global $REQUEST;
		$this->sections = array(
			$REQUEST->get_param(0),
			$REQUEST->get_param(1)
		);
		$this->process_custom_routes();
	}
	private function process_custom_controller($custom,$controller){
		$this->custom_routes[$custom]['controller'] = $controller;
	}
	private function process_custom_function($ark,$arv){
		if(count($arv)==1){
			$this->custom_routes[$ark[0]]['functions'][$ark[1]] = array($this->custom_routes[$ark[0]]['controller'],$arv[0]);
		}else{
			$cont = $arv[0];
			if(!$cont) $cont = $this->custom_routes[$ark[0]]['controller'];
			$this->custom_routes[$ark[0]]['functions'][$ark[1]] = array($cont,$arv[1]);
		}
	}
	private function process_custom_routes(){
		global $CUSTOM_ROUTES;
		$this->custom_routes = array();
		foreach ($CUSTOM_ROUTES as $key => $value) {
			$ark = explode('/',$key);
			$arv = explode('.',$value);
			if(!array_key_exists($ark[0],$this->custom_routes)){
				$this->custom_routes[$ark[0]] = array(
					'controller'=>$ark[0],
					'functions'=>array()
				);
			}
			if(count($ark)==1) $this->process_custom_controller($ark[0],$arv[0]);
			else $this->process_custom_function($ark,$arv);
		}
	}
	public function get_controller_section(){
		$p = $this->sections[0];
		if(!$p) $p = 'index';
		return $p;
	}
	public function get_function_section(){
		$p = $this->sections[1];
		if(!$p) $p = 'index';
		return $p;
	}
	public function get_action(){
		return new Action($this->get_controller_section(),$this->get_function_section());
	}
	public function create_route($controller_context,$action,$params=NULL,$query=NULL){
		global $REQUEST;
		global $PUBLIC_URL;
		if($action=='*')
			$action = $controller_context->action->controller.'.'.$controller_context->action->function;
		if(is_string($params)){
			if($params=='*') $params = $controller_context->url_params;
			else $params = explode(',',$params);
		}
		if(!is_array($params)) $params = array();
		if(!$action) $ar = array();
		elseif(is_string($action)) $ar = explode('.',$action);
		elseif(is_array($action)) $ar = $action;
		else $ar = $action;
		if(count($ar)>0){
			if(count($ar)==1){
				$controller = $controller_context->action->controller;
				$function = $ar[0];
			}else{
				$controller = array_shift($ar);
				if(!$controller) $controller = $controller_context->action->controller;
				$function = array_shift($ar);
				if(count($ar)>0){
					foreach ($ar as $value) {
						array_unshift($params,$value);
					}
				}
			}
		}else{
			$controller = $controller_context->action->controller;
			$function = $controller_context->action->function;
		}
		if(is_array($query)){
			$q = array();
			foreach ($query as $key => $value) {
				array_push($q,"$key=$value");
			}
			$query = implode('&',$q);
		}elseif($query=='*') $query = $REQUEST->vars['QUERY_STRING'];
		if(!$query) $query = '';
		else $query = "?$query";
		if(count($params)>0)
			$params = implode('/',$params); 
		else $params = '';
		if($params) $params = "/$params";
		return $PUBLIC_URL."/$controller/$function$params$query";
	}
}

$ROUTE = new Route();
$ACTION = $ROUTE->get_action();