#!/bin/bash

BASEDIR=$(dirname "$BASH_SOURCE")
VENV_DIR="${BASEDIR}/venv"
PROGRESS_FILE=/tmp/dependancy_networks_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi

# add timestamped log in stdout and logfile
function log(){
	 echo "$(date +'[%F %T]') $1";
}

touch ${PROGRESS_FILE}
log "Start install dependancies -- check $PROGRESS_FILE"

# install venv - should be already done by jeedom-core
echo 0 > ${PROGRESS_FILE}
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y python3 python3-pip python3-venv

# create the venv; DO NOT activate venv /!\
sudo -u www-data python3 -m venv $VENV_DIR

# check python version should be >=3.6
echo 5 > ${PROGRESS_FILE}
PYTHON_VERSION=$($VENV_DIR/bin/python3 -c 'import sys; version=sys.version_info[:3]; print("{0}{1}".format(*version))')
if [[ "$PYTHON_VERSION" -ge "36" ]]
then
    log "Valid Python version $PYTHON_VERSION"
	PYTHON_BASH=$VENV_DIR/bin/python3
else
    log "ERROR: Invalid Python version $PYTHON_VERSION !! minimum 3.6 required"
	exit 1
fi

# go into daemon dir:
cd ${BASEDIR}
# for jeedom to know the command for python3.7
sudo -u www-data echo ${PYTHON_BASH} > python.cmd

echo 85 > ${PROGRESS_FILE}
log "upgrade pip3 and wheel"
sudo -u www-data ${PYTHON_BASH} -m pip install --upgrade pip wheel

echo 90 > ${PROGRESS_FILE}
log "install python dependencies in ${BASEDIR}"
sudo -u www-data ${PYTHON_BASH} -m pip install -r requirements.txt

echo 95 > ${PROGRESS_FILE}
log "clone wideq lib from github in ${BASEDIR}"
rm -r wideq
sudo -u www-data git clone https://github.com/pifou25/wideq.git -b jeedom
# wget https://github.com/pifou25/wideq/archive/jeedom.zip -O wideq.zip
# unzip -q wideq.zip -d .
# mv wideq-jeedom wideq
# rm wideq.zip

echo 100 > ${PROGRESS_FILE}
log "Everything is successfully installed!"

rm ${PROGRESS_FILE}
