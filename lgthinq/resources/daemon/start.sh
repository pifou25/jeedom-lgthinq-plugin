#!/bin/bash
app="wideqsrv.test"
docker build -t ${app} .
docker run -d -p 5000:5000 \
  --name=${app} ${app}
