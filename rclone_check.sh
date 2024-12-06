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
DESTINO="san:usp-gcp-2000044-7fe99.usp.br/${PASTA}"

# Exibir o comando que será executado
echo "Executando: rclone check \"$ORIGEM\" \"$DESTINO\" --one-way --differ diferencas.txt --missing-on-dst faltando_no_destino.txt --error erros.txt"

# Executar o comando rclone check com relatórios
rclone check "$ORIGEM" "$DESTINO" \
    --one-way \
    --differ diferencas.txt \
    --missing-on-dst faltando_no_destino.txt \
    --error erros.txt

# Verificar se o rclone terminou com sucesso
if [ $? -eq 0 ]; then
  echo "Verificação concluída com sucesso. Nenhuma diferença encontrada."
else
  echo "Diferenças ou problemas encontrados. Consulte os relatórios:"
  echo " - diferencas.txt: Arquivos com diferenças de conteúdo."
  echo " - faltando_no_destino.txt: Arquivos que estão na origem, mas faltam no destino."
  echo " - erros.txt: Problemas encontrados durante a verificação."
fi

