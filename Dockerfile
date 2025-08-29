FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
COPY composer.json composer.lock* ./
COPY . /var/www

COPY --chown=www-data:www-data . /var/www
RUN composer install --no-dev --optimize-autoloader
USER www-data
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
