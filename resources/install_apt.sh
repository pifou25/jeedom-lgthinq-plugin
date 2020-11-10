#!/bin/bash

BASEDIR=$(dirname "$BASH_SOURCE")
LOG_FILE=$(cd "${BASEDIR}/../../../log"; pwd)"/lgthinq_install"
PROGRESS_FILE=/tmp/dependancy_networks_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi

# add timestamped log in stdout and logfile
function log(){
	 echo "$(date +'[%F %T]') $1" >> ${LOG_FILE};
	 echo "$(date +'[%F %T]') $1";
}

touch ${PROGRESS_FILE}
log "Start install dependancies"

# check python version should be >=3.6
echo 0 > ${PROGRESS_FILE}
export PYTHON_VERSION=$(python3 -c 'import sys; version=sys.version_info[:3]; print("{0}{1}".format(*version))')
if [[ "$PYTHON_VERSION" -ge "36" ]]
then
    log "Valid Python version $PYTHON_VERSION"
		PYTHON_BASH=python3

		# echo 20 > ${PROGRESS_FILE}
		# log "install required dependancies"
		# pip3 virtualenv >> ${LOG_FILE}

else
    log "Invalid Python version $PYTHON_VERSION (min 36 required)"

		# install dependancy
		# download and make python3.7
		# source https://unix.stackexchange.com/questions/332641/how-to-install-python-3-6

		echo 10 > ${PROGRESS_FILE}
		log "update package list"
		apt-get update >> ${LOG_FILE}

		echo 20 > ${PROGRESS_FILE}
		log "install required dependancies"
		apt-get install -y make build-essential libssl-dev zlib1g-dev libbz2-dev \
		  libreadline-dev libsqlite3-dev wget curl llvm libncurses5-dev libncursesw5-dev \
		  xz-utils tk-dev libffi-dev liblzma-dev python-virtualenv virtualenv >> ${LOG_FILE}

		echo 30 > ${PROGRESS_FILE}
		log "download Python-3.7"
		cd /tmp
		wget https://www.python.org/ftp/python/3.7.7/Python-3.7.7.tgz >> ${LOG_FILE}

		echo 40 > ${PROGRESS_FILE}
		log "unzip archive Python-3.7"
		tar xvf Python-3.7.7.tgz >> ${LOG_FILE}
		cd Python-3.7.7

		echo 50 > ${PROGRESS_FILE}
		log "configure Python-3.7"
		# dont use too long option --enable-optimizations
		./configure  --prefix=/usr/local --enable-shared \
		  LDFLAGS="-Wl,-rpath /usr/local/lib" --with-ensurepip=install > /dev/null

		# trèèèès long le make
		echo 60 > ${PROGRESS_FILE}
		export NUMCPUS=$(grep -c '^processor' /proc/cpuinfo)
		log "make Python-3.7 with $NUMCPUS threads"
		make -j$NUMCPUS > /dev/null

		echo 80 > ${PROGRESS_FILE}
		log "altinstall Python-3.7"
		make altinstall > /dev/null

		# fix error:
		# python3.7: error while loading shared libraries: libpython3.7m.so.1.0: cannot open shared object file: No such file or directory
		export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/local/lib
		PYTHON_BASH=python3.7
		#
		whereis python >> ${LOG_FILE}

fi

# dans le rep du daemon python:
cd ${BASEDIR}/daemon
# for jeedom to know the command for python3.7
echo ${PYTHON_BASH} > python.cmd

echo 85 > ${PROGRESS_FILE}
log "upgrade pip3"
${PYTHON_BASH} -m pip install --upgrade pip >> ${LOG_FILE}

echo 90 > ${PROGRESS_FILE}
log "install python dependencies in ${BASEDIR}/daemon"
# ${PYTHON_BASH} -m venv env >> ${LOG_FILE}
# source env/bin/activate
${PYTHON_BASH} -m pip install -r requirements.txt >> ${LOG_FILE}
# sortie de l'env virtuel
# deactivate

echo 95 > ${PROGRESS_FILE}
log "clone wideq lib from github in ${BASEDIR}/daemon"
rm -r wideq
wget https://github.com/pifou25/wideq/archive/jeedom.zip -O wideq.zip
unzip -q wideq.zip -d .
mv wideq-jeedom wideq
chown -R www-data:www-data wideq
rm wideq.zip

chmod +x check.sh

echo 100 > ${PROGRESS_FILE}
log "Everything is successfully installed!"

rm ${PROGRESS_FILE}
