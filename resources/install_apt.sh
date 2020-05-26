#!/bin/bash

BASEDIR=$(dirname "$BASH_SOURCE")
LOG_FILE=${BASEDIR}/../../logs/lgthinq_install.log
PROGRESS_FILE=/tmp/dependancy_networks_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi

touch ${PROGRESS_FILE}

# check python version should be >=3.6
echo 0 > ${PROGRESS_FILE}
export PYTHON_VERSION=`python3 -c 'import sys; version=sys.version_info[:3]; print("{0}{1}".format(*version))'`
if [[ "$PYTHON_VERSION" -ge "36" ]]
then
    echo "Valid version $PYTHON_VERSION" >> ${LOG_FILE}
		PYTHON_BASH=python3

		echo 20 > ${PROGRESS_FILE}
		echo "$(date +'[%F %T]') install required dependancies"
		apt-get install -y python-virtualenv virtualenv >> ${LOG_FILE}

else
    echo "Invalid version $PYTHON_VERSION" >> ${LOG_FILE}

		# install dependancy
		# download and make python3.7
		# source https://unix.stackexchange.com/questions/332641/how-to-install-python-3-6

		echo 10 > ${PROGRESS_FILE}
		echo "$(date +'[%F %T]') update package list"
		apt-get update > ${LOG_FILE}

		echo 20 > ${PROGRESS_FILE}
		echo "$(date +'[%F %T]') install required dependancies"
		apt-get install -y make build-essential libssl-dev zlib1g-dev libbz2-dev \
		  libreadline-dev libsqlite3-dev wget curl llvm libncurses5-dev libncursesw5-dev \
		  xz-utils tk-dev libffi-dev liblzma-dev python-virtualenv virtualenv >> ${LOG_FILE}

		echo 30 > ${PROGRESS_FILE}
		echo "$(date +'[%F %T]') download Python-3.7"
		cd /tmp
		wget https://www.python.org/ftp/python/3.7.7/Python-3.7.7.tgz >> ${LOG_FILE}

		echo 40 > ${PROGRESS_FILE}
		echo "$(date +'[%F %T]') unzip archive Python-3.7"
		tar xvf Python-3.7.7.tgz >> ${LOG_FILE}
		cd Python-3.7.7

		echo 50 > ${PROGRESS_FILE}
		echo "$(date +'[%F %T]') configure Python-3.7"
		./configure --enable-optimizations  --prefix=/usr/local --enable-shared \
		  LDFLAGS="-Wl,-rpath /usr/local/lib" --with-ensurepip=install >> ${LOG_FILE}

		# trèèèès long le make
		echo 60 > ${PROGRESS_FILE}
		export NUMCPUS=`grep -c '^processor' /proc/cpuinfo`
		echo "$(date +'[%F %T]') make Python-3.7 with $NUMCPUS threads"
		make -j$NUMCPUS >> ${LOG_FILE}

		echo 80 > ${PROGRESS_FILE}
		echo "$(date +'[%F %T]') altinstall Python-3.7"
		make altinstall >> ${LOG_FILE}

		# fix error:
		# python3.7: error while loading shared libraries: libpython3.7m.so.1.0: cannot open shared object file: No such file or directory
		export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/local/lib
		PYTHON_BASH=python3.7
		#
		whereis python >> ${LOG_FILE}

fi

echo 90 > ${PROGRESS_FILE}
echo "$(date +'[%F %T]') install python dependencies"
# dans le rep du daemon python:
cd ${BASEDIR}/daemon
virtualenv -p ${PYTHON_BASH} env
source env/bin/activate
pip install -r requirements.txt >> ${LOG_FILE}

echo 100 > ${PROGRESS_FILE}
echo "$(date +'[%F %T]') Everything is successfully installed!"

rm ${PROGRESS_FILE}
