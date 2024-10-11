<?php

/**
 * Description of replicacaoCatraca
 *
 * @author erickson
 */

require_once 'lib/banco.php';

class replicacaoCatraca {
    
    public $dblib;
    public $dbcatraca;
    public $mysql;
    private $logfile;

    public function __construct(){
        $this->dblib = banco_dblib::instanciar('dblib');
        $this->mysql = banco::instanciar('mysql');
        $this->dbcatraca = banco_catraca::instanciar('dblib');
        $filename = 'catraca-' . date('d') . '.log';
        //$filename = 'catraca-' . date('d-H-i-s') . '.log';
        $symlink = 'lastlog.log';
        $this->logfile = fopen($filename, 'w');
        unlink($symlink);
        symlink($filename, $symlink);
        $this->log("REPLICA INICIADA EM " . date('d-m-Y H-i-s'));
        $this->foto = new fotoCarteirinha();
        return true;
    }
    
    public function __destruct() {
        $this->log("REPLICA FINALIZADA EM " . date('d-m-Y H-i-s') . "\n");
        fclose($this->logfile);
        unset($this->dblib);
        unset($this->dbcatraca);
        unset($this->foto);
        return true;
    }
    
    /*
     *  $importatudo
     *  Se true, importa todos os ativos
     *  Se false, importa as unidades do array $unidades
     */
    public function popular($importartudo,$unidades=false){
        global $primeiraUnidade;
        if ($importartudo){
            $q = "select distinct codoriclgund as codund
                    from CATR_CRACHA 
                    order by codoriclgund";
            $res = $this->dblib->consultar($q);
            $unidades = array();
            foreach ($res as $row){
                $unidades[] = $row['codund'];
            }
        }
        // loop nas unidades
        foreach ($unidades as $unidade){
            if ($unidade<$primeiraUnidade) continue;
            $this->get_unidade($unidade);
            $this->log("\nUNIDADE {$this->codund} {$this->sglund} {$this->nomund}");
            $this->get_pessoas($unidade);
            $this->get_categorias($unidade);
            $this->sincronizar();
        }
        //$this->consistencia_intranet();
        return true;
    }
    
    /**
     * verifica nomes na tabela intrageo para inclusão na catraca
     *   útil para pessoas novas no instituto, pois demorarão a ter carteirinha,
     *   consequentemente não estarão na tabela CATR_CRACHA
     *   assim poderão obter carteirinha provisória
     */
    /** FUNÇÃO INATIVA **/
    private function consistencia_intranet($unidade = 44){
        $exclusoes[] = 5860340; // nusps para não realizar a conscistencia
        $q = "select id,nome,categorias from pessoas where checked = 1";
        $res = $this->mysql->consultar($q);
 
        // monta o array no mesmo formato utilizando pela função get_pessoas()        
        $i = 0;
        $p = array();
        foreach ($res as $r){
            if ($this->pessoa_cadastrada($r['id'])) 
                continue;
            if ($r['id'] == 5860340)
                continue;
            $p[$i]['codpescra'] = $r['id'];
            $p[$i]['sitpescra'] = 'A';
            // trata  categorias, pois pode haver mais de uma
            // só podemos enviar uma, então será escolhida uma
            // primeiro servidor, depois alunopos, 
            $categorias = explode(',',$r['categorias']);
            $c = '';
            foreach ($categorias as $cat){
                if ($cat == "SERVIDOR"){
                    $c = $cat;
                }
                elseif (($cat != 'SERVIDOR') and ($c != 'SERVIDOR')){
                    $c = $cat;
                }
            }
            $p[$i]['tipvinaux'] = $c;
            $p[$i]['nompescra'] = $r['nome'];
            /**
             * TO DO
             * Verifica se existe para definir o código de barras
             * Quando pronto, tirar verificação pessoa_cadastrada acima
             * 
             */
            $p[$i]['numserchi'] = $r['id'];
            $p[$i]['nomorg'] = 'IGc';
            $i++;   
        }
        var_dump($p);
        $this->pessoas = null;
        $this->pessoas = $p;
        $this->get_unidade($unidade);
        $this->log("\nConsistencia Intranet UNIDADE {$this->codund} {$this->sglund} {$this->nomund}");
        $this->get_categorias($unidade);
        $this->sincronizar();
        return true;
    }


