#!/bin/bash

# Verificar se os parâmetros de origem e destino foram fornecidos
if [ $# -ne 2 ]; then
    echo "Uso: $0 <origem> <destino>"
    exit 1
fi

# Atribuir os parâmetros a variáveis
ORIGEM=$1
DESTINO=$2

# Executar o comando rclone com os parâmetros fornecidos
rclone -P --delete-before \
  --drive-scope "drive" \
  --gcs-bucket-policy-only \
  --stats-one-line \
  --drive-skip-shortcuts \
  --drive-stop-on-download-limit \
  copy "$ORIGEM" "$DESTINO"

# Verificar se o rclone terminou com sucesso
if [ $? -eq 0 ]; then
    echo "Cópia concluída com sucesso!"
else
    echo "Erro durante a cópia"
fi

