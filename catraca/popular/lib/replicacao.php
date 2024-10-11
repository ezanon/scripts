<?php

class replicacao {
	
	public $dblib;
	public $mysql;
	public $ativos = array();
        public $ativos_adicionais = array();
	
	public function __construct(){
		$this->dblib = banco_dblib::instanciar('dblib');
		$this->mysql = banco::instanciar('mysql');
		return true;
	}
	
	/*
	* Importa novos usuários
	* Atualiza usuários atuais
	* Remove usuários
	* sybase -> mysql
	*/
	public function replicar(){
		// zera flag de checado nos registros dos usuários
		$ps = new pessoas();
		$ps->checked(0);
		// obtem lista a ser importada
		$this->listar_usuarios();
                // obtem lista adicional a ser importada
                $this->read_adicional_igc_users();
		// obtem array com sistemas autorizados
		$this->get_permissoes_individuais();
		// loop sobre a lista a ser importada
		foreach ($this->ativos as $nusp){
			// obtem dados da pessoa;
			if (!$this->importar_pessoa($nusp)) // não deu certo a importação, vai pro próximo
				continue;
			// atualiza registro
			$p = new pessoa();
			
			if ($p->cadastrado($nusp)){
				$p->get($nusp);
				$p->nome = @$this->pessoa['nome'];
				$p->email = @$this->pessoa['email'];
				$p->categorias = $this->pessoa['categorias'];
				$p->sistemas = $this->pessoa['sistemas'];
				$p->docente = $this->pessoa['docente'];
				$p->checked = 1;
				$p->alterar();
			}
			// insere novo registro
			else {
				$p->id = $nusp;
				$p->nome = @$this->pessoa['nome'];
				$p->email = @$this->pessoa['email'];
				$p->categorias = $this->pessoa['categorias'];
				$p->sistemas = $this->pessoa['sistemas'];
				$p->docente = $this->pessoa['docente'];
				$p->checked = 1;
				$p->inserir();
			}
			$p = NULL;
		}
		// deleta os não checados, pois já não tem vinculo ativo
////// rever		//$ps->remover_unchecked();
		return "Replicação finalizada.\n";			
	}
	
	
	/*
	* Monta lista de usuários a serem cadastrados ou atualizados
	*/
	private function listar_usuarios(){
		global $vinculos, $unidades;
		// obtém números usp de quem tem codição ativo em qualquer tipo de vínculo
		$q = "select distinct codpes from VINCULOPESSOAUSP where sitatl='A' and (";
		$i = 0;
		foreach ($vinculos as $vinculo){
			if ($i > 0)
				$q.= " or tipvin='$vinculo'";
			else
				$q.= "tipvin='$vinculo'";
			$i++;
		}
		$q.= ") and (";
		$i = 0;
		foreach ($unidades as $unidade){
			if ($i > 0)
				$q.= " or (codund=$unidade or codclg=$unidade or codfusclgund=$unidade)";
			else
				$q.= "(codund=$unidade or codclg=$unidade or codfusclgund=$unidade)";
			$i++;	
		}
		$q.= ") order by codpes";
		$ps = $this->dblib->consultar($q);
		$this->ativos = array();
		foreach ($ps as $p)
			$this->ativos[] = $p['codpes'];
		return true;		
	}
	
