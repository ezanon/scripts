<?php

define("DOMINIO", "http://api.igc.usp.br");

// autenticação
$auth_user = 'glpi';
$auth_pwd = 'dlskfiezjhlkdsig';
$context = stream_context_create([
    'http' => [
        'header' => 'Authorization: Basic ' . base64_encode($auth_user . ':' . $auth_pwd),
    ],
]);

$linhas = array();
$urlimg = 'https://dev2.igc.usp.br/glpi/fotospessoas/';

// ordem dos campos em cada linha:
//  login, nome, email, nusp
$cabecalho = array();
$cabecalho[] = 'Usuário';
$cabecalho[] = 'Número Administrativo';
$cabecalho[] = 'Nome';
$cabecalho[] = 'E-mails';
//$cabecalho[] = 'Imagem';
$cabecalho[] = 'Perfil padrão';
$cabecalho[] = 'Grupo';

$linhas[] = $cabecalho;

$endpoints = array( // colocar em ordem hierárquica, assim a maior será selecionada caso a pessoa pertença a mais de um grupo
    '/posgraduacao/ativos',
    '/pessoa/servidores',
    '/pessoa/docentes'   
);

foreach ($endpoints as $e){   
    $endpoint = DOMINIO . $e;
    $json = file_get_contents($endpoint, false, $context);
    $pessoas = json_decode($json, true);
    foreach($pessoas as $p){
        $linha = array();
        $login = explode('@', $p['codema']);
        $linha[] = $login[0]; //usuario
        $linha[] = $p['codpes']; // nusp = numero administrativo
        $linha[] = $p['nompes']; // nome
        $linha[] = $p['codema']; // email
        //$linha[] = $urlimg . $p['codpes'] . '.jpg'; // imagem
        $linha[] = 'Self-Service'; // perfil padrão
        $linha[] = $p['tipvinext']; // grupo = vinculo
        
        $linhas[] = $linha;
    }
    $pessoas = NULL;    
}

$handle = fopen('glpiusers.csv','w');
foreach($linhas as $l){
    fputcsv($handle, $l);
}
fclose($handle);
$linhas = NULL;




