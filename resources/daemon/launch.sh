#!/bin/bash
# activate virtualenv and launch python script

BASEDIR=$(dirname "$BASH_SOURCE")
cd ${BASEDIR}
source env/bin/activate
echo "call $BASEDIR srv.py $*"
$(cat python.cmd) srv.py "$@"
