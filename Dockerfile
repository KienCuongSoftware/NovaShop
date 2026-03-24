FROM php:8.2-cli-bookworm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    default-mysql-client \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    gnupg \
 && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs \
 && docker-php-ext-install pdo_pgsql pdo_mysql zip \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

COPY . .

RUN curl -sS https://getcomposer.org/installer | php \
 && php composer.phar install --no-dev --optimize-autoloader --no-interaction \
 && rm -f composer.phar

RUN npm ci \
 && npm run build \
 && rm -rf node_modules

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
 && chmod -R ug+rwx /var/www/storage /var/www/bootstrap/cache

USER www-data

ENTRYPOINT ["sh", "/usr/local/bin/docker-entrypoint.sh"]
