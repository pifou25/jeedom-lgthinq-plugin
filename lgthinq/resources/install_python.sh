#!/bin/bash

# install dependancy
# download and make python3.7
# source https://unix.stackexchange.com/questions/332641/how-to-install-python-3-6

PROGRESS_FILE=/tmp/dependancy_networks_in_progress
LOG_FILE=/tmp/install_python.log
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi

touch ${PROGRESS_FILE}

echo 0 > ${PROGRESS_FILE}
echo "$(date +'[%F %T]') update package list"
apt-get update > ${LOG_FILE}

echo 10 > ${PROGRESS_FILE}
echo "$(date +'[%F %T]') install required dependancies"
apt-get install -y make build-essential libssl-dev zlib1g-dev libbz2-dev \
  libreadline-dev libsqlite3-dev wget curl llvm libncurses5-dev libncursesw5-dev \
  xz-utils tk-dev libffi-dev liblzma-dev >> ${LOG_FILE}
  
echo 20 > ${PROGRESS_FILE}
echo "$(date +'[%F %T]') download Python-3.7"
cd /tmp
wget https://www.python.org/ftp/python/3.7.7/Python-3.7.7.tgz >> ${LOG_FILE}

echo 30 > ${PROGRESS_FILE}
echo "$(date +'[%F %T]') unzip archive Python-3.7"
tar xvf Python-3.7.7.tgz >> ${LOG_FILE}
cd Python-3.7.7

echo 40 > ${PROGRESS_FILE}
echo "$(date +'[%F %T]') configure Python-3.7"
./configure --enable-optimizations --enable-shared --with-ensurepip=install >> ${LOG_FILE}

# trèèèès long le make
echo 50 > ${PROGRESS_FILE}
export NUMCPUS=`grep -c '^processor' /proc/cpuinfo`
echo "$(date +'[%F %T]') make Python-3.7 with $NUMCPUS threads"
make -j$NUMCPUS >> ${LOG_FILE}

echo 80 > ${PROGRESS_FILE}
echo "$(date +'[%F %T]') altinstall Python-3.7"
make altinstall >> ${LOG_FILE}

echo 100 > ${PROGRESS_FILE}
echo "$(date +'[%F %T]') Everything is successfully installed!"
whereis python >> ${LOG_FILE}

rm ${PROGRESS_FILE}
