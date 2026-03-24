FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    default-mysql-client \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
 && docker-php-ext-install pdo_pgsql pdo_mysql zip \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY . .

RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar install --no-dev --optimize-autoloader

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]