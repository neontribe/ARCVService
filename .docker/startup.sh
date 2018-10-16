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
APP_DEBUG=${APP_DEBUG}
APP_ENV=${APP_ENV}
APP_KEY=base64:5/euYB2rELcumOH7lKf8aOd4aOb5GAO6J/I1ykDDIPk=
APP_LOG_LEVEL=${APP_LOG_LEVEL}
APP_NAME=${APP_NAME}
APP_SEEDS=${APP_SEEDS}
APP_TIMEZONE=${APP_TIMEZONE}
APP_URL=${APP_URL}
APP_VER=${APP_VER}

ARC_MARKET_URL=${ARC_MARKET_URL}
ARC_SCHOOL_MONTH=${ARC_SCHOOL_MONTH}
ARC_SERVICE_DOMAIN=${ARC_SERVICE_DOMAIN}
ARC_STORE_DOMAIN=${ARC_STORE_DOMAIN}

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

# Set this to true for production envs
SESSION_SECURE_COOKIE=${SESSION_SECURE_COOKIE}

MAIL_DRIVER=${MAIL_DRIVER}
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT}
MAIL_USERNAME=${MAIL_USERNAME}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}
MAIL_FROM_NAME='${MAIL_FROM_NAME}'
MAIL_TO_ADMIN_ADDRESS=${MAIL_TO_ADMIN_ADDRESS}
MAIL_TO_ADMIN_NAME='${MAIL_TO_ADMIN_NAME}'
MAIL_TO_DEVELOPER_TEAM=${MAIL_TO_DEVELOPER_TEAM}
MAIL_TO_DEVELOPER_NAME='${MAIL_TO_DEVELOPER_NAME}'

PASSWORD_CLIENT=${PASSWORD_CLIENT}
PASSWORD_CLIENT_SECRET=${PASSWORD_CLIENT_SECRET}
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

echo -e "░█▀▄░█▀▀░█▀█░█▀▄░█░█\n░█▀▄░█▀▀░█▀█░█░█░░█░\n░▀░▀░▀▀▀░▀░▀░▀▀░░░▀░"
# apachectl -D FOREGROUND
php artisan serve --host=0.0.0.0
