#!/bin/bash

# Este script atualiza as instalações WordPress automaticamente, com validação de diretórios e tratamento de exceções.

# Arquivo de log com a data no nome
current_date=$(date +"%Y%m%d") 
log_file="/sites-usp/scripts/logs/update_wordpress/update_wordpress_$current_date.log"

# Basta adicionar a pasta do site na relação abaixo

# Array de diretórios que contêm instalações do WordPress
declare -a directories=(
    "/sites-usp/dev2/www/wordpress"
    "/sites-usp/6sbpg/www"
    "/sites-usp/areias/www"
    "/sites-usp/colecoes/www"
    "/sites-usp/didatico"
    "/sites-usp/game/www"
    "/sites-usp/geohereditas/www"
    "/sites-usp/geolit"
    "/sites-usp/legal/www"
    "/sites-usp/litoteca4/www"
    "/sites-usp/materiaisdidaticos/www"
    "/sites-usp/memoria/www"
    "/sites-usp/museu/www"
    "/sites-usp/ppegeo/www/portal"
    "/sites-usp/recursosdidaticos/www"
    "/sites-usp/repositorio/www"
    "/sites-usp/rtopbrgeociencias/www"
    "/sites-usp/transamazondrilling/www"
    "/sites-usp/wims/www"
    "/sites-usp/docentes/www"
    "/sites-usp/nwldw2025"
    "/sites-usp/csts"
    "/sites-usp/lago"
    "/sites-usp/replicas"
    "/sites-usp/mineraisdelgados"
    "/sites-usp/astrobio"
    "/sites-usp/prh"
)

# Lista de plugins que não devem ser atualizados
declare -a excluded_plugins=(
    "elementor-pro"
    "jonradio-multiple-themes"
)

# Função para atualizar uma instalação do WordPress
update_wordpress() {
    local dir=$1

    echo " "
    echo "Atualizando WordPress em $dir" | tee -a "$log_file"

    # Validar se o diretório existe
    if [ ! -d "$dir" ]; then
        echo "[ERRO] Diretório $dir não encontrado. Pulando..." | tee -a "$log_file"
        return 1
    fi

    # Navegar para o diretório
    cd "$dir" || {
        echo "[ERRO] Falha ao acessar o diretório $dir" | tee -a "$log_file"
        return 1
    }

    # Atualizar o núcleo do WordPress
    wp core update --allow-root 2>&1 | tee -a "$log_file"

    # Atualizar plugins, exceto os da lista de exclusão
    for plugin in $(wp plugin list --field=name --allow-root); do
        if [[ " ${excluded_plugins[@]} " =~ " $plugin " ]]; then
            echo "[INFO] Plugin $plugin está na lista de exclusão. Pulando..." | tee -a "$log_file"
        else
            wp plugin update "$plugin" --allow-root 2>&1 | tee -a "$log_file"
        fi
    done

    # Atualizar todos os temas
    wp theme update --all --allow-root 2>&1 | tee -a "$log_file"

    # Atualizar pacotes de linguagem
    wp language core update --allow-root 2>&1 | tee -a "$log_file"
    wp language plugin update --all --allow-root 2>&1 | tee -a "$log_file"
    wp language theme update --all --allow-root 2>&1 | tee -a "$log_file"

    # Pular uma linha no log
    echo " " >> "$log_file"

    # Redefinir permissões para www-data
    chown www-data. wp-content/plugins wp-content/themes wp-admin wp-includes index.php wp-settings.php wp-load.php wp-login.php wp-cron.php xmlrpc.php -R || {
        echo "[ERRO] Falha ao redefinir permissões em $dir" | tee -a "$log_file"
        return 1
    }
}

# Limpar ou criar o arquivo de log
echo "Log de atualização do WordPress - $(date)" > "$log_file"

# Verificar se um parâmetro foi passado
if [ "$#" -eq 1 ]; then
    site_name="$1"
    for dir in "${directories[@]}"; do
        extracted_name=$(echo "$dir" | sed -E 's|^/sites-usp/([^/]+).*|\1|')
        if [[ "$extracted_name" == "$site_name" ]]; then
            update_wordpress "$dir"
            echo "Atualização completa para $site_name." | tee -a "$log_file"
            exit 0
        fi
    done
    echo "[ERRO] Site $site_name não encontrado na lista de diretórios." | tee -a "$log_file"
    exit 1
else
    # Loop através de cada diretório e executar a função de atualização
    for dir in "${directories[@]}"; do
        update_wordpress "$dir"
    done

    echo "Atualização completa em todos os diretórios." | tee -a "$log_file"
    echo " "
fi