    /**
     * Obtém e cadastra as categorias
     * Armazena em array por Unidade
     * O índice do array é o nome da Categoria, pois é o dado inicial
     * O valor na posição é o id na tabela (campo CATEICOD)
     */
    private function get_categorias($unidade){
        $this->categorias = array();
        $q = "select distinct tipvinaux from CATR_CRACHA where codoriclgund=" . $unidade;
        $res = $this->dblib->consultar($q);
        foreach ($res as $r){
            $tipvin = $r['tipvinaux'];
            $this->get_categoria($tipvin);
        }
        return true;        
    }
    
    /**
     * Obtem dados da categoria
     * Se não existe, cadastra
     */
    private function get_categoria($categoria){
        $categoria = trim($categoria);
        if($this->dbcatraca->contar('CATEGORIA','CATEA60DESCR',"'{$categoria}'") > 0){
            $q = "select CATEICOD from CATEGORIA where CATEA60DESCR='{$categoria}'";
            $res = $this->dbcatraca->consultar($q);
            $this->categorias[$categoria] = $res[0]['CATEICOD'];
        }
        else {
            // obtem maior índice
            $q = "select max(CATEICOD) as maxid from CATEGORIA";
            $res = $this->dbcatraca->consultar($q);
            $idcategoria = $res[0]['maxid'] + 1;
            
            $dados['CATEA60DESCR'] = $categoria;
            $dados['CATECSOLICBLOQCRACHA'] = 'N';
            
            $q = "insert into CATEGORIA "
                    . "(CATEICOD,CATEA60DESCR,CATECSOLICBLOQCRACHA) "
                    . "values ($idcategoria,'{$dados['CATEA60DESCR']}','{$dados['CATECSOLICBLOQCRACHA']}')";
            $this->dbcatraca->executar($q);
            
            $q = "select CATEICOD from CATEGORIA where CATEA60DESCR='{$categoria}'";
            $res = $this->dbcatraca->consultar($q);
            $this->categorias['$categoria'] = $res[0]['CATEICOD'];
            
            /*$idcategoria = $this->dbcatraca->inserir('CATEGORIA',$dados);  
            $this->categorias['$categoria'] = $idcategoria;*/
            
            $this->log("CATEGORIA CADASTRADA: $idcategoria $categoria");
        }
        return true;
    }


    /*
     * Obtém dados da Unidade 
     * $unidade = código da unidade
     */
    private function get_unidade($unidade){
        // primeiro verifica se a Unidade está na tabela replica.Unidade
        // se está, usa da replica.UNIDADE que são mais completos
        if ($this->dblib->contar('UNIDADE','codund',$unidade) > 0){
            $q = "select distinct C.codoriclgund as codund,U.nomund as nomund,U.sglund as sglund
                    from CATR_CRACHA as C,UNIDADE as U 
                    where C.codoriclgund = {$unidade} and C.codoriclgund=U.codund
                    order by codoriclgund";    
        }
        // se não está, usa os dados de replica.CATR_CRACHA mesmo
        else {
            $q = "select distinct codoriclgund as codund, nomorg as nomund, nomorg as sglund 
                    from CATR_CRACHA 
                    where codoriclgund = {$unidade}
                    order by codoriclgund";    
        }
        $res = $this->dblib->consultar($q);
        $this->codund = $res[0]['codund'];
        $this->nomund = $res[0]['nomund'];
        $this->sglund = $res[0]['sglund'];
        $this->cadastra_unidade();
        return true;        
    }
    
    /**
     * Cadastra Unidade no sistema de catraca
     */
    private function cadastra_unidade(){
        if ($this->unidade_existe()){
            return true;
        }
        else {
            $dados['EMPRICOD'] = $this->codund;
            $dados['EMPRA60RAZAOSOCIAL'] = trim($this->nomund);
            $dados['EMPRA60NOMEFANTAS'] = trim($this->sglund);
            $q = "insert into EMPRESA "
                    . "(EMPRICOD,EMPRA60RAZAOSOCIAL,EMPRA60NOMEFANTAS,EMPRCFISJURID) "
                    . "values ({$this->codund},'{$dados['EMPRA60RAZAOSOCIAL']}','{$dados['EMPRA60NOMEFANTAS']}','J')";
            $this->dbcatraca->executar($q);
            //$this->dbcatraca->inserir('EMPRESA',$dados);
            $this->log("UNIDADE CADASTRADA: {$this->codund} {$this->sglund} {$this->nomund}");
        }
        return true;
    }
    
