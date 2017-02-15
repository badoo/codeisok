#!/bin/bash

CURRENT_DIR=`pwd`
DATABASE_DIR=$CURRENT_DIR/.setup/storage/database
GIT_HOME=$CURRENT_DIR/.setup/storage/git

docker run \
    --name gitphp \
    -p 8080:80 \
    -p 2222:22 \
    -v $CURRENT_DIR:/local/gitphp \
    -v $DATABASE_DIR:/var/lib/mysql \
    -v $GIT_HOME:/home/git/ \
    --rm \
    -it \
    gitphp
