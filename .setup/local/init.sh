#!/bin/bash

#first run, mysql init setup should be done
if [ ! -d "/var/lib/mysql/mysql" ]; then
    cd /local
    tar -xzf mysql.tgz
    mv mysql/* /var/lib/mysql/
    chown mysql:mysql -R /var/lib/mysql
fi

service mysql start
service php7.0-fpm start
service nginx start
service ssh start

mysql -uroot -proot < /local/schema.sql

#first run, git home should be initialised
if [ ! -d "/home/git/.ssh" ]; then
    mkdir /home/git/.ssh && chmod 0700 /home/git/.ssh
    touch /home/git/.ssh/authorized_keys && chmod 0600 /home/git/.ssh/authorized_keys
    chown -R git.git /home/git
    ln -s /local/codeisok/ssh_serve.php /home/git/ssh_serve.php
    mkdir /local/codeisok/repositories
    chown git.git /local/codeisok/repositories
fi


#sendmail init
hostname=$(hostname)
localhost=$(head -n 1 /etc/hosts | awk '{print $2}')
echo "127.0.0.1 $localhost $localhost.localdomain $hostname $hostname.localdomain" >> /etc/hosts
service sendmail start
