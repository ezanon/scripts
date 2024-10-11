#!/bin/bash

update-wp-multisite-logs

# Definindo o diretório raiz do WordPress como o diretório atual
WP_PATH=$(pwd)

# Pasta para armazenar os logs
LOG_DIR="$WP_PATH/logs/update-wp-multisite"

# Verifica se a pasta de logs existe, senão, cria
if [ ! -d "$LOG_DIR" ]; then
    mkdir -p "$LOG_DIR" || { echo "Erro ao criar a pasta de logs"; exit 1; }
fi

# Nome do arquivo de log com a data da execução como prefixo
LOG_FILE="$LOG_DIR/$(date '+%Y%m%d_%H%M%S')_update-wp-multisite.log"

# Configurações
UPDATE_THEMES=false
EXCLUDE_THEMES=("treville") # Adicione aqui os nomes dos temas que deseja excluir da atualização

# Função para registrar logs
log() {
    local log_message="$1"
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $log_message" | tee -a $LOG_FILE
}

# Função para verificar o status do último comando e sair em caso de erro
check_status() {
    if [ $? -ne 0 ]; then
        log "Erro ao executar: $1"
        # Enviar notificação de erro (e-mail, Slack, etc.)
        # mail -s "Erro na atualização do WordPress" seuemail@dominio.com < $LOG_FILE
        exit 1
    fi
}

# Verificar se o script está sendo executado como root
if [ "$(id -u)" -ne 0 ]; then
    log "Este script deve ser executado como root"
    exit 1
fi

log "Iniciando atualização do WordPress Multisite"

# Troque para o diretório do WordPress
cd $WP_PATH || { log "Diretório não encontrado: $WP_PATH"; exit 1; }

# Ativar modo de manutenção
wp maintenance-mode activate --allow-root
check_status "Ativar modo de manutenção"

# Atualiza o núcleo do WordPress
log "Atualizando núcleo do WordPress"
wp core update --allow-root
check_status "Atualizar núcleo do WordPress"

# Atualiza o banco de dados após a atualização do núcleo
log "Atualizando banco de dados do núcleo"
wp core update-db --allow-root
check_status "Atualizar banco de dados do núcleo"

# Atualiza todas as traduções do núcleo
log "Atualizando traduções do núcleo"
wp language core update --allow-root
check_status "Atualizar traduções do núcleo"

if [ "$UPDATE_THEMES" = true ]; then
    log "Atualizando todos os temas, exceto os especificados"

    # Atualiza todos os temas, exceto os especificados em EXCLUDE_THEMES
    for theme in $(wp theme list --field=name --status=active,inactive --allow-root); do
        if [[ ! " ${EXCLUDE_THEMES[@]} " =~ " ${theme} " ]]; then
            wp theme update $theme --allow-root
            check_status "Atualizar tema: $theme"
        else
            log "Tema '$theme' excluído da atualização"
        fi
    done

    # Atualiza as traduções de todos os temas, exceto os especificados em EXCLUDE_THEMES
    log "Atualizando traduções de todos os temas, exceto os especificados"
    for theme in $(wp theme list --field=name --status=active,inactive --allow-root); do
        if [[ ! " ${EXCLUDE_THEMES[@]} " =~ " ${theme} " ]]; then
            wp language theme update $theme --allow-root
            check_status "Atualizar traduções do tema: $theme"
        else
            log "Traduções do tema '$theme' excluídas da atualização"
        fi
    done
else
    log "ATUALIZAÇÃO DE TEMAS FOI DESATIVADA"
fi

# Atualiza todos os plugins
log "Atualizando todos os plugins"
wp plugin update --all --allow-root
check_status "Atualizar todos os plugins"

# Atualiza as traduções de todos os plugins
log "Atualizando traduções de todos os plugins"
wp language plugin update --all --allow-root
check_status "Atualizar traduções de todos os plugins"

# Para multisite, precisamos iterar por cada site e realizar as atualizações específicas do site
# Obtém a lista de IDs dos sites
SITE_IDS=$(wp site list --field=blog_id --allow-root)

# Loop através de cada site e atualiza o banco de dados e plugins específicos do site
for SITE_ID in $SITE_IDS; do
    log "Atualizando site ID: $SITE_ID"

    # Atualiza o banco de dados do site
    wp --url=$(wp site url $SITE_ID --allow-root) core update-db --allow-root
    check_status "Atualizar banco de dados do site ID: $SITE_ID"

    # Atualiza os plugins específicos do site
    wp --url=$(wp site url $SITE_ID --allow-root) plugin update --all --allow-root
    check_status "Atualizar plugins do site ID: $SITE_ID"

    # Atualiza as traduções dos plugins específicos do site
    wp --url=$(wp site url $SITE_ID --allow-root) language plugin update --all --allow-root
    check_status "Atualizar traduções dos plugins do site ID: $SITE_ID"

    if [ "$UPDATE_THEMES" = true ]; then
        # Atualiza os temas específicos do site, exceto os especificados em EXCLUDE_THEMES
        for theme in $(wp --url=$(wp site url $SITE_ID --allow-root) theme list --field=name --status=active,inactive --allow-root); do
            if [[ ! " ${EXCLUDE_THEMES[@]} " =~ " ${theme} " ]]; then
                wp --url=$(wp site url $SITE_ID --allow-root) theme update $theme --allow-root
                check_status "Atualizar tema do site ID $SITE_ID: $theme"
            else
                log "Tema '$theme' excluído da atualização no site ID: $SITE_ID"
            fi
        done

        # Atualiza as traduções dos temas específicos do site, exceto os especificados em EXCLUDE_THEMES
        for theme in $(wp --url=$(wp site url $SITE_ID --allow-root) theme list --field=name --status=active,inactive --allow-root); do
            if [[ ! " ${EXCLUDE_THEMES[@]} " =~ " ${theme} " ]]; then
                wp --url=$(wp site url $SITE_ID --allow-root) language theme update $theme --allow-root
                check_status "Atualizar traduções do tema do site ID $SITE_ID: $theme"
            else
                log "Traduções do tema '$theme' excluídas da atualização no site ID: $SITE_ID"
            fi
        done
    fi
done

# Desativar modo de manutenção
wp maintenance-mode deactivate --allow-root
check_status "Desativar modo de manutenção"

log "Atualização completa!"

# Enviar notificação de sucesso
# mail -s "Atualização do WordPress concluída com sucesso" seuemail@dominio.com < $LOG_FILE

# Captura a saída do log para a memória
#log_output=$(cat $LOG_FILE)

# Exibe a saída do log na tela
#echo "$log_output"
