<?php

$maxDia = 20;

$maxMes = date('n', strtotime( "-3 month" ) ); 
$maxAno = date('Y', strtotime( "-3 month" ) ); 

//$maxMes = date('n');
//$maxAno = date('Y');

$maxData = mktime(23,59,59,$maxMes,$maxDia,$maxAno);

require_once '../config.php';
require_once '../banco_catraca.php';

$cabecalho = array('COLEICOD','CRACICOD', 'CRACA60CODBAR','MOVID',
    'MOVIA2STATUS','MOVIA60IMAGEMCFT','MOVICENTSAI','VISIICOD',
    'MOVICORIGEM','MOVICCONSIDERACONTAGEM','MOVICCRACHAPROVISORIO');

$banco = banco_catraca::instanciar('dblib');

for ($year = 2015; $year <= $maxAno; $year++){
for ($month = 1; $month <= 12; $month++){
    
    if ($year > $maxAno) continue;
    if (($year==$maxAno) and ($month>$maxMes)) continue;
    
    $arquivo = 'arquivo/' . $year . '-' . sprintf('%02d', $month) . '.csv';
    
    if (file_exists($arquivo)) continue;
    
    $handle = fopen($arquivo, 'w');
    fputcsv($handle,$cabecalho,',','"');
    
    $sql = "select * from MOVIMENTO where YEAR(MOVID)=$year and MONTH(MOVID)=$month";
    $res = $banco->consultar($sql);
    
    // gravar em csv
    $counter = 0;
    foreach ($res as $r){
        fputcsv($handle,$r,',','"');
        $counter++;
    }
    
    fclose($handle);
    
    echo "$year-$month : $counter registros \n";
    
    // deletar registros
    $sql = "delete from MOVIMENTO where YEAR(MOVID)=$year and MONTH(MOVID)=$month";
    $res = $banco->consultar($sql);
    
}}








