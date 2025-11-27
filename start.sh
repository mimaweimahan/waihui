#!/bin/bash
Path=$(cd "$(dirname "$0")"; pwd)
if [ "$1" = "settle" ]; then

    while (true) do
        current=`date "+%Y-%m-%d %H:%M:%S"`
        timeStamp=`date -d "$current" +%s`
        /www/server/php/74/bin/php "${Path}/stock.php" Settle/run/param/$timeStamp  > /dev/null
        echo $current
        sleep 5
    done

elif [ "$1" = "update_stock" ]; then

    while (true) do
        now=`date "+%Y-%m-%d %H:%M:%S"`
        /www/server/php/74/bin/php "${Path}/stock.php" Stock/run > /dev/null
        echo $now
        sleep 5
    done

else
    echo "no data"
fi
