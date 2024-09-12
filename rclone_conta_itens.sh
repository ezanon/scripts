#!/bin/bash

# Verifica se a pasta foi passada como argumento
if [ -z "$1" ]; then
    echo "Uso: $0 <remote:path/para/pasta>"
    exit 1
fi

# Define o caminho remoto
REMOTE_PATH=$1

# Função para contar itens de uma pasta
contar_itens() {
    local PASTA=$1
    local NIVEIS=$2

    # Contar todos os itens (arquivos e pastas)
    TOTAL=$(rclone lsf "$PASTA" | wc -l)
    
    # Contar arquivos
    ARQUIVOS=$(rclone lsf --files-only "$PASTA" | wc -l)

    # Contar pastas
    PASTAS=$(rclone lsf --dirs-only "$PASTA" | wc -l)

    echo "Pasta: $PASTA"
    echo "Total de itens: $TOTAL"
    echo "Arquivos: $ARQUIVOS"
    echo "Pastas: $PASTAS"
    echo "---------------------"

    # Se ainda não alcançou o nível máximo, percorra as subpastas
    if [ "$NIVEIS" -gt 0 ]; then
        # Listar as subpastas e percorrê-las
        SUBPASTAS=$(rclone lsf --dirs-only "$PASTA")
        for SUBPASTA in $SUBPASTAS; do
            contar_itens "$PASTA/$SUBPASTA" $((NIVEIS - 1))
        done
    fi
}

# Chama a função de contagem com 3 níveis de subpastas
contar_itens "$REMOTE_PATH" 3

