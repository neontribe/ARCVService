FROM php:7.2-apache

# Release v1.5
ARG COMMIT_HASH=e2e348ca14e8dd119c0b539656fb0dc700d92c80

RUN apt update && apt install -y \
        acl \
        ccze \
        bash \
        libpng-dev \
        sudo \
        sqlite3 \
        vim \
        wget \
        default-mysql-client \
 && wget -O /tmp/composer-setup.php https://getcomposer.org/installer \
 && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && docker-php-ext-install \
        bcmath \
        gd \
        zip \
 && docker-php-ext-enable \
        bcmath \
        gd \
        zip \
 && docker-php-ext-install pdo pdo_mysql  \
 && docker-php-ext-enable pdo pdo_mysql \
 && sed -i 's#/var/www/html#/var/www/html/public#' /etc/apache2/sites-enabled/000-default.conf \
 && a2enmod rewrite

ADD https://github.com/neontribe/ARCVService/archive/${COMMIT_HASH}.tar.gz /var/www/html
RUN tar -zxv --strip 1 -f ${COMMIT_HASH}.tar.gz && rm *.tar.gz

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

COPY .docker/startup.sh /startup.sh
COPY .docker/vimrc /root/.vimrc
COPY .docker/php.ini /usr/local/etc/php/php.ini
RUN echo "127.0.1.1	arc.test arcv-service.test arcv-store.test" >> /etc/hosts

WORKDIR /var/www/html
ENTRYPOINT /startup.sh
