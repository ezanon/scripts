#!/bin/bash

# Verifica se os parâmetros foram passados corretamente
if [ -z "$1" ] || [ -z "$2" ]; then
    echo "Uso: $0 <remote:path/para/pasta> <niveis>"
    exit 1
fi

# Define o caminho remoto e o número de níveis a serem percorridos
REMOTE_PATH=$1
NIVEIS=$2

# Função para contar itens de uma pasta
contar_itens() {
    local PASTA=$1
    local NIVEL_ATUAL=$2

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
    if [ "$NIVEL_ATUAL" -gt 0 ]; then
        # Listar as subpastas e percorrê-las
        SUBPASTAS=$(rclone lsf --dirs-only "$PASTA")
        for SUBPASTA in $SUBPASTAS; do
            contar_itens "$PASTA/$SUBPASTA" $((NIVEL_ATUAL - 1))
        done
    fi
}

# Chama a função de contagem com o número de níveis especificado
contar_itens "$REMOTE_PATH" "$NIVEIS"
