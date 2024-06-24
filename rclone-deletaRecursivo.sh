#!/bin/bash

# Defina o nome remoto do rclone e o caminho da pasta
REMOTE_NAME="meu_drive"
FOLDER_PATH="caminho/para/a/pasta"

# Verifique se o rclone está instalado
if ! command -v rclone &> /dev/null
then
    echo "rclone não está instalado. Instale-o primeiro."
    exit 1
fi

# Registrar o tempo de início
start_time=$(date +%s)

# Função para mostrar o tempo decorrido em tempo real
show_elapsed_time() {
    while true; do
        current_time=$(date +%s)
        elapsed=$((current_time - start_time))
        hours=$((elapsed / 3600))
        minutes=$(((elapsed % 3600) / 60))
        seconds=$((elapsed % 60))
        echo "Tempo decorrido: $hours horas, $minutes minutos e $seconds segundos."
        sleep 10
    done
}

# Iniciar a exibição do tempo decorrido em segundo plano
show_elapsed_time &
elapsed_time_pid=$!

# Exclui todos os arquivos e pastas no caminho especificado
delete_folder_content() {
    local path="$1"

    echo "Processando: $path"

    # Listar e deletar arquivos no caminho especificado
    rclone ls "$REMOTE_NAME:$path" --fast-list | while read -r size file; do
        echo "Deletando arquivo: $file"
        rclone delete "$REMOTE_NAME:$path/$file" --transfers=16 --checkers=32 --fast-list
        if [ $? -ne 0 ]; then
            echo "Erro ao deletar o arquivo: $file"
        fi
    done

    # Listar e deletar subpastas no caminho especificado
    rclone lsd "$REMOTE_NAME:$path" --fast-list | while read -r line; do
        # Extrair o nome da subpasta
        subfolder=$(echo "$line" | awk '{print $5}')
        echo "Deletando conteúdo da subpasta: $subfolder"
        delete_folder_content "$path/$subfolder"
        echo "Deletando subpasta: $subfolder"
        rclone rmdirs "$REMOTE_NAME:$path/$subfolder" --transfers=16 --checkers=32 --fast-list
        if [ $? -ne 0 ]; then
            echo "Erro ao deletar a subpasta: $subfolder"
        fi
    done
}

# Deleta o conteúdo da pasta especificada
delete_folder_content "$FOLDER_PATH"

# Deleta a pasta raiz especificada
echo "Deletando pasta raiz: $FOLDER_PATH"
rclone rmdirs "$REMOTE_NAME:$FOLDER_PATH" --transfers=16 --checkers=32 --fast-list
if [ $? -ne 0 ]; then
    echo "Erro ao deletar a pasta raiz: $FOLDER_PATH"
fi

# Matar o processo de exibição do tempo decorrido
kill $elapsed_time_pid

# Registrar o tempo de término
end_time=$(date +%s)

# Calcular a duração total em segundos
duration=$((end_time - start_time))

# Converter a duração para um formato legível
hours=$((duration / 3600))
minutes=$(((duration % 3600) / 60))
seconds=$((duration % 60))

echo "O script levou $hours horas, $minutes minutos e $seconds segundos para ser executado."
