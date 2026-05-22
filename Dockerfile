# docker/nginx/Dockerfile
# Nginx image yang sudah include config di dalamnya
# → tidak perlu bind mount file dari host ke container
# → tidak akan error OCI "mount directory onto a file" di Coolify

FROM nginx:1.25-alpine

# Copy nginx config langsung ke dalam image saat build
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Buat direktori public yang akan di-mount sebagai volume
RUN mkdir -p /var/www/public

EXPOSE 80

# Dockerfile (root)
# PHP-FPM image untuk service: app, queue, scheduler

FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git curl zip unzip \
    libpng-dev oniguruma-dev libxml2-dev \
    freetype-dev libjpeg-turbo-dev libzip-dev \
    icu-dev linux-headers

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www

# Copy seluruh source code ke dalam image
# (tidak lagi pakai bind mount .:/var/www di production)
COPY . .

# Install dependencies tanpa dev packages
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permission untuk storage dan cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]