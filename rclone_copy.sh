#!/bin/bash

# Verificar se o nome da pasta foi passado como argumento
if [ -z "$1" ]; then
  echo "Uso: $0 <nome_da_pasta>"
  exit 1
fi

# Nome da pasta passado como argumento
PASTA=$1

# Remotos de origem e destino
ORIGEM="gdczanon:${PASTA}"
#ORIGEM="${PASTA}"
DESTINO="san:usp-gcp-2000044-7fe99.usp.br/${PASTA}"

# Exibir o comando que será executado
echo "Executando: rclone copy \"$ORIGEM\" \"$DESTINO\" -P --gcs-bucket-policy-only --stats-one-line --drive-acknowledge-abuse --drive-scope 'drive' --tpslimit 5"

# Executar o comando rclone com todas as opções passadas
rclone copy "$ORIGEM" "$DESTINO" \
    -P \
    --gcs-bucket-policy-only \
    --stats-one-line \
    --drive-acknowledge-abuse \
    --tpslimit 5 \
    --drive-scope "drive"
#    --transfers=8 \
#    --checkers=8  \
#    --drive-skip-shortcuts \
#    --drive-stop-on-download-limit

# Verificar se o rclone terminou com sucesso
if [ $? -eq 0 ]; then
  echo "Cópia concluída com sucesso."
else
  echo "Erro durante a cópia."
fi

