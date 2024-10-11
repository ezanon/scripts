<?php

class banco_catraca {
	public static $instancia;
	protected $conexao;
	
	public static function instanciar() {
		if(!self::$instancia) {
			self::$instancia = new banco_catraca;
			self::$instancia->conectar();
		}
		
		return self::$instancia;
	}
	
	protected function conectar($driver = 'dblib') {
		global $configC;
		$this->conexao = new PDO("{$configC['driver']}:host={$configC['host']};dbname={$configC['database']}", $configC['user'], $configC['pass']);
		$this->conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	public function executar($sql, $dados = null) {
		//echo "<pre>$sql</pre>";
		$statement = $this->conexao->prepare($sql);
		$statement->execute($dados);
	}
	
	public function consultar($sql, $dados = null) {
		$statement = $this->conexao->prepare($sql);
		$statement->execute($dados);
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	/*
	 * Função que conta o número de ocorrencias de $campo=$valor em $tabela
	 */
	public function contar($tabela,$campo,$valor){
		$q = "select count(*) as quantidade from $tabela where $campo=$valor";
		$res = $this->consultar($q);
		foreach ($res as $r){
			$num = $r['quantidade'];
			break;
		}
		return $num;
	}

	public function inserir($tabela, $dados) {
		foreach($dados as $coluna => $valor) {
			$colunas[] = "`$coluna`";
			$substitutos[] = "?";
			$valores[] = $valor;
		}

		$colunas = implode(", ", $colunas);
		$substitutos = implode(", ", $substitutos);

		$query = "INSERT INTO `$tabela` ($colunas) VALUES ($substitutos)";

		$this->executar($query, $valores);
		
		// trecho inserido para obter o id do item inserido
		$id = $this->conexao->lastInsertId();
		return $id;
		
		
	}

	public function alterar($tabela, $id, $dados) {
		foreach($dados as $coluna => $valor) {
			$set[] = "`$coluna` = ?";
			$valores[] = $valor;
		}
		
		$valores[] = $id;

		$set = implode(", ", $set);

		$query = "UPDATE `$tabela` SET $set WHERE id = ?";
		
		$this->executar($query, $valores);
	}

	public function remover($tabela, $id) {
		$query = "DELETE FROM `$tabela`";

		if(!empty($id)) {
			$query .= " WHERE id = ?";
		}

		$this->executar($query, array($id));
	}

	public function listar($tabela, $campos = '*', $onde = null, $filtro = null, $ordem = null, $limite = null) {
		$query = "SELECT $campos FROM `$tabela`";

		if(!empty($onde)) {
			$query .= " WHERE $onde";
		}

		if(!empty($filtro)) {
			$query .= " LIKE $filtro";
		}

		if(!empty($ordem)) {
			$query .= " ORDER BY $ordem";
		}

		if(!empty($limite)) {
			$query .= " LIMIT $limite";
		}
		return $this->consultar($query);
	}

	public function ver($tabela, $campos, $onde) {
		$query = "SELECT $campos FROM `$tabela`";

		if(!empty($onde)) {
			$query .= " WHERE $onde";
		}
		
		return $this->consultar($query);
	}

}
