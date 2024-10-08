#!/bin/bash

# Verificar se o parâmetro da pasta foi fornecido
if [ $# -ne 1 ]; then
    echo "Uso: $0 <nome_da_pasta>"
    exit 1
fi

# Atribuir o parâmetro a uma variável
PASTA=$1

# Definir os remotos de origem e destino
ORIGEM="gdczanon:${PASTA}"
DESTINO="gcs:usp-gcp-2000044-7fe99.usp.br/${PASTA}"

# Exibir o comando rclone que será executado
echo "Comando a ser executado: rclone -P --drive-scope 'drive' --gcs-bucket-policy-only --stats-one-line --drive-skip-shortcuts copy \"$ORIGEM\" \"$DESTINO\""

# Executar o comando rclone com os remotos definidos
rclone -P \
  --drive-scope "drive" \
  --gcs-bucket-policy-only \
  --stats-one-line \
  --drive-skip-shortcuts \
  copy "$ORIGEM" "$DESTINO"

#  --drive-stop-on-download-limit \ dando erro

# Verificar se o rclone terminou com sucesso
if [ $? -eq 0 ]; then
    echo "Cópia concluída com sucesso!"
else
    echo "Erro durante a cópia"
fi
