FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql

RUN a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --no-interaction --no-scripts

RUN mkdir -p public/storage && chown -R www-data:www-data storage bootstrap/cache public/storage

EXPOSE 80
