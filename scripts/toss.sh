#!/bin/sh



/home/fidonet/bin/sql2pkt.pl
/home/fidonet/bin/hpt toss
/home/fidonet/bin/hpt pack

[ -e /tmp/flag_link ] &&  exit 1
touch /tmp/flag_link
/home/fidonet/bin/xml2sql.pl > /dev/null
/var/www/wfido/bin/fastlink.php > /dev/null
rm /tmp/flag_link



