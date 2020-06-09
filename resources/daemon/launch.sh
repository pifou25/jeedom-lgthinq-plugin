#!/bin/bash
# activate virtualenv and launch python script

source env/bin/activate
echo "call launch.py $@"
bash ./python.cmd < launch.py "$@"
