#!/bin/bash


if [ ! -d "/var/lib/mysql/mysql" ]; then
    cd /local
    tar -xzf mysql.tgz
    mv mysql/* /var/lib/mysql/
fi

service mysql start
service php7.0-fpm start
service nginx start

mysql -uroot -proot < /local/schema.sql
