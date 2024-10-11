<?php

/* 
 * Script para utilização no servidor de compartilhamento de arquivos
 * para download das fotos 3x4 para utilização no servidor de catracas
 */

$url = "http://www.igc.usp.br:8080/intrageo/scripts/fotos/";
$file = "listafotos.txt";
$pasta = 'fotos/';
$extensao = '.jpg';
$baixartudo = false;

$handle = @fopen($file,'r');

if ($handle) {
    while (!feof($handle)) {
        $buffer = fgets($handle);
        $file = explode('.', $buffer);
        $filename = $file[0] . $extensao;
        if ((!is_file($pasta . $filename)) or ($baixartudo)){
           $cmd = "find $pasta -type f -size 0 -delete"; 
           shell_exec($cmd);
           $cmd = "wget -q -t 5 -O " . $pasta . $filename . " " . $url . $filename; 
           shell_exec($cmd);
        }
    }
    fclose($handle);
}


