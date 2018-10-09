#!/bin/bash -e

ESC_SEQ="\x1b["
COL_RESET=$ESC_SEQ"39;49;00m"
COL_RED=$ESC_SEQ"31;01m"
COL_GREEN=$ESC_SEQ"32;01m"
COL_YELLOW=$ESC_SEQ"33;01m"
COL_BLUE=$ESC_SEQ"34;01m"
COL_MAGENTA=$ESC_SEQ"35;01m"
COL_CYAN=$ESC_SEQ"36;01m"

cat <<EOF > .env
APP_NAME=${APP_NAME}
APP_ENV=${APP_ENV}
APP_KEY=base64:5/euYB2rELcumOH7lKf8aOd4aOb5GAO6J/I1ykDDIPk=
APP_DEBUG=${APP_DEBUG}
APP_LOG_LEVEL=${APP_LOG_LEVEL}
APP_URL=${APP_URL}
APP_SEEDS=${APP_SEEDS}

ARC_SCHOOL_MONTH=${ARC_SCHOOL_MONTH}

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=lamp
DB_USERNAME=lamp
DB_PASSWORD=lamp

BROADCAST_DRIVER=${BROADCAST_DRIVER}
CACHE_DRIVER=${CACHE_DRIVER}
SESSION_DRIVER=${SESSION_DRIVER}
QUEUE_DRIVER=${QUEUE_DRIVER}
SESSION_SECURE_COOKIE=${SESSION_SECURE_COOKIE}

MAIL_DRIVER=${MAIL_DRIVER}
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT}
MAIL_USERNAME=${MAIL_USERNAME}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION}
EOF

set +e
COUNT=$(mysql -u lamp -plamp -h db lamp -e 'select count(*) from users')
DB_READY="$?"
set -e
if [ "$DB_READY" != 0 ]; then
    echo -e "░▀█▀░█▀█░▀█▀░▀█▀░▀█▀░█▀█░█░░░░░▀█▀░█▀█░█▀▀░▀█▀░█▀█░█░░░█░░\n░░█░░█░█░░█░░░█░░░█░░█▀█░█░░░░░░█░░█░█░▀▀█░░█░░█▀█░█░░░█░░\n░▀▀▀░▀░▀░▀▀▀░░▀░░▀▀▀░▀░▀░▀▀▀░░░▀▀▀░▀░▀░▀▀▀░░▀░░▀░▀░▀▀▀░▀▀▀\n"
    echo -e $COL_YELLOW"This may take some time..."$COL_RESET
    echo
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
