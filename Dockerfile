FROM php:8.2-fpm
RUN apt-get update && apt-get install -y libpq-dev libzip-dev zip && docker-php-ext-install pdo_pgsql opcache zip
WORKDIR /var/www/html
COPY . .