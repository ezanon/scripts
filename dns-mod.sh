
#!/usr/bin/env bash

# Configurações padrão
ZONE="igc.usp.br"
SERVER="127.0.0.1"
TTL="86400"

# Função para exibir mensagem de ajuda / modo de uso
usage() {
    echo "========================================================"
    echo "       Gerenciador Dinâmico de DNS - $ZONE"
    echo "========================================================"
    echo "Uso:"
    echo "  Adicionar: $0 -a add    -d <subdominio> -i <ip>"
    echo "  Deletar:   $0 -a delete -d <subdominio>"
    echo ""
    echo "Opções:"
    echo "  -a : Ação ('add' ou 'delete')"
    echo "  -d : Subdomínio (ex: 'prh' ou 'prh.igc.usp.br')"
    echo "  -i : Endereço IP (obrigatório para a ação 'add')"
    echo "  -h | ? : Exibe esta tela de ajuda"
    echo ""
    echo "Exemplos:"
    echo "  $0 -a add -d prh -i 143.107.76.44"
    echo "  $0 -a delete -d colocal"
    echo "  $0 ?"
    echo "  $0 -h"
    echo "========================================================"
    exit 0
}

# Se o primeiro argumento for '?' ou '--help', exibe a ajuda imediatamente
if [[ "$1" == "?" || "$1" == "--help" ]]; then
    usage
fi

# Se rodar o script sem nenhum argumento, exibe a ajuda
if [[ $# -eq 0 ]]; then
    usage
fi

# Processamento das opções/flags passadas na linha de comando
ACTION=""
SUBDOMAIN=""
IP=""

while getopts "a:d:i:h" opt; do
    case "$opt" in
        a) ACTION="$OPTARG" ;;
        d) SUBDOMAIN="$OPTARG" ;;
        i) IP="$OPTARG" ;;
        h|*) usage ;;
    esac
done

# Validações básicas
if [[ "$ACTION" != "add" && "$ACTION" != "delete" ]]; then
    echo "Erro: Ação deve ser 'add' ou 'delete'."
    echo ""
    usage
fi

if [[ -z "$SUBDOMAIN" ]]; then
    echo "Erro: O subdomínio não foi informado."
    echo ""
    usage
fi

# Formata o FQDN (nome completo terminando com ponto)
if [[ "$SUBDOMAIN" != *"$ZONE"* ]]; then
    FQDN="${SUBDOMAIN}.${ZONE}."
else
    FQDN="${SUBDOMAIN%.}."
fi

# Execução da ação: ADD
if [[ "$ACTION" == "add" ]]; then
    if [[ -z "$IP" ]]; then
        echo "Erro: Para adicionar uma entrada é necessário informar o IP (-i)."
        echo ""
        usage
    fi

    NSUPDATE_CMD="zone $ZONE\nupdate add $FQDN $TTL A $IP\nsend"

    echo "--------------------------------------------------------"
    echo "[+] Adicionando: $FQDN -> $IP"
    echo "[>] Executando comando:"
    echo -e "$NSUPDATE_CMD" | sed 's/^/    /'
    echo "--------------------------------------------------------"
    
    echo -e "$NSUPDATE_CMD" | nsupdate -4 -l
    STATUS=$?

# Execução da ação: DELETE
elif [[ "$ACTION" == "delete" ]]; then
    NSUPDATE_CMD="zone $ZONE\nupdate delete $FQDN\nsend"

    echo "--------------------------------------------------------"
    echo "[-] Deletando: $FQDN"
    echo "[>] Executando comando:"
    echo -e "$NSUPDATE_CMD" | sed 's/^/    /'
    echo "--------------------------------------------------------"
    
    echo -e "$NSUPDATE_CMD" | nsupdate -4 -l
    STATUS=$?
fi

# Checagem de sucesso
if [[ $STATUS -eq 0 ]]; then
    echo "Operação concluída com sucesso!"
    echo "Testando com dig local:"
    dig @$SERVER +short $FQDN A
else
    echo "Erro ao executar o nsupdate (código $STATUS)."
fi
