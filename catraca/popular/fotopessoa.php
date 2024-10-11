<?php

$nusp =   7992806;

//bibliotecas
require_once('../lib/nusoap-0.9.5/lib/nusoap.php');

//instanciando um cliente SOAP
//$clienteSoap = new nusoap_client('wsfoto_labs.wsdl', 'wsdl'); // ambiente de desenvolviment
$clienteSoap = new nusoap_client('wsfoto.wsdl', 'wsdl'); // ambiente de produção
//$clienteSoap = new nusoap_client('https://uspdigital.usp.br/wsfoto/foto?wsdl', 'wsdl'); // link direto - produção "dá pau"
$clienteSoap->loadWSDL();
$erro = $clienteSoap->getError();
if ($erro){   
  printf("%s", $erro);     
  exit;
}
if ($clienteSoap->fault) {
   echo 'Falha no cliente';
   exit;
}

//indicando usuario e senha
//$soapHeaders = array('username' => 'desenvolvimento', 'password' => 'desenvolvimento*'); // ambiente de desenvolvimento
$soapHeaders = array('username' => 'IGC', 'password' => '!#1g13ci*7'); // ambiente de produção
$clienteSoap->setHeaders($soapHeaders);

//disparando uma operacao
$retorno = $clienteSoap->call('obterFotoCartao', array('codigoPessoa' => $nusp));
if ($clienteSoap->fault) {
   echo 'Falha no cliente 2';
   exit;
}
if ($clienteSoap->getError()){   
  printf("%s", $erro);     
  exit;
}

/** salvar em disco
$f = fopen("$nusp.jpg",'w');
fwrite($f,base64_decode($retorno['fotoCartao']));
fclose($f);*/

ob_end_clean();

//redirecionando os dados binarios do jpg para o browser
header('Content-type: image/jpeg');
header("Content-Disposition: attachment; filename={$nusp}.jpg");
echo base64_decode($retorno['fotoCartao']);
?>