<?php

class UsuarioController extends Controller{
	public $usuarios;
	protected function initialize(){
		$this->usuarios = $this->get_model();
	}
	public function index(){
		$this->usuarios->all();
		$this->create_view();
	}
	public function edit($id){
		if($this->is_post){
			$this->usuarios->use_post('update');
			$this->redirect('index');
		}else{
			$usuario = $this->usuarios->filter(array('id'=>$id))[0];
			$this->create_view(array('usuario'=>$usuario));
		}
	}
	public function delete($id){
	}
	public function add(){
		if($this->is_post){
			$this->usuarios->use_post('insert');
			$this->redirect('index');
		}else{
			$this->create_view();
		}
	}
}