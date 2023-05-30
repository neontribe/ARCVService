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

COUNT=$(mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" -h "${DB_HOST}" "${DB_DATABASE}" -sN -e "select count(*) from information_schema.TABLES where TABLE_SCHEMA = '${DB_DATABASE}'" 2>/dev/null)
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

composer --no-ansi clearcache

# Do I have a GID in the container
# shellcheck disable=SC2153
GROUP_NAME=$(id -gn "$CURRENT_UID")
# shellcheck disable=SC2181
if [ "$?" != 0 ]; then
  CURRENT_GID=33
  GROUP_NAME=www-data
else
  CURRENT_GID=$(id -g "$CURRENT_UID")
fi

# Do I have a UID in the container
USER_NAME=$(id -un "${CURRENT_UID}")
if [ -z "$USER_NAME" ]; then
  echo arcuser:x:"$CURRENT_UID":"$CURRENT_GID"::/var/www:/usr/sbin/nologin >> /etc/passwd
  pwconv
fi

echo "Setting permission to $CURRENT_UID:$CURRENT_GID"

# Chown back any files created as root
find . -user root -exec chown "${USER_NAME}:${GROUP_NAME}" {} \;
chown -R "${USER_NAME}:${GROUP_NAME}" /opt/project/storage /opt/project/bootstrap/cache
# shellcheck disable=SC2086
su -c "php ./artisan serve --host=0.0.0.0" -s /bin/bash "$USER_NAME"
