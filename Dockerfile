FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev default-mysql-client

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir -p /var/run/php
RUN chown www-data:www-data /var/run/php

RUN echo '[global]' > /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'daemonize = no' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'error_log = /proc/self/fd/2' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo '' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo '[www]' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'user = www-data' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'group = www-data' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'listen = /var/run/php/php-fpm.sock' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'listen.owner = www-data' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'listen.group = www-data' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'listen.mode = 0666' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'pm = dynamic' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'pm.max_children = 5' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'pm.start_servers = 2' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'pm.min_spare_servers = 1' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'pm.max_spare_servers = 3' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'catch_workers_output = yes' >> /usr/local/etc/php-fpm.d/zz-docker.conf
RUN echo 'decorate_workers_output = no' >> /usr/local/etc/php-fpm.d/zz-docker.conf

RUN echo 'display_errors = On' >> /usr/local/etc/php/conf.d/docker-php.ini
RUN echo 'display_startup_errors = On' >> /usr/local/etc/php/conf.d/docker-php.ini
RUN echo 'error_reporting = E_ALL' >> /usr/local/etc/php/conf.d/docker-php.ini

RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

CMD ["php-fpm", "-F"]