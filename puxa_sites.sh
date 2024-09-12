#! /bin/bash

# config pasta destino do backup
DESTINO='/sites/'

# config pasta de logs
LOGS="${DESTINO}scripts/logs/puxa_sites"

# marca inicio
DATA=`date +%Y%m%d%H%M`
echo $DATA > $LOGS/geral.log

######## MAQUINAS INICIO

#  MysqlServer2020 (nuvemUSP) - Replicação do IGc
SERVIDOR='MysqlServer2020-USP'
IP='nuvem.igc.usp.br'
ORIGEM='/root/automysqlbackup/'
PORTA=2721
rsync -avz --delete --rsh="ssh -p $PORTA -i /root/.ssh/id_rsa" root@$IP:$ORIGEM $DESTINO/_$SERVIDOR/ > $LOGS/$SERVIDOR.log 2> $LOGS/$SERVIDOR.err

# Webserver2020 (nuvemUSP)
#SERVIDOR='Webserver2020-USP'
#IP='nuvem.igc.usp.br'
#ORIGEM='/sites-usp'
#PORTA=2720
#rsync -avz --delete --rsh="ssh -p $PORTA -i /root/.ssh/id_rsa" root@$IP:$ORIGEM $DESTINO/ > $LOGS/$SERVIDOR.log 2> $LOGS/$SERVIDOR.err

######## MAQUINAS FIM

# marca fim
DATA=`date +%Y%m%d%H%M`
echo $DATA >> $LOGS/geral.log
