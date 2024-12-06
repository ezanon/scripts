#!/bin/bash

# Verificar se os parâmetros foram passados corretamente
if [ -z "$1" ] || [ -z "$2" ]; then
  echo "Uso: $0 <pasta_raiz> <arquivo_relatorio>"
  exit 1
fi

# Pasta raiz e nome do arquivo de relatório
PASTA_RAIZ=$1
RELATORIO=$2

# Verificar se o arquivo de relatório existe
if [ ! -f "$RELATORIO" ]; then
  echo "Arquivo de relatório \"$RELATORIO\" não encontrado."
  exit 1
fi

# Ler o arquivo linha por linha e copiar as pastas listadas
while IFS= read -r ARQUIVO; do
  # Construir o caminho completo para a origem
  CAMINHO_COMPLETO_ORIGEM=$(dirname "${PASTA_RAIZ}/${ARQUIVO}")

  # Escapar espaços no caminho de origem
#  CAMINHO_COMPLETO_ORIGEM_ESCAPADO=$(echo "$CAMINHO_COMPLETO_ORIGEM" | sed 's/ /\\ /g')

  echo "Tentando copiar: $CAMINHO_COMPLETO_ORIGEM"
#  echo "Tentando copiar: $CAMINHO_COMPLETO_ORIGEM_ESCAPADO"

  # Chamar o script rclone_copy.sh com o caminho escapado
  ./rclone_copy.sh $CAMINHO_COMPLETO_ORIGEM
#  ./rclone_copy.sh $CAMINHO_COMPLETO_ORIGEM_ESCAPADO

  echo " "

done < "$RELATORIO"

echo "Tentativa de cópia concluída."
