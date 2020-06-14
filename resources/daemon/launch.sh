#!/bin/bash
# activate virtualenv and launch python script

BASEDIR=$(dirname "$BASH_SOURCE")
cd ${BASEDIR}
source env/bin/activate
echo "call $BASEDIR wideqServer.py $@"
`cat python.cmd` "wideqServer.py $@"
