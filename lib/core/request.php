<?php

class Request{
	public $vars;
	public $url_params;
	public function __construct(){
		global $PUBLIC_URL;
		$this->vars = array();
		foreach ($_SERVER as $key => $value) {
			$this->vars[$key] = $value;
		}
		$this->vars['PUBLIC_URL'] = $PUBLIC_URL;
		$this->vars['COMPLETE_URL'] = 'http://'.$this->vars['HTTP_HOST'].$this->vars['REQUEST_URI'];
		$this->vars['APP_QUERY'] = substr($this->vars['COMPLETE_URL'],strlen($PUBLIC_URL)+1);
		$this->url_params = array();
		if($this->vars['APP_QUERY']){
			$txt = explode('?',$this->vars['APP_QUERY'])[0];
			if($txt) $this->url_params = explode('/',$txt);
		}
	}
	public function get_var($key){
		return $this->vars[$key];
	}
	public function is_get(){
		return strtoupper($this->vars['REQUEST_METHOD'])=='GET';
	}
	public function is_post(){
		return strtoupper($this->vars['REQUEST_METHOD'])=='POST';
	}
	public function get_param($pos,$method=NULL){
		if(is_numeric($pos)) return $this->url_params[$pos];
		else{
			if(!$method){
				$method = $this->is_post()?'post':'get';
			}else $method = strtolower($method);
			if($method=='post') return $_POST[$pos];
			else return $_GET[$pos];
		}
	}
}

$REQUEST = new Request();