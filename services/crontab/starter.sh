#!/bin/sh
# */10 * * * * ./starter.sh ./ script >> ./starter.log - использование скрипта
if ( /bin/ps axwu|/bin/grep -v "grep"|/bin/grep "$2\.php" ) then
  /bin/echo $(/bin/date -u '+%Y-%m-%d %T (UTC)'): already running $2.php
else
  /bin/echo $(/bin/date -u '+%Y-%m-%d %T (UTC)'): start $2.php
  /bin/su -c "/usr/bin/php -q $1$2.php >> $1$2.log &" -s /bin/sh www-data
fi;