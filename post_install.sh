#!/bin/bash

set -x

date

export HOME2=${PWD}

git clone --depth 1 https://github.com/tshr20140816/heroku-mode-04.git /tmp/self_repository &

php -l www/redirect.php
php -l loggly.php

# ***** phppgadmin *****

pushd www
git clone --depth 1 https://github.com/phppgadmin/phppgadmin.git phppgadmin
cp ../config.inc.php phppgadmin/conf/
# cp ../Connection.php phppgadmin/classes/database/
popd

wait

# ***** self_repository *****

pushd /tmp/self_repository
last_update=$(git log | grep Date | grep -o "\w\{3\} .\+$")
echo "${last_update}" > ${HOME2}/www/last_update.txt
popd

chmod 755 ./start_web.sh

date
