#!/bin/bash

BASEDIR=$(dirname "$BASH_SOURCE")

# add timestamped log in stdout and logfile
function log(){
	 echo "$(date +'[%F %T]') $1";
}

# go into daemon dir:
cd ${BASEDIR}

log "clone wideq lib from github in ${BASEDIR}"
rm -r wideq
sudo -u www-data git clone https://github.com/pifou25/wideq.git -b jeedom
# wget https://github.com/pifou25/wideq/archive/jeedom.zip -O wideq.zip
# unzip -q wideq.zip -d .
# mv wideq-jeedom wideq
# rm wideq.zip

log "Everything is successfully installed!"

