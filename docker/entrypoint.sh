#!/bin/sh
set -e

# Install/update dependencies jika vendor belum ada atau composer.json berubah
if [ ! -f "/var/www/vendor/autoload.php" ]; then
    echo "[entrypoint] Running composer install..."
    cd /var/www && composer install \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-dev
fi

# Fix permissions storage & bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Hanya jalankan cache & link untuk php-fpm (bukan queue/scheduler)
if [ "$1" = "php-fpm" ]; then
    echo "[entrypoint] Optimizing Laravel..."
    cd /var/www
    php artisan storage:link --force 2>/dev/null || true
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo "[entrypoint] Starting php-fpm..."
fi

exec "$@"