    /**
     * Verifica se a unidade já é cadastrada
     */
    private function unidade_existe(){
        if($this->dbcatraca->contar('EMPRESA','EMPRICOD',$this->codund) > 0){
            return true;
        }
        else {
            return false;
        }
    }
    
    /**
     * Obtém pessoas da Unidade
     */
    private function get_pessoas($unidade){
        $this->pessoas = null;
        $q = "select codpescra, sitpescra, tipvinaux, nompescra, numserchi, nomorg "
                . "from replica.dbo.CATR_CRACHA "
                . "where codoriclgund=" . $unidade 
                . " order by codpescra";
        echo $q . "\n";
        $res = $this->dblib->consultar($q);
        $this->pessoas = $res;
        return true;
    }
    
    /**
     * Sincroniza os bancos de dados
     */
    private function sincronizar(){
        foreach ($this->pessoas as $pessoa){
            $pessoa['codpescra'] = trim($pessoa['codpescra']);
            //if ($pessoa['codpescra']!=2962373) continue;
            $pessoa_cadastrada = $this->pessoa_cadastrada($pessoa['codpescra']);
            if (($pessoa_cadastrada) and ($pessoa['sitpescra']=='A')){
                $this->atualizar_pessoa($pessoa);
                $downloadfoto = true;
            }
            elseif (($pessoa_cadastrada) and ($pessoa['sitpescra']=='D')) {
                $this->descadastrar_pessoa($pessoa);
                $downloadfoto = false;
            }
            elseif ((!$pessoa_cadastrada) and ($pessoa['sitpescra']=='A')){
                $this->cadastrar_pessoa($pessoa);
                $downloadfoto = true;
            }
            elseif ((!$pessoa_cadastrada) and ($pessoa['sitpescra']=='D')){
                $downloadfoto = false;
                return true;
            }
            if ($downloadfoto){
                //obtém foto
                $this->get_fotocarteirinha($pessoa['codpescra']);
            }
        }
        //$this->validacao_reversa();
        return true;
    }
    
    /**
     * Validação reversa
     * Verifica se os cadastrados no banco de acesso, também estão na base da usp
     * E se são de Unidades autorizadas
     * Se não, bloqueia
     */
    private function validacao_reversa(){
        global $unidadesCatraca, $importartudo, $nossaUnidade;
        $maxid = 500;
        // EMPRICOD<500 pois 500 é o id a partir do qual são gerados novos registros manualmente
        // os da USP são menores
        $q = "select CRACICOD,CRACA60NOME,EMPRICOD from CRACHA where EMPRICOD<{$maxid} and convert(char(23),CRACDFIM,126) > '" . date("Y-m-d H:i:s.000") . "'";
        $res = $this->dbcatraca->consultar($q);
        foreach ($res as $row){
            $nusp = $row['CRACICOD'];
            $nompes = $row['CRACA60NOME'];
            $codund = $row['EMPRICOD'];
            $nomund = $row['EMPRICOD'];
            $p['nomorg'] = $nomund;
            $p['codpescra'] = $nusp;
            $p['nompescra'] = $nompes;
        // verifica se é válido na tabela CATRACA-GEOLOGIA.CRACHA
        // entao verifica se é válido também na tabela replica.CATR_CRACHA
        // e
        // verifica se pertence a Unidades autorizadas no array unidadesCatraca se importatudo é false
            $q = "select count(*) as n from CATR_CRACHA where sitpescra='A' and codpescra=" . $nusp;
            $res2 = $this->dblib->consultar($q);
            $n = $res2[0]['n'];
            
            // se pessoa não é de unidade autorizada, descadastra
            if ((!$importartudo) and (!in_array($codund, $unidadesCatraca))){
                $this->descadastrar_pessoa($p);
                continue;
            }
            // se n > 0, pessoa ainda é ativa
            if ($n > 0){
                continue;
            }
            // se não, se não é do igc (44), desliga
            if ($codund != $nossaUnidade){
                $this->descadastrar_pessoa($p);
            }
            // se é da nossa unidade, verifica se é ativo
            else {
                $q = "select count(*) as n from VINCULOPESSOAUSP where codpes={$nusp} and sitatl='A'";
                $res3 = $this->dblib->consultar($q);
                $n = $res3[0]['n'];
                if ($n == 0){
                    $this->descadastrar_pessoa($p);
                }
                else {
                    $this->anula_cartao($p);
                }
            }
            
            // trecho antigo
            /*if (($res[0]['n'] == 0) or ((!$importartudo) and (!in_array($codund, $unidadesCatraca)))){
                $this->descadastrar_pessoa($p);
            }*/
        }
        return true;
    }
    
