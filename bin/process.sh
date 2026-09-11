#!/bin/bash

ARTISAN=$(dirname "$0")/../artisan
TARGET_DIR=$1

if [ -z "$TARGET_DIR" ]; then
    echo Dir not found
    exit 1
fi

for x in "$TARGET_DIR"/*.arcx; do
    [ -e "$x" ] || continue
    $ARTISAN arc:mvl:process "$x"
done
