root@BackupIGc:~# cat bkpIncremental.sh
#! /bin/bash

# config pasta destino do backup
DESTINO='/root/bkpIncremental/backup.0/'

# config pasta de logs
LOGS=$DESTINO/_replica.log

# marca inicio
DATA=`date +%Y%m%d%H%M`
echo $DATA > $DESTINO/_logs/geral.log

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
#rsync -avz --delete --rsh="ssh -p $PORTA -i /root/.ssh/id_rsa" root@$IP:$ORIGEM $DESTINO/$SERVIDOR/ > $LOGS/$SERVIDOR.log 2> $LOGS/$SERVIDOR.err

######## MAQUINAS FIM

# marca fim
DATA=`date +%Y%m%d%H%M`
echo $DATA >> $DESTINO/_logs/geral.log
