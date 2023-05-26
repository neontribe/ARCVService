#!/bin/bash -x

source $HOME/.bashrc

composer --no-ansi install --working-dir=/opt/project --dev --optimize-autoloader
yarn
yarn prod

while [ "$MYSQL_IS_RUNNING" != 0 ]; do
  echo "Testing for mysql running..."
  mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" -h "${DB_HOST}" "${DB_DATABASE}" -e "show tables;"# 2>&1 > /dev/null
  MYSQL_IS_RUNNING="$?"
  echo "MYSQL_IS_RUNNING = $MYSQL_IS_RUNNING"
done


COUNT=$(mysql -u arcservice -parcservice arcservice -sN -e "select count(*) from information_schema.TABLES where TABLE_SCHEMA = '${DB_DATABASE}'" 2>/dev/null)
# The 20 below is arbitrary. It's a test to see if we need to install

if [ -z "$COUNT" ] || [ "$COUNT" -lt 20 ]; then
  php ./artisan key:generate
  php ./artisan migrate --seed
  php ./artisan passport:install > passport.install
  echo "PASSWORD_CLIENT=1" >> .env
  echo -n "PASSWORD_CLIENT_SECRET=" >> .env
  grep -A 1 "Client ID: 2" passport.install | tail -n 1 | awk '{ print $3 }'
  touch .docker-installed
fi

if [ ! -z "$CURRENT_UID" ] && [ "$CURRENT_UID" != "33" ]; then
    echo arcuser:x:"$CURRENT_UID":"$CURRENT_UID"::/var/www:/usr/sbin/nologin >> /etc/passwd
    pwconv
fi

composer --no-ansi clearcache

# shellcheck disable=SC2086
chown -R "${CURRENT_UID}" /opt/project/storage
find . -user root -exec chown "${CURRENT_UID}" {} +
su -c "php ./artisan serve --host=0.0.0.0" -s /bin/bash "$(id -nu $CURRENT_UID)"
