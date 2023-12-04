ARG PHP_VER="8.1"
ARG COMPOSER_VER="latest"
ARG BRANCH="develop"
ARG TIMEZONE="Europe/London"

FROM alpine:latest AS git-dev
ARG BRANCH
ARG TIMEZONE
RUN apk add --no-cache git && \
    git clone --depth 1 --branch ${BRANCH} https://github.com/neontribe/ARCVService.git /opt/project && \
    git -C /opt/project rev-parse HEAD > /opt/project/current_hash
WORKDIR /opt/project

FROM composer:${COMPOSER_VER} AS composer


FROM php:${PHP_VER}-fpm-alpine AS fpm-php-ext-base
RUN apk add --no-cache \
    # build-tools
    autoconf \
    dpkg \
    dpkg-dev \
    file \
    g++ \
    gcc \
    icu-dev \
    libatomic \
    libc-dev \
    libgomp \
    libmagic \
    linux-headers \
    m4 \
    make \
    mpc1 \
    mpfr4 \
    musl-dev \
    perl \
    re2c \
    # gd
    freetype-dev \
    libpng-dev \
    # icu
    icu-dev \
    icu-data-full \
    # ldap
    openldap-dev \
    libldap \
    # zip
    libzip-dev \
    # xsl
    libxslt-dev

FROM fpm-php-ext-base AS php-ext-gd
RUN docker-php-ext-configure gd \
        --with-freetype && \
    docker-php-ext-install -j$(nproc) gd

FROM fpm-php-ext-base AS php-ext-intl
RUN docker-php-ext-install -j$(nproc) intl

FROM fpm-php-ext-base AS php-ext-pdo_mysql
RUN docker-php-ext-install -j$(nproc) pdo_mysql

FROM fpm-php-ext-base AS php-ext-zip
RUN docker-php-ext-install -j$(nproc) zip

FROM fpm-php-ext-base AS php-ext-xsl
RUN docker-php-ext-install -j$(nproc) xsl

FROM fpm-php-ext-base AS php-ext-opcache
RUN docker-php-ext-install -j$(nproc) opcache

FROM fpm-php-ext-base AS php-ext-xdebug
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

FROM php:${PHP_VER}-fpm-alpine AS fpm-base
ARG BRANCH
ARG TIMEZONE
RUN apk add --no-cache \
        bash \
        coreutils \
        fcgi \
        freetype \
        git \
        haveged \
        icu \
        icu-data-full \
        libldap \
        libpng \
        libxslt-dev \
        libzip \
        nodejs \
        npm \
        tzdata && \
    touch /use_fpm && \
    npm -g i yarn
EXPOSE 9000
HEALTHCHECK --interval=20s --timeout=10s --retries=3 \
    CMD \
    SCRIPT_NAME=/ping \
    SCRIPT_FILENAME=/ping \
    REQUEST_METHOD=GET \
    cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1


FROM fpm-base AS base
WORKDIR /opt/project
ARG BRANCH
ARG TIMEZONE
LABEL maintainer="tobias@neontribe.co.uk"
LABEL licence="proprietary"

ENV BRANCH=${BRANCH}
ENV TIMEZONE=${TIMEZONE}
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone && \
    # make composer home dir
    mkdir /composer  && \
    chown -R www-data:www-data /composer
