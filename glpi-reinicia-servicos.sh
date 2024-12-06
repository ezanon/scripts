#!/bin/bash

# Caminho para o arquivo cron.php do GLPI
GLPI_CRON="/sites-usp/atendimento/glpi/front/cron.php"

# Função para encerrar e reiniciar os serviços
reiniciar_servico_glpi() {
    local servico=$1

    # Encerra o serviço manualmente
    php $GLPI_CRON --force --action=stop --task=$servico
    echo "Tentativa de encerrar $servico em execução."

    # Executa o serviço novamente
    php $GLPI_CRON --force --task=$servico
    if [ $? -eq 0 ]; then
        echo "$servico reiniciado com sucesso."
    else
        echo "Falha ao reiniciar $servico."
    fi
}

# Reiniciar os serviços problemáticos
reiniciar_servico_glpi "mailgate"
reiniciar_servico_glpi "queuednotification"

echo "Processo de verificação e reinício concluído."

