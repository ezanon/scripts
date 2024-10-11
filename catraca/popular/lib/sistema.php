<?php

class sistema {
	
	public $banco;

	public function __construct(){
		$this->banco = banco::instanciar();
		return true;
	}
	
/*
 * Obtem info do aplicativo
 */
	public function get_info($id){
		$q = "select * from sistemas where id=" . $id;
		$ss = $this->banco->consultar($q);
		foreach ($ss as $s)
			foreach ($s as $key=>$value){
				$this->$key = $value;	
			}
		return true;
	}
	
}
	
?>
	