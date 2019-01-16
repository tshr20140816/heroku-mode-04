#!/bin/bash

set -x

if [ ! -v APP_NAME ]; then
  echo "Error : APP_NAME not defined."
  exit
fi

./heroku labs:enable runtime-dyno-metadata -a ${APP_NAME}
./heroku pg:backups schedule DATABASE_URL --at '05:00 Asia/Tokyo' -a ${APP_NAME}

echo "please deploy."
