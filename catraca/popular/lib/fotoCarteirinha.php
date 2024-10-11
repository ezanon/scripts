<?php

/**
 * Description of fotoCarteirinha
 *
 * @author erickson
 */

require_once('nusoap-0.9.5/lib/nusoap.php');

class fotoCarteirinha {
    
    public function __construct(){
        return true;
    }
    
    public function __destruct() {
        unset($this->clienteSoap);
        return true;
    }     

    /**
     * 
     * @param type $nusp
     * @return boolean
     * 
     * Se $nusp for array, realiza loop nos números
     */
    public function importar($nusp = false){
        if (!$nusp){
            return false;
        }
        $this->iniciar_soap();
        if (is_array($nusp)) {
            foreach ($nusp as $n){
                $this->nusp = $n;
                $this->importar_foto();
            }
        }
        else {
            $this->nusp = $nusp;
            $this->importar_foto();
        }
        return true;
    }
    
    /**
     * instanciando um cliente SOAP
     */
    private function iniciar_soap(){
        //$clienteSoap = new nusoap_client('wsfoto_labs.wsdl', 'wsdl'); // ambiente de desenvolviment
        $this->clienteSoap = new nusoap_client('wsfoto.wsdl', 'wsdl'); // ambiente de produção
        //$clienteSoap = new nusoap_client('https://uspdigital.usp.br/wsfoto/foto?wsdl', 'wsdl'); // link direto - produção "dá pau"
        $this->clienteSoap->loadWSDL();
        $erro = $this->clienteSoap->getError();
        if ($erro){   
          printf("Erro(1) %s", $erro);     
          exit;
        }
        if ($this->clienteSoap->fault) {
           echo 'Erro(2) Falha no cliente';
           exit;
        }
        //indicando usuario e senha
        //$soapHeaders = array('username' => 'desenvolvimento', 'password' => 'desenvolvimento*'); // ambiente de desenvolvimento
        $soapHeaders = array('username' => 'IGC', 'password' => '!#1g13ci*7'); // ambiente de produção
        $this->clienteSoap->setHeaders($soapHeaders);
        return true;
    }
    
    /**
     * importa foto dado número usp
     */
    private function importar_foto(){
        //disparando uma operacao
        $retorno = $this->clienteSoap->call('obterFotoCartao', array('codigoPessoa' => $this->nusp));
        if ($this->clienteSoap->fault) {
           echo 'Erro(3) Falha no cliente';
           exit;
        }
        $erro = $this->clienteSoap->getError();
        if ($erro){   
          printf("Erro(4) %s", $erro);     
          exit;
        }
        $f = fopen("fotos/{$this->nusp}.jpg",'w');
        fwrite($f,base64_decode($retorno['fotoCartao']));
        fclose($f);
        //ob_end_clean();
        return true;
    }
    
    /**
     * Verifica se arquivo já existe
     */
    public function existe($nusp){
        if (file_exists("fotos/{$nusp}.jpg")){
            return true;
        }
        else {
            return false;
        }
    }

}
