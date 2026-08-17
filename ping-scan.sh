#!/bin/bash

# Intervalo de IPs para escanear (exemplo: 192.168.1.1-192.168.1.254)
IP_RANGE="10.190.1.10-10.190.1.120"

# Arquivo de saída do relatório
REPORT_FILE="ping_report.txt"

# Limpa o arquivo de relatório
echo "Relatório de Ping - $(date)" > "$REPORT_FILE"
echo "Intervalo de IPs: $IP_RANGE" >> "$REPORT_FILE"
echo "----------------------------------------" >> "$REPORT_FILE"

# Função para obter o MAC address de um IP
get_mac_address() {
    arp -n "$1" | awk '/ether/ {print $3}'
}

# Varre o intervalo de IPs e realiza o ping
echo "Varredura iniciada, por favor aguarde..."
nmap -sn "$IP_RANGE" -oG - | grep "Host" | while read -r line; do
    IP=$(echo "$line" | awk '/Host/ {print $2}')
    STATUS=$(echo "$line" | awk '/Status: Up/ {print $4}')
    
    if [[ "$STATUS" == "Up" ]]; then
        MAC=$(get_mac_address "$IP")
        echo "Respondido: $IP - MAC: ${MAC:-Não disponível}" >> "$REPORT_FILE"
    else
        echo "Não respondido: $IP" >> "$REPORT_FILE"
    fi
done

echo "----------------------------------------" >> "$REPORT_FILE"
echo "Varredura concluída. Relatório salvo em: $REPORT_FILE"