#!/bin/bash

ARTISAN=$(dirname $0)/../artisan
TARGET_DIR=$1

if [ -z $1 ]; then
    echo Dir not found
    exit 1
fi

for x in $(ls $1/*.arcx); do
    $ARTISAN arc:mvl:process $x
done
