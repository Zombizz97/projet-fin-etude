FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql

RUN a2enmod rewrite

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --optimize-autoloader --no-dev

RUN php artisan config:cache && php artisan route:cache && php artisan view:cache
