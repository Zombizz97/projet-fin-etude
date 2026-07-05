FROM composer:latest AS composer

FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip libxml2-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql bcmath gd zip mbstring exif intl xml ctype fileinfo soap sockets

RUN a2enmod rewrite

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --optimize-autoloader --no-dev --no-interaction --no-scripts

COPY . .

RUN composer dump-autoload --optimize --no-interaction

RUN mkdir -p public/storage && chown -R www-data:www-data storage bootstrap/cache public/storage

EXPOSE 80
