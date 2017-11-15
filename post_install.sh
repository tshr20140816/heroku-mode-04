#!/bin/bash

set -x

date

php -l www/redirect.php
php -l loggly.php

tar xf phpPgAdmin-5.1.tar.bz2

mv phpPgAdmin-5.1 www/phppgadmin

rm -f phpPgAdmin-5.1.tar.bz2

cp config.inc.php www/phppgadmin/conf/config.inc.php

chmod 755 ./start_web.sh

date
