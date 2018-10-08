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

COPY . /var/www/html
RUN ln -s /var/www/html/.docker/startup.sh /startup.sh

WORKDIR /var/www/html
ENTRYPOINT /startup.sh
