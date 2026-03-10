# NovaShop - Laravel 12 (PHP 8.2)
FROM php:8.2-cli

WORKDIR /var/www

# Install system deps + PHP extensions for Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install zip pdo pdo_mysql mbstring bcmath opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# App code
COPY . .

# Dependencies (no dev for production)
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Use PORT from environment (e.g. Render), default 10000
EXPOSE 10000

# Start Laravel (artisan serve is fine for small/medium deploy)
CMD ["sh", "-c", "php artisan storage:link 2>/dev/null || true && php artisan config:cache && php artisan route:cache && exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
