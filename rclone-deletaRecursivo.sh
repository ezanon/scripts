
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

# Exclui todos os arquivos e pastas no caminho especificado
function delete_folder_content {
    local path="$1"

    echo "Processando: $path"

    # Listar e deletar arquivos no caminho especificado
    rclone ls "$REMOTE_NAME:$path" | while read -r size file; do
        echo "Deletando arquivo: $file"
        rclone delete "$REMOTE_NAME:$path/$file"
    done

    # Listar e deletar subpastas no caminho especificado
    rclone lsd "$REMOTE_NAME:$path" | while read -r line; do
        # Extrair o nome da subpasta
        subfolder=$(echo "$line" | awk '{print $5}')
        echo "Deletando conteúdo da subpasta: $subfolder"
        delete_folder_content "$path/$subfolder"
        echo "Deletando subpasta: $subfolder"
        rclone rmdirs "$REMOTE_NAME:$path/$subfolder"
    done
}

# Deleta o conteúdo da pasta especificada
delete_folder_content "$FOLDER_PATH"

# Deleta a pasta raiz especificada
echo "Deletando pasta raiz: $FOLDER_PATH"
rclone rmdirs "$REMOTE_NAME:$FOLDER_PATH"