	/*
	* Obtém dados da replicação de um usuário
	*/
	private function importar_pessoa($nusp){
            global $vinculos,$unidades;
            $this->pessoa = array();
            // nusp
            $this->pessoa['id'] = $nusp;
            // categorias ativas divididas por vírgula
            $categorias = '';
            if (in_array($nusp, $this->ativos_adicionais)) // se é pessoa adicional, não constará como ativo
                $q = "select tipvin from VINCULOPESSOAUSP where codpes=" . $nusp;    
            else {
                $q = "select tipvin from VINCULOPESSOAUSP where sitatl='A' and codpes=" . $nusp . " and (";
                $i = 0;
                foreach ($unidades as $unidade) {
                    if ($i > 0)
                        $q.= " and (codund=$unidade or codclg=$unidade or codfusclgund=$unidade)";
                    else
                        $q.= "(codund=$unidade or codclg=$unidade or codfusclgund=$unidade)";
                    $i++;
                }
                $q.= ")";
            }
            $ps = $this->dblib->consultar($q);
            foreach ($ps as $p){
                    if ($categorias != '')
                            $categorias.= ',';
                    $c = trim($p['tipvin']);
                    if (in_array($c, $vinculos)) {
                            $categorias.= $c;
                    }
                    if (in_array($nusp, $this->ativos_adicionais)){
                        $categorias = 'SERVIDOR';
                        break; // somente uma categoria
                    }
            }
            $this->pessoa['categorias'] = $categorias;
            // nome
            $q = "select nompes from PESSOA where codpes=" . $nusp;
            $ps = $this->dblib->consultar($q);
            foreach ($ps as $p){
                    $this->pessoa['nome'] = $p['nompes'];
                    break;
            }
            // email
            $q = "select codema from EMAILPESSOA where stamtr='S' and codpes=" . $nusp;
            $ps = $this->dblib->consultar($q);
            foreach ($ps as $p){
                    $this->pessoa['email'] = $p['codema'];
                    break;
            }
            // verifica se é servidor docente
            $q = "select count(*) as num from DOCENTE where codpes=" . $nusp;
            $cc = $this->dblib->consultar($q);
            foreach ($cc as $c){
                    if ( ($c['num'] >= 1) and (substr_count($this->pessoa['categorias'],'SERVIDOR')>0 ) )
                            $this->pessoa['docente'] = 1;
                    else 
                            $this->pessoa['docente'] = 0;
                    break;
            }
            // sistemas liberados pelo número usp
            $this->pessoa['sistemas'] = $this->permissoes[$nusp];

            return true;
	}
	
	/*
	* Obtem as permissões individuais dos sistemas
	*/
	public function get_permissoes_individuais(){
		//require_once("sistemas/permissoes.php");
		
		$pis = array();
		$perm = $this->read_perm_files();
		
		foreach ($perm as $id_sys=>$pp){
			foreach ($pp as $nusp){
				$pis[$nusp][] = $id_sys;
			}
		}	
		// cria array de permissoes
		$this->permissoes = array();
		foreach ($this->ativos as $nusp){
			$this->permissoes[$nusp] = '';
		}
		// transforma o array em string separada por vírgulas
		foreach ($pis as $nusp=>$pi)
			foreach ($pi as $id_sys){
				if ($this->permissoes[$nusp] == '')
					$this->permissoes[$nusp] = $id_sys;	
				else
					$this->permissoes[$nusp].= ',' . $id_sys;	
			}
		return true;
	}
	
	/*
	* LÊ arquivos de permissões e monta array com as mesmas
	*/
	private function read_perm_files(){
		$perm = array();
//		echo getcwd() . " <- atual \n";
		$opendir = '../permissoes';
                $opendir = '/sites/intrageo/www/permissoes';
		clearstatcache();
		// abre a pasta para leitura dos arquivos
		if ($handle = opendir($opendir)) {
			// loop sobre os arquivos
			while (false !== ($file = readdir($handle))) { 
				// verifica se extensao é txt, que sao os arquivos com as permissoes
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				if ($ext != "txt")
					continue;
				// abre o arquivo pra leitura
				$file = $opendir . '/' . $file;
				$h = fopen($file,'r');
				$linha = 0;
				// loop sobre as linhas
				while (($buffer = fgets($h)) !== false) {
					$linha++;
					if ($linha == 1) { // id do sistema
						$p = explode('#',$buffer);
						$id = $p[0];
						$id = trim($id);
						continue;
					}
					// monta array com as permissoes do sistema
					$p = explode('#',$buffer);
					$nusp = $p[0];
					$nusp = trim($nusp);
					if ($nusp == '')
						continue;
					$perm[$id][] = $nusp;
				}
				fclose($h);
			}
			closedir($handle);
			return $perm;
		}
	}
	
        /*
	* LÊ arquivo com números usp adicionais a serem importados
         * e os coloca na propriedade ativos[]
        * Em geral, docentes aposentados, ou que trocaram de unidade
        * Mas que estão presentes na base de dados
	*/
	private function read_adicional_igc_users(){
            $perm = array();
            $file = 'nusps_a_replicar_igc.txt';
            $f = fopen($file, 'r') ;
            while (($buffer = fgets($f)) !== false) {
                $n = explode('#',$buffer);
                $nusp = intval(trim($n[0]));
                if (!is_int($nusp)){
                    continue;
                }
                $this->ativos[] = $nusp;
                $this->ativos_adicionais[] = $nusp;
            }
            fclose($f);
            return true;
	}
	
	
}

?>