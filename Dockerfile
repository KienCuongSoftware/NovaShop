FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    git \
    curl

RUN docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www

COPY . .

RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar install --no-dev --optimize-autoloader

CMD php artisan serve --host=0.0.0.0 --port=10000