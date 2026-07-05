FROM composer:latest AS composer

FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql bcmath gd zip

RUN a2enmod rewrite

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --optimize-autoloader --no-dev --no-interaction

COPY . .

RUN chown -R www-data:www-data storage bootstrap/cache public/storage

EXPOSE 80
