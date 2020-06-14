#!/bin/bash
# check for requirements

BASEDIR=$(dirname "$BASH_SOURCE")
cd ${BASEDIR}
source env/bin/activate
pip3 list | grep -Ec "wideq|Flask|requests"
