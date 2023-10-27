#!/bin/bash -x

function checkDatabase() {
  echo "Wait for MySQL DB connection ..."
  echo -n "Checking DB"
  until php /dbtest.php "$DB_HOST" "$DB_DATABASE" "$DB_PORT" "$DB_USERNAME" "$DB_PASSWORD"; do
    echo -n "."
    sleep 3
  done
  echo " ✅ Connection established"
}

function handleStartup() {
  # in production we will have a .env mounted into the container, this will have (at least) a
  # APP_KEY, if we don't have a .env we will create one
  if [ ! -e /opt/project/.env ]; then
    if [ "$APP_ENV" == "production" ]; then
      echo "No .env file present."
      echo "Your are running a prod environment version but there is no .env file present"
      echo "You need to mount one into this container or the system cannot proceed."
      exit 1
    else
      touch .env
    fi
  fi

  grep APP_KEY .env
  # shellcheck disable=SC2181
  if [ "$?" != 0 ]; then
    echo "APP_KEY=''" > .env
    php /opt/project/artisan key:generate
  fi

  # These are idempotent, run them anyway
  php /opt/project/artisan migrate
  if [ "$APP_ENV" == "local" ] || [ "$APP_ENV" == "dev" ] || [ "$APP_ENV" == "development" ] ; then
    # check the DB, if there are no vouchers install fixtures
    voucher_count=$(/opt/project/artisan tinker --execute='print(App\Voucher::all()->count()))')
    if [ "$voucher_count" == "0" ]; then
      php /opt/project/artisan migrate:refresh --seed --force
    fi
  fi

  php /passport-install.php

  if [ -e /docker-entrypoint-initdb.d ]; then
    for filename in /docker-entrypoint-init.d/*; do
      if [ "${filename##*.}" == "sh" ]; then
        # shellcheck disable=SC1090
        source /docker-entrypoint-initdb.d/"$filename"
      fi
    done
  fi
  yarn production
}

checkDatabase
handleStartup

if [ -n "$RUN_AS" ]; then
  GROUP_ID=${RUN_AS#*:}
  USER_ID=${RUN_AS%:*}  # drops substring from last occurrence of `SubStr` to end of string

  GROUP_NAME=$(id -ng "$GROUP_ID")
  if [ -z "$GROUP_NAME" ]; then
      addgroup --gid "$GROUP_ID" arcuser
      GROUP_NAME=arcuser
  fi

  USER_NAME=$(id -n "$USER_ID")
  if [ -z "$USER_NAME" ]; then
      adduser -G "$GROUP_NAME" -u "$USER_ID" arcuser
      USER_NAME=arcuser
  fi
  sed -i "s/user = www-data/user = $USER_NAME/g" /usr/local/etc/php-fpm.d/www.conf
  sed -i "s/group = www-data/group = $GROUP_NAME/g" /usr/local/etc/php-fpm.d/www.conf

  chown -R $USER_NAME:$GROUP_NAME /opt/project/storage
fi

exec php-fpm

exit
