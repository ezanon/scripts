<?php

class sistemas {
	
	public $banco;
	
	public $appids = array(); // array com os ids
	public $appalias = array(); // array com alias, chave é o id
	public $appnome = array(); // array com nomes, chave é o id
	public $appcategorias = array(); // array com as categorias do sistema onde chave é o id
	public $applink = array(); // array com o link do sistema onde chave é o id
	public $appativo = array(); // array com o status ativo ou inativo onde chave é o id

	public function __construct(){
		$this->banco = banco::instanciar();
		return true;
	}
	
	/*
	* Devolve ids das categorias autorizadas para determinada categoria
	* $ativo se o sistema está habilitado: all -> tanto faz; 0 inativo; 1 ativo
	*/
	public function autorizadas_por_categoria($categoria,$ativo=1){
		$q = "select id from sistemas where (" .
				"categorias='" . $categoria . "'" .
				" or categorias like '" . $categoria . ",%'" .
				" or categorias like '%," . $categoria . ",%'" .
				" or categorias like '%," . $categoria . "')";
		if ($ativo!='all')
			$q.= " and ativo=" . $ativo;
		$cs = $this->banco->consultar($q);
		$ids = '';
		foreach ($cs as $c){
			foreach ($c as $key=>$value){
				if ($ids == '')
					$ids.= $value;
				else
					$ids.= ',' . $value;
			}
		}
		return $ids;
	}
	
	/*
	* Retorna todos os ids, independente de seus outros valores
	*/
	public function todos_ids(){
		$q = "select id from sistemas";	
		$cs = $this->banco->consultar($q);
		$ids = '';
		foreach ($cs as $c)
			foreach ($c as $key=>$value){
				if ($ids == '')
					$ids.= $value;
				else
					$ids.= ',' . $value;
			}
		return $ids;
	}
	
}

?>