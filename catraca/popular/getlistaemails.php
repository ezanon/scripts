<?php

require_once('../config.php');
require_once('../lib/banco_dblib.php');

$Unidade = 44;
$sitatl = "sitatl='A'";
$codund = "codund=$Unidade";
$tipvin = "tipvin='SERVIDOR'";
$onde = "{$tipvin} and {$sitatl} and {$codund}";

$retiradocentes = true;

$db = banco_dblib::instanciar('dblib');

//$db = new banco_dblib();

// obtém lista de nusp do desejado

$sql = "select distinct codpes from VINCULOPESSOAUSP where $onde order by codpes";
//$res = $db->listar('VINCULOPESSOAUSP', 'codpes', "$onde", '', 'codpes');
$res = $db->consultar($sql);

// obtém nomes das pessoas
$nomes = array();

foreach ($res as $r){
    $nusp = $r['codpes'];
    
    // retira docentes
    if ($retiradocentes){
        $sql = "select count(*) as num from DOCENTE where codpes=" . $nusp;
        $res3 = $db->consultar($sql);
        if ($res3[0]['num'] != 0) continue;
    }
   
    $sql = "select nompes from PESSOA where codpes=$nusp";
    $res2 = $db->consultar($sql);
    //var_dump($res2);die();
    $nomes[$nusp] = $res2[0]['nompes'];
}
asort($nomes);

// obtém emails
foreach ($nomes as $nusp => $nome){
    $sql = "select codema from EMAILPESSOA where codpes=$nusp order by numseqema";
    $res2 = $db->consultar($sql);
    echo $nusp . ',' . $nome;
    foreach ($res2 as $r){
        echo  ',' . $r['codema'];
    }
    echo "\n";
}

$db = NULL;
