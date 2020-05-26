#!/bin/bash
# start docker container wideq server
# require Dockerfile
app="wideqsrv.test"
docker build -t ${app} .
docker run -d -p 5000:5000 \
  --name=${app} ${app}
