
#!/bin/bash

# Este script atualiza as instalações wordpress automaticamente
# Basta adicionar a pasta do site na relação abaixo

# Array de diretórios que contêm instalações do WordPress
declare -a directories=(
    "/sites-usp/6sbpg/www"
    "/sites-usp/areias/www"
    "/sites-usp/colecoes/www"
    "/sites-usp/didatico"
#     "/sites-usp/game/www"
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
    "/sites-usp/nwldw2025"
)

# Data atual no formato YYYYMMDD
current_date=$(date +"%Y%m%d")

# Arquivo de log com a data no nome
log_file="/sites-usp/scripts/update_wordpress_logs/update_wordpress_$current_date.log"

# Função para atualizar uma instalação do WordPress
update_wordpress() {
    local dir=$1

    echo " "
    echo "Atualizando WordPress em $dir" | tee -a "$log_file"
    
    # Navegar para o diretório
    cd "$dir" || {
        echo "Erro ao acessar o diretório $dir" | tee -a "$log_file"
        return 1
    }

    # Atualizar o núcleo do WordPress
    wp core update --allow-root 2>&1 | tee -a "$log_file"

    # Atualizar todos os plugins
    wp plugin update --all --allow-root 2>&1 | tee -a "$log_file"

    # Atualizar todos os temas
    wp theme update --all --allow-root 2>&1 | tee -a "$log_file"

    # Atualizar pacotes de linguagem (opcional)
    wp language core update --allow-root 2>&1 | tee -a "$log_file"
    wp language plugin update --all --allow-root 2>&1 | tee -a "$log_file"
    wp language theme update --all --allow-root 2>&1 | tee -a "$log_file"

    # Pular uma linha no log
    echo " " >> "$log_file"
}

# Limpar ou criar o arquivo de log
echo "Log de atualização do WordPress - $(date)" > "$log_file"

# Loop através de cada diretório e executar a função de atualização
for dir in "${directories[@]}"; do
    update_wordpress "$dir"
done

echo "Atualização completa em todos os diretórios." | tee -a "$log_file"
echo " "

