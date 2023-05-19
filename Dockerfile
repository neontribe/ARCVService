FROM tobybatch/php:8.1-apache as builder
LABEL maintainer="tobias@neontribe.co.uk"

ADD . /opt/app

RUN touch .env
ENV COMPOSER_HOME=/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV DATABASE_URL="sqlite://tmp/db.sqlite3"
RUN mkdir -p /opt/app/.git/hooks/ && \
    apt update && apt install -y default-mysql-client vim htop && \
    composer --no-ansi install --working-dir=/opt/app --no-dev --optimize-autoloader && \
    composer --no-ansi clearcache && \
    cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
    sed -i "s/expose_php = On/expose_php = Off/g" /usr/local/etc/php/php.ini && \
    sed -i "s/;opcache.enable=1/opcache.enable=1/g" /usr/local/etc/php/php.ini && \
    sed -i "s/;opcache.memory_consumption=128/opcache.memory_consumption=256/g" /usr/local/etc/php/php.ini && \
    sed -i "s/;opcache.interned_strings_buffer=8/opcache.interned_strings_buffer=24/g" /usr/local/etc/php/php.ini && \
    sed -i "s/;opcache.max_accelerated_files=10000/opcache.max_accelerated_files=100000/g" /usr/local/etc/php/php.ini && \
    sed -i "s/opcache.validate_timestamps=1/opcache.validate_timestamps=0/g" /usr/local/etc/php/php.ini && \
    sed -i "s/session.gc_maxlifetime = 1440/session.gc_maxlifetime = 604800/g" /usr/local/etc/php/php.ini && \
    mkdir -p /opt/app/var/logs && chmod 777 /opt/app/var/logs && \
    sed "s/128M/-1/g" /usr/local/etc/php/php.ini-development > /opt/app/php-cli.ini && \
    chown -R www-data:www-data /opt/app/var /usr/local/etc/php/php.ini

FROM builder as final

ENV APP_DEBUG true
ENV APP_ENV local
ENV APP_KEY base64:5/euYB2rELcumOH7lKf8aOd4aOb5GAO6J/I1ykDDIPk:
ENV APP_LOG_LEVEL debug
ENV APP_NAME ARCVService
ENV APP_SEEDS 'Dev'
ENV APP_TIMEZONE Europe/London
ENV APP_URL http://0.0.0.0:8000
ENV APP_VER 1.9.0
ENV ARC_MARKET_URL https://voucher-staging.alexandrarose.org.uk
ENV ARC_SCHOOL_MONTH 9
ENV ARC_SERVICE_DOMAIN arcv-service.test
ENV ARC_STORE_DOMAIN arcv-store.test
ENV BROADCAST_DRIVER log
ENV CACHE_DRIVER file
ENV DB_CONNECTION mysql
ENV DB_DATABASE lamp
ENV DB_HOST db
ENV DB_PASSWORD lamp
ENV DB_PORT 3306
ENV DB_USERNAME lamp
ENV MAIL_DRIVER log
ENV MAIL_ENCRYPTION null
ENV MAIL_FROM_ADDRESS from@example.com
ENV MAIL_FROM_NAME 'Mailer Name'
ENV MAIL_HOST smtp.mailtrap.io
ENV MAIL_PASSWORD null
ENV MAIL_PORT 2525
ENV MAIL_TO_ADMIN_ADDRESS to@example.com
ENV MAIL_TO_ADMIN_NAME 'Admin Name'
ENV MAIL_TO_DEVELOPER_NAME 'User Support'
ENV MAIL_TO_DEVELOPER_TEAM arc@neontribe.co.uk
ENV MAIL_USERNAME null
ENV PASSWORD_CLIENT 1
ENV PASSWORD_CLIENT_SECRET secret
ENV QUEUE_DRIVER sync
ENV SESSION_DRIVER file
ENV SESSION_SECURE_COOKIE false

ENTRYPOINT /opt/app/.docker/docker-entrypoint.sh