    /**
     * Verifica se a pessoa já é cadastrada no sistema de acesso
     */
    private function pessoa_cadastrada($id){
        if($this->dbcatraca->contar('CRACHA','CRACICOD',$id) > 0){
            return true;
        }
        else{
            return false;
        }
    }
    
    /**
     * Atualiza dados da pessoa
     */
    private function atualizar_pessoa($p){
        $CRACDFIM = date("Ymd", mktime(0, 0, 0, 12, 31, 2099));
        $CRACDFIM = 20991231;
        $CRACDFIM = date("Ymd", strtotime('+6 months'));
        $CATEICOD = $this->categorias[$p['tipvinaux']];
        $p['codpescra'] = $p['codpescra'];
        $p['nompescra'] = str_replace("'", "''", $p['nompescra']);
        $p['numserchi'] = sprintf("%010s",trim($p['numserchi']));
        $foto = $p['codpescra'] . '.jpg';
        $q = "update CRACHA set "
                . "CRACA60NOME='{$p['nompescra']}',"
                . "CRACA60CODBAR='" . str_replace("'","",$p['numserchi']) . "',"
                . "CRACDFIM='$CRACDFIM'," 
                . "EMPRICOD={$this->codund},"
                . "CATEICOD={$CATEICOD},"
                . "CRACA60FOTO='$foto' "      
                . "where CRACICOD = " . $p['codpescra'];
        if ($p['codpescra']==10333077) echo $q;
//	echo $q . "\n";
        $this->dbcatraca->executar($q);
        $this->log("ATUALIZADO: {$p['nomorg']} {$p['codpescra']} {$p['nompescra']}");
        return true;
    }
    
    /**
     * Descadastra pessoa
     */
    private function descadastrar_pessoa($p){
        return true;
        $p['codpescra'] = $p['codpescra'];
        $CRACDFIM = date("Y-m-d 00:00:00.000");
        $q = "update CRACHA set "
                . "CRACDFIM='$CRACDFIM' "      
                . "where CRACICOD = " . $p['codpescra'];
        $this->dbcatraca->executar($q);
        $this->log("DESLIGADO: {$p['nomorg']} {$p['codpescra']} {$p['nompescra']}");
        return true;
    }
    
