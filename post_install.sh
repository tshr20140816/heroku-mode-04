#!/bin/bash

set -x

date

git clone --depth 1 https://github.com/tshr20140816/heroku-mode-04.git self_repository &

php -l www/redirect.php
php -l loggly.php

tar xf phpPgAdmin-5.1.tar.bz2

mv phpPgAdmin-5.1 www/phppgadmin

rm -f phpPgAdmin-5.1.tar.bz2

cp config.inc.php www/phppgadmin/conf/config.inc.php

wait

pushd self_repository
last_update=$(git log | grep Date | grep -o "\w\{3\} .\+$")
echo "${last_update}" > ../www/last_update.txt
popd

rm -rf self_repository

chmod 755 ./start_web.sh

date
