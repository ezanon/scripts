<?php

class pessoas{
	
	public $banco;
	
	public function __construct(){
		$this->banco = banco::instanciar();
		return true;
	}
	
	/*
	* Remove registros não checados pelo sistema de importação de usuários // module=replicacao&action=replicar
	*/
	public function remover_unchecked(){
		$q = "delete from pessoas where checked = 0";	
		$this->banco->executar($q);
		return true;
	}
	
	/*
	* Ative ou desativa flag de checado ou não pelo processo de importação de usuários // module=replicacao&action=replicar
	* 1 - o usuário foi checado
	* 0 - o usuário não foi checado... provavelmente não tem mais vínculo com unidade
	*/ 
	public function checked($flag){
		$q = "update pessoas set checked=" . $flag;
		$this->banco->executar($q);
		return true;	
	}
	
}
	
?>