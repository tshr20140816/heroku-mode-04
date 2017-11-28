#!/bin/bash

set -x

export TZ=JST-9

httpd -V
httpd -M
php --version
whereis php
cat /proc/version
curl --version
printenv

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

export HOME_IP_ADDRESS=$(nslookup ${HOME_FQDN} 8.8.8.8 | grep ^A | grep -v 8.8.8.8 | awk '{print $2}')

echo "${HOME_FQDN} ${HOME_IP_ADDRESS}" > /app/HOME_IP_ADDRESS

htpasswd -c -b .htpasswd ${BASIC_USER} ${BASIC_PASSWORD}

vendor/bin/heroku-php-apache2 -C apache.conf www
