<?php

require_once('../config.php');
require_once('../lib/banco.php');
require_once('../lib/banco_dblib.php');
require_once('../lib/pessoas.php');
require_once('../lib/pessoa.php');
require_once('../lib/replicacao.php');

$r = new replicacao();
echo $r->replicar();
$r = NULL;

?>