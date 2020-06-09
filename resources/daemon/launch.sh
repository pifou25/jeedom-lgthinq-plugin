#!/bin/bash
# check for requirements

source env/bin/activate
echo "call launch.py $@"
./launch.py "$@"
