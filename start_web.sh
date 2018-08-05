#!/bin/bash

set -x

export TZ=JST-9

httpd -V
httpd -M | sort
php --version
whereis php
php -m
cat /proc/version
cat /proc/cpuinfo | grep 'model name' | head -n 1
curl --version
printenv | sort
  
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

rm apache.conf
wget https://raw.githubusercontent.com/tshr20140816/heroku-mode-04/master/apache.conf &

export HOME_IP_ADDRESS=$(nslookup ${HOME_FQDN} 8.8.8.8 | tail -n2 | grep -o '[0-9]\+.\+')
if [ -z "${HOME_IP_ADDRESS}" ]; then
  HOME_IP_ADDRESS=127.0.0.1
fi

url="https://logs-01.loggly.com/inputs/${LOGGLY_TOKEN}/tag/START/"

last_commit=$(curl -s https://github.com/tshr20140816/heroku-mode-06/commits/master.atom | grep Grit | grep -E -o Commit.+ | head -n 1)

curl -i -H 'content-type:text/plain' -d "S ${last_commit:7:-5} heroku-mode-06" ${url}

last_update=$(cat /app/www/last_update.txt)

curl -i -H 'content-type:text/plain' -d "S ${HEROKU_APP_NAME} * ${HOME_FQDN} ${HOME_IP_ADDRESS} * ${last_update}"  ${url}

htpasswd -c -b .htpasswd ${BASIC_USER} ${BASIC_PASSWORD}

wait

vendor/bin/heroku-php-apache2 -C apache.conf www
