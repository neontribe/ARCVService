#!/bin/bash -x

ESC_SEQ="\x1b["
COL_RESET=$ESC_SEQ"39;49;00m"
COL_RED=$ESC_SEQ"31;01m"
COL_GREEN=$ESC_SEQ"32;01m"
COL_YELLOW=$ESC_SEQ"33;01m"
COL_BLUE=$ESC_SEQ"34;01m"
COL_MAGENTA=$ESC_SEQ"35;01m"
COL_CYAN=$ESC_SEQ"36;01m"

cat <<EOF > .env
# Set this to true for production envs
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
ARC_MVL_FILENAME=MVLReport.zip
ARC_MVL_DISK=enc
ARC_SCHOOL_MONTH=9
ARC_STORE_BUNDLE_MAX_VOUCHER_APPEND=20

BROADCAST_DRIVER=${BROADCAST_DRIVER}

CACHE_DRIVER=${CACHE_DRIVER}

MAIL_DRIVER=${MAIL_DRIVER}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}
MAIL_FROM_NAME='${MAIL_FROM_NAME}'
MAIL_HOST=${MAIL_HOST}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_PORT=${MAIL_PORT}
MAIL_TO_ADMIN_ADDRESS=${MAIL_TO_ADMIN_ADDRESS}
MAIL_TO_ADMIN_NAME='${MAIL_TO_ADMIN_NAME}'
MAIL_TO_DEVELOPER_NAME='${MAIL_TO_DEVELOPER_NAME}'
MAIL_TO_DEVELOPER_TEAM=${MAIL_TO_DEVELOPER_TEAM}
MAIL_USERNAME=${MAIL_USERNAME}

PASSWORD_CLIENT=${PASSWORD_CLIENT}
PASSWORD_CLIENT_SECRET=${PASSWORD_CLIENT_SECRET}

QUEUE_DRIVER=${QUEUE_DRIVER}

SESSION_DRIVER=${SESSION_DRIVER}
SESSION_SECURE_COOKIE=${SESSION_SECURE_COOKIE}
EOF
cat .env

composer install

while [ "$MYSQL_IS_RUNNING" != 0 ]; do
  echo "Testing for mysql running..."
  mysql -u ${DB_USERNAME} -p${DB_PASSWORD} -h ${DB_HOST} ${DB_DATABASE} -e "show tables;"# 2>&1 > /dev/null
  MYSQL_IS_RUNNING="$?"
  echo "MYSQL_IS_RUNNING = $MYSQL_IS_RUNNING"
done

COUNT=$(mysql -u arcservice -parcservice arcservice -sN -e "select count(*) from information_schema.TABLES where TABLE_SCHEMA = '${DB_DATABASE}'" 2>/dev/null)
# The 20 below is arbitry. It's a test to see if we need to install
echo $COUNT

composer --no-ansi install --working-dir=/opt/app --dev --optimize-autoloader
composer --no-ansi clearcache

if [ -z "$COUNT" ] || [ "$COUNT" -lt 20 ]; then
  php ./artisan key:generate
  php ./artisan migrate --seed
  php ./artisan passport:install > passport.install
  echo "PASSWORD_CLIENT=1" >> .env
  echo -n "PASSWORD_CLIENT_SECRET=" >> .env
  grep -A 1 "Client ID: 2" passport.install | tail -n 1 | awk '{ print $3 }'
  touch .docker-installed
fi

if [ ! -z "$CURRENT_UID" and "$CURRENT_UID" != "33" ]; then
    chown -R "$CURRENT_UID" /opt/project
    echo arcuser:x:"$CURRENT_UID":"$CURRENT_UID"::/var/www:/usr/sbin/nologin >> /etc/passwd
    pwconv
fi

setfacl -R -m u:${CURRENT_UID}:rwX storage
su -c "php ./artisan serve --host=0.0.0.0" -s /bin/bash $(id -nu $CURRENT_UID)
