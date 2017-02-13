#!/bin/bash

CURRENT_DIR=`pwd`
DATABASE_DIR=$CURRENT_DIR/.setup/storage/database

docker run \
    --name gitphp \
    -p 80:80 \
    -p 443:443 \
    -p 3306:3306 \
    -v $CURRENT_DIR:/local/gitphp \
    -v $DATABASE_DIR:/var/lib/mysql \
    --rm \
    -it \
    gitphp
