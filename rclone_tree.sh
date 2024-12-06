#!/bin/bash

# Verificar se o número correto de parâmetros foi passado
if [ "$#" -ne 2 ]; then
    echo "Uso: $0 <nome_pasta> <subníveis>"
    exit 1
fi

# Atribuindo parâmetros a variáveis
NOME_PASTA=$1
NIVEIS=$2

# Verificar se rclone está instalado
if ! command -v rclone &> /dev/null
then
    echo "rclone não está instalado. Instale-o antes de continuar."
    exit 1
fi

# Função para exibir a árvore de diretórios
function exibir_arvore() {
    local PASTA=$1
    local NIVEIS=$2
    local PREFIXO=""

    # Exibir a árvore de diretórios com base nos níveis especificados
    rclone lsd "$PASTA" --max-depth "$NIVEIS" | while IFS= read -r linha; do
        local DIR=$(echo "$linha" | awk '{print $5}')
        if [ -n "$DIR" ]; then
            echo "$PREFIXO$DIR/"
            # Incrementar a profundidade
            if [ "$NIVEIS" -gt 1 ]; then
                exibir_arvore "$PASTA/$DIR" $((NIVEIS-1))
            fi
        fi
    done
}

# Chamar a função com os parâmetros fornecidos
exibir_arvore "$NOME_PASTA" "$NIVEIS"

