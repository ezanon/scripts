<?php

require_once('config.php');
require_once('lib/banco_catraca.php');
require_once('lib/banco_dblib.php');
require_once('lib/replicacaoCatraca.php');
require_once('lib/fotoCarteirinha.php');

$importartudo = true; // se true, ignora array unidadesCatraca e importa todos uspianos
$downloadtodasasfotos = false; // se true, faz download de todas as fotos novamente, senão apenas das que não tem
$primeiraUnidade = 1; // se $importartudo, começa por este id
//
// unidades para importar
$unidadesCatraca[] = '44';

// a Unidade que utiliza o equipamento
$nossaUnidade = '44';

$R = new replicacaoCatraca();

$R->popular($importartudo, $unidadesCatraca);

unset($R);

