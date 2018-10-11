FROM php:7.0-apache

RUN apt update && apt install -y \
        acl \
        ccze \
        bash \
        libpng-dev \
        sudo \
        vim \
        wget \
        mysql-client \
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

COPY .docker/startup.sh /startup.sh
COPY .docker/vimrc /root/.vimrc
COPY .docker/php.ini /usr/local/etc/php/php.ini
RUN echo "127.0.1.1	arc.test arcv-service.test arcv-store.test" >> /etc/hosts

ENV APP_NAME docker
ENV APP_ENV local
ENV APP_DEBUG true
ENV APP_LOG_LEVEL debug
ENV APP_URL http //0.0.0.0 80
ENV APP_SEEDS Usability
ENV ARC_SCHOOL_MONTH 9
ENV BROADCAST_DRIVER log
ENV CACHE_DRIVER file
ENV SESSION_DRIVER file
ENV QUEUE_DRIVER sync
ENV SESSION_SECURE_COOKIE true
ENV MAIL_DRIVER log
ENV MAIL_HOST smtp.mailtrap.io
ENV MAIL_PORT 2525
ENV MAIL_USERNAME null
ENV MAIL_PASSWORD null
ENV MAIL_ENCRYPTION null

WORKDIR /var/www/html
ENTRYPOINT /startup.sh
