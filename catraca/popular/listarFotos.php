<?php

/* 
 * Este script retorna a lista de arquivos de uma determinada extensão (var $extensao) da pasta em que está
 */

header('Content-Type: text/plain');

$extensao = 'jpg';

class arquivos {
    
    public $extensao;
    
    public function __construct($extensao) {
        $this->extensao = $extensao;
        return true;
    }

    public function __toString() {
        return $this->listar($this->extensao);
    }

    /**
     * Lista arquivos com opção de deleção
     */
    private function listar($extensao) {
        $files = array();
        $pasta = './';
        // Percorre todos os arquivos obtendo os dados
        if (is_dir($pasta)) {
            $d = dir($pasta);
            while (false !== ($file = $d->read())) {
                // Se o arquivo não é este arquivo, e não começa com "." ou "~"
                // e não termina em LCK, então guarde-o para exibição futura.
                if (($file{0} != '.') && 
                    ($file{0} != '~') &&
                    (substr($file, -3) != 'LCK') &&
                    ($file != basename($_SERVER['PHP_SELF'])) &&
                    ((substr($file, -3) == $extensao))) {
                    // Guarda o nome do arquivo e dados completos de uma chamada
                    // à stat()
                    $files[] = $file;
                }
            }
            // Fecha o diretório
            $d->close();
        }
        $saida = '';
        // Imprime os nomes dos arquivos
        foreach ($files as $file){
            $saida.= $file . "\n";
        }
        return $saida;
    }
    
}

$f = new arquivos($extensao);
echo $f;





