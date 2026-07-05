FROM composer:latest AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --no-scripts

FROM php:8.4-apache

RUN docker-php-ext-install pdo_mysql && a2enmod rewrite

COPY --from=composer /app/vendor /var/www/html/vendor

COPY . /var/www/html

RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

RUN if [ ! -f /var/www/html/storage/database/migrated ]; then \
        php artisan migrate --force && \
        touch /var/www/html/storage/database/migrated; \
    fi

EXPOSE 80