COPY ./.docker/entry-point.sh /entry-point.sh
COPY ./.docker/dbtest.php /dbtest.php
COPY ./.docker/passport-install.php /passport-install.php
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=php-ext-xsl /usr/local/etc/php/conf.d/docker-php-ext-xsl.ini /usr/local/etc/php/conf.d/docker-php-ext-xsl.ini
COPY --from=php-ext-xsl /usr/local/lib/php/extensions/no-debug-non-zts-20210902/xsl.so /usr/local/lib/php/extensions/no-debug-non-zts-20210902/xsl.so
COPY --from=php-ext-pdo_mysql /usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini /usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini
COPY --from=php-ext-pdo_mysql /usr/local/lib/php/extensions/no-debug-non-zts-20210902/pdo_mysql.so /usr/local/lib/php/extensions/no-debug-non-zts-20210902/pdo_mysql.so
COPY --from=php-ext-zip /usr/local/etc/php/conf.d/docker-php-ext-zip.ini /usr/local/etc/php/conf.d/docker-php-ext-zip.ini
COPY --from=php-ext-zip /usr/local/lib/php/extensions/no-debug-non-zts-20210902/zip.so /usr/local/lib/php/extensions/no-debug-non-zts-20210902/zip.so
COPY --from=php-ext-gd /usr/local/etc/php/conf.d/docker-php-ext-gd.ini /usr/local/etc/php/conf.d/docker-php-ext-gd.ini
COPY --from=php-ext-gd /usr/local/lib/php/extensions/no-debug-non-zts-20210902/gd.so /usr/local/lib/php/extensions/no-debug-non-zts-20210902/gd.so
COPY --from=php-ext-intl /usr/local/etc/php/conf.d/docker-php-ext-intl.ini /usr/local/etc/php/conf.d/docker-php-ext-intl.ini
COPY --from=php-ext-intl /usr/local/lib/php/extensions/no-debug-non-zts-20210902/intl.so /usr/local/lib/php/extensions/no-debug-non-zts-20210902/intl.so
COPY --from=php-ext-opcache /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini  /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
ENV LOG_CHANNEL=stderr
ENV DATABASE_URL=sqlite:///%kernel.project_dir%/storage/data/db.sqlite
ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_NAME=ARCVService
ENV APP_ENV=local
ENV APP_VER=${BRANCH}
ENV APP_DEBUG=true
ENV APP_LOG_LEVEL=debug
ENV APP_URL=http://localhost:8080
ENV APP_TIMEZONE=Europe/London
ENV APP_SEEDS='Dev'
ENV ARC_MARKET_URL=https://voucher-staging.alexandrarose.org.uk
ENV ARC_MVL_FILENAME=MVLReport.zip
ENV ARC_MVL_DISK=enc
ENV ARC_SCHOOL_MONTH=9
ENV ARC_SCOTTISH_SCHOOL_MONTH=8
ENV ARC_SERVICE_DOMAIN=arcv-service.test
ENV ARC_STORE_DOMAIN=arcv-store.test
ENV ARC_STORE_BUNDLE_MAX_VOUCHER_APPEND=100
ENV ARC_FIRST_DELIVERY_DATE=2019-09-26
ENV DB_CONNECTION=mysql
ENV DB_HOST=127.0.0.1
ENV DB_PORT=3306
ENV DB_DATABASE=homestead
ENV DB_USERNAME=homestead
ENV DB_PASSWORD=secret
ENV BROADCAST_DRIVER=log
ENV CACHE_DRIVER=file
ENV SESSION_DRIVER=file
ENV QUEUE_DRIVER=database
ENV SESSION_SECURE_COOKIE=false
ENV MAIL_MAILER=log
ENV MAIL_HOST=smtp.mailtrap.io
ENV MAIL_PORT=2525
ENV MAIL_USERNAME=null
ENV MAIL_PASSWORD=null
ENV MAIL_ENCRYPTION=null
ENV MAIL_FROM_ADDRESS=from@example.com
ENV MAIL_FROM_NAME='Mailer Name'
ENV MAIL_TO_ADMIN_ADDRESS=to@example.com
ENV MAIL_TO_ADMIN_NAME='Admin Name'
ENV MAIL_TO_DEVELOPER_TEAM=arc@neontribe.co.uk
ENV MAIL_TO_DEVELOPER_NAME='User Support'
ENV RUN_AS=""
ENV PATH=$PATH:/opt/project/node_modules/.bin
VOLUME [ "/opt/project/storage" ]
ENTRYPOINT /entry-point.sh

# developement build
FROM base AS dev
# copy kimai develop source
COPY --from=git-dev --chown=www-data:www-data /opt/project /opt/project
COPY ./.docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY --from=php-ext-xdebug /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY --from=php-ext-xdebug /usr/local/lib/php/extensions/no-debug-non-zts-20210902/xdebug.so /usr/local/lib/php/extensions/no-debug-non-zts-20210902/xdebug.so
RUN \
    export COMPOSER_HOME=/composer && \
    composer --no-ansi install --working-dir=/opt/project --optimize-autoloader && \
    composer --no-ansi clearcache && \
    cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    chown -R www-data:www-data /opt/project /usr/local/etc/php/php.ini && \
    chown -R www-data:www-data /opt/project /usr/local/etc/php/php.ini && \
    echo "error_reporting=E_ALL" > /usr/local/etc/php/conf.d/error_reporting.ini && \
    yarn
ENV APP_ENV=dev
ENV memory_limit=256M

# production build
FROM base AS prod
COPY --from=git-dev --chown=www-data:www-data /opt/project /opt/project
RUN export COMPOSER_HOME=/composer
RUN composer --no-ansi install --working-dir=/opt/project --no-dev --optimize-autoloader
RUN composer --no-ansi clearcache
RUN \
    cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
    sed -i "s/expose_php = On/expose_php = Off/g" /usr/local/etc/php/php.ini && \
    sed -i "s/;opcache.enable=1/opcache.enable=1/g" /usr/local/etc/php/php.ini && \
    sed -i "s/;opcache.memory_consumption=128/opcache.memory_consumption=256/g" /usr/local/etc/php/php.ini && \
    sed -i "s/;opcache.interned_strings_buffer=8/opcache.interned_strings_buffer=24/g" /usr/local/etc/php/php.ini && \
    sed -i "s/;opcache.max_accelerated_files=10000/opcache.max_accelerated_files=100000/g" /usr/local/etc/php/php.ini && \
    sed -i "s/opcache.validate_timestamps=1/opcache.validate_timestamps=0/g" /usr/local/etc/php/php.ini && \
    sed -i "s/session.gc_maxlifetime = 1440/session.gc_maxlifetime = 604800/g" /usr/local/etc/php/php.ini && \
    chown -R www-data:www-data /opt/project /usr/local/etc/php/php.ini && \
    yarn
ENV APP_ENV=prod
ENV SESSION_SECURE_COOKIE=true

# docker build -t arc:fpm .
