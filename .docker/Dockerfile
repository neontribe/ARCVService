FROM php:7.0-apache

RUN apt update && apt install -y \
        acl \
        bash \
        libpng-dev \
        sudo \
        wget
RUN wget -O /tmp/composer-setup.php https://getcomposer.org/installer
RUN php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install \
        bcmath \
        gd \
        zip
RUN docker-php-ext-enable \
        bcmath \
        gd \
        zip

RUN docker-php-ext-install pdo pdo_mysql 
RUN docker-php-ext-enable pdo pdo_mysql
RUN sed -i 's#/var/www/html#/var/www/html/public#' /etc/apache2/sites-enabled/000-default.conf
RUN a2enmod rewrite
RUN apt install -y mysql-client

COPY . /var/www/html
RUN ln -s /var/www/html/.docker/startup.sh /startup.sh

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
