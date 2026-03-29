FROM php:8.4-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql opcache zip \
    && rm -rf /var/lib/apt/lists/*

# Configure git to accept dubious ownership (Windows COPY issue)
RUN git config --global --add safe.directory /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy entire project
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/var /var/www/html/public

# Health check script
RUN echo '<?php echo "OK";' > /var/www/html/public/health.php

USER www-data

EXPOSE 9000

CMD ["php-fpm"]