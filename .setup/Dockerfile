FROM ubuntu:14.04
MAINTAINER Ilya Ageev <iamuyga@gmail.com>

ENV MYSQL_ROOT_PASSWORD root

RUN echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu trusty main" > /etc/apt/sources.list.d/ondrej-php-trusty.list \
    && apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C \
    && bash -c "debconf-set-selections <<< 'mysql-server-5.6 mysql-server/root_password password $MYSQL_ROOT_PASSWORD'" \
    && bash -c "debconf-set-selections <<< 'mysql-server-5.6 mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD'"

RUN apt-get update -y \
    && apt-get install -y --no-install-recommends \
        nginx \
        mysql-server-5.6 \
        php7.0-curl \
        php7.0-fpm \
        php7.0-gd \
        php7.0-imagick \
        php7.0-json \
        php7.0-mbstring \
        php7.0-mysqlnd \
        php7.0-soap \
        php7.0-xml \
        php7.0-cli \
        strace \
        vim \
        openssh-server \
        git \
        sendmail \
        imagemagick \
        exiftool \
    && rm -rf /var/lib/apt/lists/*

RUN useradd -ms /bin/bash  git && usermod -G www-data git

RUN mkdir -pv /local/logs
COPY etc/ /etc/
COPY local/ /local/

VOLUME /var/lib/mysql
VOLUME /home/git
VOLUME /local/codeisok

EXPOSE 80 22
CMD bash /local/init.sh && bash

