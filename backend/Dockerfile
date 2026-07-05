FROM composer:latest AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN chown -R www-data:www-data . && \
    composer install --no-dev --no-interaction

RUN composer install --no-dev --no-interaction --prefer-dist

FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql && a2enmod rewrite

COPY --from=composer /app/vendor /var/www/html/vendor

COPY . /var/www/html

RUN mkdir -p public/storage && chown -R www-data:www-data storage bootstrap/cache public/storage

EXPOSE 80
