# Stage 1: Build Vite assets
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json .
RUN npm install --no-audit --no-fund
COPY . .
RUN npm run build

# Stage 2: PHP-FPM — app, queue, scheduler
FROM php:8.3-fpm-alpine

# install-php-extensions: gunakan binary pre-compiled, tidak perlu compile dari source
# jauh lebih hemat RAM dan cepat vs docker-php-ext-install
ADD https://github.com/mlocati/php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www

COPY . .
COPY --from=assets /app/public/build /var/www/public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
