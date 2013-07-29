<?php

class ErrorManager{
	public $errors;
	public function __construct(){
		$this->errors = array();
	}
	public function new_error($error,$sender=NULL){
		global $THROW_ERRORS;
		if(!($error instanceof Exception))
			$error = new Exception($error);
		array_push($this->errors,array($error,$sender));
		if($THROW_ERRORS) throw $error;
	}
}

function new_error($error,$sender=NULL){
	global $ERRORS;
	$ERRORS->new_error($error,$sender);
}

$ERRORS = new ErrorManager();