<?php

class pessoa{
	
	public $banco;
	
	public function __construct(){
		$this->banco = banco::instanciar();
		return true;
	}
	
	/*
	* Obtém informações básicas do usuário registradas no bd local
	*/
	public function get($nusp){
		$q = "select * from pessoas where id=$nusp limit 1";
		$ps = $this->banco->consultar($q);
		$aux = 0;
	    foreach ($ps as $p) {
			foreach ($p as $key=>$val) 
				$this->$key = $val;
			$aux++;
		}
		if ($aux == 0)
			return false;
		if ($this->docente == 1)
			$this->categorias.= ',DOCENTE';
		return true;		
	}
	
	/*
	* Monta menu autorizado para o usuário
	*/
	public function menu(){
		global $eh_admin;
		// se usuario é admin, mostra todos os sistemas
		if ($eh_admin){
			$sistemas = new sistemas();
			$menu_ids = $sistemas->todos_ids();
			$sistemas = NULL;
		}
		else {
			$menu_ids = $this->sistemas_autorizados();
		}
		// monta o menu com os ids obtidos
		$sys = new sistema();
		$menu_ids = explode(',',$menu_ids);
		$menu_ids = array_unique($menu_ids);
		$menu_code = '';
		foreach ($menu_ids as $menu_id){
			if ($menu_id != null)
				$sys->get_info($menu_id);
			else
				continue;
			$menu_item = $sys->nome;
			$menu_item = "<a target=_blank alt='" . $sys->descricao . "' href=" . $sys->link . " />" .$menu_item . "</a>";
			$menu_item = "<p class=menu_item>" . $menu_item . "</p>\n";
			$menu_code.= $menu_item;
		}
		if ($menu_code == '')
			$menu_code = "Você não tem acesso a nenhum sistema.";
		else {
			$menu_code = "<span class=menu_box>" . $menu_code . "</span>";
			$menu_code = "<p><b>Acesse seus sistemas:</b></p>" . $menu_code;
		}
		return $menu_code;	
	}
	
	/*
	* retorna lista com ids dos sistemas autorizados para o usuário
	*/
	public function sistemas_autorizados(){
		// adiciona sistemas autorizados pela categoria do usuario
		$cs = explode(',',$this->categorias);
		$menu_ids = '';
		$sistemas = new sistemas();
		foreach ($cs as $c){
			if ($menu_ids == '')
				$menu_ids.= $sistemas->autorizadas_por_categoria($c);
			else
				$menu_ids.= ',' . $sistemas->autorizadas_por_categoria($c);
		}
		// adiciona sistemas autorizados para seu número usp
		if ($this->sistemas != NULL)
			if ($menu_ids != '')
				$menu_ids.= ',' . $this->sistemas;
			else
				$menu_ids.= $this->sistemas;
		// adiciona sistemas publicos
		$ss = $sistemas->autorizadas_por_categoria('PUBLICO');
		if ($ss != ''){
			if ($menu_ids != '')
				$menu_ids.= ',' . $ss;
			else
				$menu_ids.= $ss;
		}
		$sistemas = NULL;
		return $menu_ids;
	}
	
	/*
	* Verifica se usuário já está cadastrado localmente
	*/
	public function cadastrado($nusp){
		$c = $this->banco->contar("pessoas","id",$nusp);
		if ($c == 1)
			return true;
		else return false;		
	}
	
	/*
	* Insere novo registro
	*/
	public function inserir(){
		$dados['id'] = $this->id;
		$dados['nome'] = $this->nome;
		$dados['email'] = $this->email;
		$dados['categorias'] = $this->categorias;
		$dados['docente'] = $this->docente;
		$dados['sistemas'] = $this->sistemas;
		$dados['checked'] = $this->checked;
		$this->banco->inserir('pessoas',$dados);
		return true;
	}
	
	/*
	* Atualiza registro
	*/
	public function alterar(){
		$dados['nome'] = $this->nome;
		$dados['email'] = $this->email;
		$dados['categorias'] = $this->categorias;
		$dados['docente'] = $this->docente;
		$dados['sistemas'] = $this->sistemas;
		$dados['checked'] = $this->checked;
		$this->banco->alterar('pessoas',$this->id,$dados);
		return true;
	}
	

	
}


?>