    /**
     * Cadastra dados da pessoa
     */
    private function cadastrar_pessoa($p){
        $dados['CRACICOD'] = $p['codpescra']; //sprintf("%010s",$p['codpescra']);
        $dados['CRACA60NOME'] = str_replace("'", "''", $p['nompescra']); 
        $dados['CRACA60CODBAR'] = sprintf("%010s",trim($p['numserchi']));
        $dados['CRACDINI'] = date("Y-m-d H:i:s.000"); //, mktime($now['hours'],$now['minutes'],$now['seconds'],$now['month'],$now['day'],$now['year']));
        $dados['CRACDINI'] = date("Ymd");
        $dados['CRACDFIM'] = date("Ymd", mktime(0, 0, 0, 12, 31, 2099));
        $dados['CRACDFIM'] = 20991231;
        $dados['EMPRICOD'] = $this->codund;
        $dados['CATEICOD'] = $this->categorias[$p['tipvinaux']];
        if ($dados['EMPRICOD'] == 44){
            $dados['DEPTICOD'] = 1; // se é do IGc (44), cadastra departamento para habilitar visitas: 1 é IGC todo.
        }
        else{
            $dados['DEPTICOD'] = 0;
        }
        $dados['CRACA60FOTO'] = $dados['CRACICOD'] . '.jpg';
        $dados['NIVEICOD'] = 1; // jornada FULLTIME id = 1
        // $dados fixos iniciais
        $dados['CRACCVISITANTE'] = 'N';
        $dados['CRACCOCUPADO'] = 'N';
        $dados['CRACCFERIADO'] = 'N';
        $dados['CRACCBLOQUEADO'] = 'N';
        $dados['CRACCREENTRADA'] = 'S';
        $dados['CRACCMESTRE'] = 'N';
        $dados['CRACCAPENASBIO'] = 'N';
        $dados['CRACCVEICULO'] = 'N';
        $dados['CRACCVALIDAREQUISITO'] = 'N';
        $dados['CRACCVIATECLADO'] = 'N';
        $dados['CRACCURNARECOLHE'] = 'N';

        $q = "insert into CRACHA "
                . "(CRACICOD,CRACA60NOME,CRACA60CODBAR,CRACDINI,CRACDFIM,EMPRICOD,CATEICOD,DEPTICOD,CRACA60FOTO,NIVEICOD,"
                . " CRACCVISITANTE,CRACCOCUPADO,CRACCFERIADO,CRACCBLOQUEADO,CRACCREENTRADA,CRACCMESTRE,CRACCAPENASBIO,CRACCVEICULO,CRACCVALIDAREQUISITO,CRACCVIATECLADO,CRACCURNARECOLHE) "
                . " values "
                . "({$dados['CRACICOD']},'{$dados['CRACA60NOME']}','{$dados['CRACA60CODBAR']}',"
                . "'{$dados['CRACDINI']}','{$dados['CRACDFIM']}',{$dados['EMPRICOD']},{$dados['CATEICOD']},{$dados['DEPTICOD']},'{$dados['CRACA60FOTO']}',{$dados['NIVEICOD']},"
                . "'{$dados['CRACCVISITANTE']}', '{$dados['CRACCOCUPADO']}','{$dados['CRACCFERIADO']}','{$dados['CRACCBLOQUEADO']}','{$dados['CRACCREENTRADA']}','{$dados['CRACCMESTRE']}','{$dados['CRACCAPENASBIO']}','{$dados['CRACCVEICULO']}','{$dados['CRACCVALIDAREQUISITO']}','{$dados['CRACCVIATECLADO']}','{$dados['CRACCURNARECOLHE']}')";
        echo $q . "\n";
        $this->dbcatraca->executar($q);
        //$this->dbcatraca->inserir('CRACHA',$dados);
        
        // altera valor de DEPTICOD para NULL onde DEPTICOD = 0
        $q = "update CRACHA set DEPTICOD = replace(DEPTICOD,0,null) where CRACICOD=" . $p['codpescra'];
        $this->dbcatraca->executar($q);
        // log
        $this->log("INSERIDO: {$p['nomorg']} {$p['codpescra']} {$p['nompescra']}");
        return true;
    }
    
    /**
     * Obtém foto carteirinha
     */
    private function get_fotocarteirinha($nusp){
        global $downloadtodasasfotos;
        if ((!$this->foto->existe($nusp)) or ($downloadtodasasfotos)){
            $this->foto->importar($nusp);
        }
        return true;
    }
    
    /**
     * Obtém dados da pessoa no db Catraca
     * PARA TESTE
     */
    public function pessoa_catraca($id){
        $q = "select * from CRACHA where CRACICOD=" . $id;
        $res = $this->dbcatraca->consultar($q);
        return $res;
    }
    
    /**
     * Realiza logs
     */
    private function log($log){
        fwrite($this->logfile,$log . "\n");
        return true;
    }
    
    /**
     * Anula código de barras, pois cartão está desativado e a pessoa ainda não tem outro
     */
    private function anula_cartao($pessoa){
        return true;
    }
        
}
