#!/bin/bash

WP_PATH=$(pwd)
LOG_DIR="$WP_PATH/logs/update-wp-multisite"

if [ ! -d "$LOG_DIR" ]; then
    mkdir -p "$LOG_DIR" || { echo "Erro ao criar a pasta de logs"; exit 1; }
fi

LOG_FILE="$LOG_DIR/$(date '+%Y%m%d_%H%M%S')_update-wp-multisite.log"

UPDATE_THEMES=false
EXCLUDE_THEMES=("treville")

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

check_status() {
    if [ $? -ne 0 ]; then
        log "Erro ao executar: $1"
        exit 1
    fi
}

if [ "$(id -u)" -ne 0 ]; then
    log "Este script deve ser executado como root"
    exit 1
fi

disable_plugin_auto_updates_site() {
    local site_url=$1
    log "Desativando autoatualizações de plugins no site: $site_url"
    for plugin in $(sudo -u www-data wp --url="$site_url" plugin list --field=name ); do
        sudo -u www-data wp --url="$site_url" plugin auto-updates disable "$plugin"  2>&1 | tee -a "$LOG_FILE"
    done
}

log "========== INÍCIO DA ATUALIZAÇÃO WORDPRESS MULTISITE =========="

cd "$WP_PATH" || { log "Diretório não encontrado: $WP_PATH"; exit 1; }

log "Estado inicial: listagem de plugins"
sudo -u www-data wp plugin list  2>&1 | tee -a "$LOG_FILE"

log "Ativando modo de manutenção"
sudo -u www-data wp maintenance-mode activate  2>&1 | tee -a "$LOG_FILE"
check_status "Ativar modo de manutenção"

log "Atualizando núcleo do WordPress"
sudo -u www-data wp core update  2>&1 | tee -a "$LOG_FILE"
check_status "Atualizar núcleo do WordPress"

log "Atualizando banco de dados do núcleo"
sudo -u www-data wp core update-db  2>&1 | tee -a "$LOG_FILE"
check_status "Atualizar banco de dados do núcleo"

log "Atualizando traduções do núcleo"
sudo -u www-data wp language core update  2>&1 | tee -a "$LOG_FILE"
check_status "Atualizar traduções do núcleo"

if [ "$UPDATE_THEMES" = true ]; then
    log "Atualizando todos os temas, exceto: ${EXCLUDE_THEMES[*]}"
    for theme in $(sudo -u www-data wp theme list --field=name --status=active,inactive ); do
        if [[ ! " ${EXCLUDE_THEMES[@]} " =~ " ${theme} " ]]; then
            sudo -u www-data wp theme update "$theme"  2>&1 | tee -a "$LOG_FILE"
            check_status "Atualizar tema: $theme"
        else
            log "Tema '$theme' excluído da atualização"
        fi
    done

    log "Atualizando traduções dos temas"
    for theme in $(sudo -u www-data wp theme list --field=name --status=active,inactive ); do
        if [[ ! " ${EXCLUDE_THEMES[@]} " =~ " ${theme} " ]]; then
            sudo -u www-data wp language theme update "$theme"  2>&1 | tee -a "$LOG_FILE"
            check_status "Atualizar tradução do tema: $theme"
        else
            log "Tradução do tema '$theme' excluída"
        fi
    done
else
    log "Atualização de temas DESATIVADA"
fi

log "Atualizando todos os plugins"
sudo -u www-data wp plugin update --all  2>&1 | tee -a "$LOG_FILE"
check_status "Atualizar todos os plugins"

log "Atualizando traduções de plugins"
sudo -u www-data wp language plugin update --all  2>&1 | tee -a "$LOG_FILE"
check_status "Atualizar traduções de plugins"

SITE_IDS=$(sudo -u www-data wp site list --field=blog_id )

for SITE_ID in $SITE_IDS; do
    site_url=$(sudo -u www-data wp site url "$SITE_ID" )
    log "Atualizando site ID: $SITE_ID ($site_url)"

    sudo -u www-data wp --url="$site_url" core update-db  2>&1 | tee -a "$LOG_FILE"
    check_status "Atualizar banco de dados do site $SITE_ID"

    #disable_plugin_auto_updates_site "$site_url"

    log "Atualizando plugins do site $SITE_ID"
    sudo -u www-data wp --url="$site_url" plugin update --all  2>&1 | tee -a "$LOG_FILE"
    check_status "Atualizar plugins do site $SITE_ID"

    log "Atualizando traduções de plugins do site $SITE_ID"
    sudo -u www-data wp --url="$site_url" language plugin update --all  2>&1 | tee -a "$LOG_FILE"
    check_status "Atualizar traduções dos plugins do site $SITE_ID"

    if [ "$UPDATE_THEMES" = true ]; then
        log "Atualizando temas do site $SITE_ID"
        for theme in $(sudo -u www-data wp --url="$site_url" theme list --field=name --status=active,inactive ); do
            if [[ ! " ${EXCLUDE_THEMES[@]} " =~ " ${theme} " ]]; then
                sudo -u www-data wp --url="$site_url" theme update "$theme"  2>&1 | tee -a "$LOG_FILE"
                check_status "Atualizar tema $theme do site $SITE_ID"
            else
                log "Tema '$theme' excluído da atualização no site $SITE_ID"
            fi
        done

        log "Atualizando traduções dos temas do site $SITE_ID"
        for theme in $(sudo -u www-data wp --url="$site_url" theme list --field=name --status=active,inactive ); do
            if [[ ! " ${EXCLUDE_THEMES[@]} " =~ " ${theme} " ]]; then
                sudo -u www-data wp --url="$site_url" language theme update "$theme"  2>&1 | tee -a "$LOG_FILE"
                check_status "Atualizar tradução do tema $theme no site $SITE_ID"
            else
                log "Tradução do tema '$theme' excluída no site $SITE_ID"
            fi
        done
    fi
done

log "Desativando modo de manutenção"
sudo -u www-data wp maintenance-mode deactivate  2>&1 | tee -a "$LOG_FILE"
check_status "Desativar modo de manutenção"

log "Ajustando permissões da pasta wp-content"
chown www-data. wp-content wp-admin wp-includes index.php wp-settings.php wp-load.php wp-login.php wp-cron.php xmlrpc.php -R 2>&1 | tee -a "$LOG_FILE"

check_status "Aplicar chown na wp-content"

log "========== ATUALIZAÇÃO COMPLETA =========="