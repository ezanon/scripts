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
    for plugin in $(wp --url="$site_url" plugin list --field=name --allow-root); do
        wp --url="$site_url" plugin auto-updates disable "$plugin" --allow-root 2>&1 | tee -a "$LOG_FILE"
    done
}

log "========== INÍCIO DA ATUALIZAÇÃO WORDPRESS MULTISITE =========="

cd "$WP_PATH" || { log "Diretório não encontrado: $WP_PATH"; exit 1; }

log "Estado inicial: listagem de plugins"
wp plugin list --allow-root 2>&1 | tee -a "$LOG_FILE"

log "Ativando modo de manutenção"
wp maintenance-mode activate --allow-root 2>&1 | tee -a "$LOG_FILE"
check_status "Ativar modo de manutenção"

log "Atualizando núcleo do WordPress"
wp core update --allow-root 2>&1 | tee -a "$LOG_FILE"
check_status "Atualizar núcleo do WordPress"

log "Atualizando banco de dados do núcleo"
wp core update-db --allow-root 2>&1 | tee -a "$LOG_FILE"
check_status "Atualizar banco de dados do núcleo"

log "Atualizando traduções do núcleo"
wp language core update --allow-root 2>&1 | tee -a "$LOG_FILE"
check_status "Atualizar traduções do núcleo"

if [ "$UPDATE_THEMES" = true ]; then
    log "Atualizando todos os temas, exceto: ${EXCLUDE_THEMES[*]}"
    for theme in $(wp theme list --field=name --status=active,inactive --allow-root); do
        if [[ ! " ${EXCLUDE_THEMES[@]} " =~ " ${theme} " ]]; then
            wp theme update "$theme" --allow-root 2>&1 | tee -a "$LOG_FILE"
            check_status "Atualizar tema: $theme"
        else
            log "Tema '$theme' excluído da atualização"
        fi
    done

    log "Atualizando traduções dos temas"
    for theme in $(wp theme list --field=name --status=active,inactive --allow-root); do
        if [[ ! " ${EXCLUDE_THEMES[@]} " =~ " ${theme} " ]]; then
            wp language theme update "$theme" --allow-root 2>&1 | tee -a "$LOG_FILE"
            check_status "Atualizar tradução do tema: $theme"
        else
            log "Tradução do tema '$theme' excluída"
        fi
    done
else
    log "Atualização de temas DESATIVADA"
fi

log "Atualizando todos os plugins"
wp plugin update --all --allow-root 2>&1 | tee -a "$LOG_FILE"
check_status "Atualizar todos os plugins"

log "Atualizando traduções de plugins"
wp language plugin update --all --allow-root 2>&1 | tee -a "$LOG_FILE"
check_status "Atualizar traduções de plugins"

SITE_IDS=$(wp site list --field=blog_id --allow-root)

for SITE_ID in $SITE_IDS; do
    site_url=$(wp site url "$SITE_ID" --allow-root)
    log "Atualizando site ID: $SITE_ID ($site_url)"

    wp --url="$site_url" core update-db --allow-root 2>&1 | tee -a "$LOG_FILE"
    check_status "Atualizar banco de dados do site $SITE_ID"

    #disable_plugin_auto_updates_site "$site_url"

    log "Atualizando plugins do site $SITE_ID"
    wp --url="$site_url" plugin update --all --allow-root 2>&1 | tee -a "$LOG_FILE"
    check_status "Atualizar plugins do site $SITE_ID"

    log "Atualizando traduções de plugins do site $SITE_ID"
    wp --url="$site_url" language plugin update --all --allow-root 2>&1 | tee -a "$LOG_FILE"
    check_status "Atualizar traduções dos plugins do site $SITE_ID"

    if [ "$UPDATE_THEMES" = true ]; then
        log "Atualizando temas do site $SITE_ID"
        for theme in $(wp --url="$site_url" theme list --field=name --status=active,inactive --allow-root); do
            if [[ ! " ${EXCLUDE_THEMES[@]} " =~ " ${theme} " ]]; then
                wp --url="$site_url" theme update "$theme" --allow-root 2>&1 | tee -a "$LOG_FILE"
                check_status "Atualizar tema $theme do site $SITE_ID"
            else
                log "Tema '$theme' excluído da atualização no site $SITE_ID"
            fi
        done

        log "Atualizando traduções dos temas do site $SITE_ID"
        for theme in $(wp --url="$site_url" theme list --field=name --status=active,inactive --allow-root); do
            if [[ ! " ${EXCLUDE_THEMES[@]} " =~ " ${theme} " ]]; then
                wp --url="$site_url" language theme update "$theme" --allow-root 2>&1 | tee -a "$LOG_FILE"
                check_status "Atualizar tradução do tema $theme no site $SITE_ID"
            else
                log "Tradução do tema '$theme' excluída no site $SITE_ID"
            fi
        done
    fi
done

log "Desativando modo de manutenção"
wp maintenance-mode deactivate --allow-root 2>&1 | tee -a "$LOG_FILE"
check_status "Desativar modo de manutenção"

log "Ajustando permissões da pasta wp-content"
chown www-data. wp-content wp-admin wp-includes index.php wp-settings.php wp-load.php wp-login.php wp-cron.php xmlrpc.php -R 2>&1 | tee -a "$LOG_FILE"

check_status "Aplicar chown na wp-content"

log "========== ATUALIZAÇÃO COMPLETA =========="