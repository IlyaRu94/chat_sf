FROM php:7.4.29-fpm
#файл для запуска server.php и его зависимостей

#RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql
RUN docker-php-ext-install pdo mysqli pdo_mysql

RUN docker-php-ext-install sockets

#COPY --from=composer /usr/bin/composer /usr/bin/composer

 RUN apt-get update && apt-get install -y \
     git \
     curl \
     zip \
     vim \
     unzip


COPY . /public/var/www/
#COPY ./.env /public/var/www/.env

#RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer
#ENV COMPOSER_ALLOW_SUPERUSER=1
#RUN ./ composer install


CMD sleep 10 && php /public/var/www/server.php

