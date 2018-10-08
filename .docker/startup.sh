#!/bin/bash -e

ESC_SEQ="\x1b["
COL_RESET=$ESC_SEQ"39;49;00m"
COL_RED=$ESC_SEQ"31;01m"
COL_GREEN=$ESC_SEQ"32;01m"
COL_YELLOW=$ESC_SEQ"33;01m"
COL_BLUE=$ESC_SEQ"34;01m"
COL_MAGENTA=$ESC_SEQ"35;01m"
COL_CYAN=$ESC_SEQ"36;01m"

if [ ! -e /var/www/html/.env ]; then
    echo -e "░▀█▀░█▀█░▀█▀░▀█▀░▀█▀░█▀█░█░░░░░▀█▀░█▀█░█▀▀░▀█▀░█▀█░█░░░█░░\n░░█░░█░█░░█░░░█░░░█░░█▀█░█░░░░░░█░░█░█░▀▀█░░█░░█▀█░█░░░█░░\n░▀▀▀░▀░▀░▀▀▀░░▀░░▀▀▀░▀░▀░▀▀▀░░░▀▀▀░▀░▀░▀▀▀░░▀░░▀░▀░▀▀▀░▀▀▀\n"
    echo -e $COL_YELLOW"This may take some time..."$COL_RESET
    echo
    cp .env.docker .env
    composer install --no-interaction
    php artisan key:generate
    php artisan passport:keys
    php artisan migrate --seed --force

    echo -e "░▀█▀░█▀█░█▀▀░▀█▀░█▀█░█░░░█░░░░░█▀▀░█▀█░█▄█░█▀█░█░░░█▀▀░▀█▀░█▀▀\n░░█░░█░█░▀▀█░░█░░█▀█░█░░░█░░░░░█░░░█░█░█░█░█▀▀░█░░░█▀▀░░█░░█▀▀\n░▀▀▀░▀░▀░▀▀▀░░▀░░▀░▀░▀▀▀░▀▀▀░░░▀▀▀░▀▀▀░▀░▀░▀░░░▀▀▀░▀▀▀░░▀░░▀▀▀\n"
fi

find storage -type d -exec chmod 775 {} +
find storage -type f -exec chown 664 {} +
chown -R www-data:www-data storage

echo -e "░█▀▄░█▀▀░█▀█░█▀▄░█░█\n░█▀▄░█▀▀░█▀█░█░█░░█░\n░▀░▀░▀▀▀░▀░▀░▀▀░░░▀░"

apachectl -D FOREGROUND
