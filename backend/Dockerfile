FROM composer:latest AS composer

WORKDIR /app

COPY . .

RUN composer install --no-dev --no-interaction --no-scripts

FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql && a2enmod rewrite

COPY --from=composer /app/vendor /var/www/html/vendor

COPY --from=composer /app /var/www/html

RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/storage && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/storage && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/storage

EXPOSE 80
