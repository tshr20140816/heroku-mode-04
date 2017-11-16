#!/bin/bash

set -x

export TZ=JST-9

httpd -V
httpd -M
php --version
whereis php

if [ ! -v LOGGLY_TOKEN ]; then
  echo "Error : LOGGLY_TOKEN not defined."
  exit
fi

if [ ! -v LOG_LEVEL ]; then
  export LOG_LEVEL="warn"
fi

if [ ! -v BASIC_USER ]; then
  echo "Error : BASIC_USER not defined."
  exit
fi

if [ ! -v BASIC_PASSWORD ]; then
  echo "Error : BASIC_PASSWORD not defined."
  exit
fi

printenv

export HOME_IP_ADDRESS=$(nslookup ${HOME_FQDN} 8.8.8.8 | grep ^A | grep -v 8.8.8.8 | awk '{print $2}')

url="https://logs-01.loggly.com/inputs/${LOGGLY_TOKEN}/tag/APP_START/"
curl -i -v -H 'content-type:text/plain' -d "${HOME_FQDN} ${HOME_IP_ADDRESS}" ${url}

htpasswd -c -b .htpasswd ${BASIC_USER} ${BASIC_PASSWORD}

vendor/bin/heroku-php-apache2 -C apache.conf www
