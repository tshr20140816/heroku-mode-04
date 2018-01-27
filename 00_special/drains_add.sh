#!/bin/bash

set -x

if [ ! -v APP_NAME ]; then
  echo "Error : APP_NAME not defined."
  exit
fi

if [ ! -v LOGGLY_TOKEN ]; then
  echo "Error : LOGGLY_TOKEN not defined."
  exit
fi

./heroku drains:add https://logs-01.loggly.com/bulk/${LOGGLY_TOKEN}/tag/heroku,${APP_NAME} -a ${APP_NAME}
