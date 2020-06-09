#!/bin/bash
# check for requirements

source env/bin/activate
pip3 list | grep -Ec "wideq|Flask|requests"
