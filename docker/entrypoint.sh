#!/bin/sh
set -e

VENDOR_AUTOLOAD="/var/www/vendor/autoload.php"

if [ "$1" = "php-fpm" ]; then
    # php-fpm (app container): install dependencies jika belum ada
    if [ ! -f "$VENDOR_AUTOLOAD" ]; then
        echo "[entrypoint] Running composer install..."
        cd /var/www && composer install \
            --no-interaction \
            --prefer-dist \
            --optimize-autoloader \
            --no-dev
    fi

    # Fix permissions
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

    echo "[entrypoint] Optimizing Laravel..."
    cd /var/www
    php artisan storage:link --force 2>/dev/null || true
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force
    echo "[entrypoint] Seeding database..."
    php artisan db:seed --force
    echo "[entrypoint] Starting php-fpm..."

else
    # queue / scheduler: vendor sudah ada di image (COPY . . + composer install)
    # Jalankan migrate juga agar tidak crash jika app belum selesai migrasi
    echo "[entrypoint] Running migrations (queue/scheduler)..."
    cd /var/www && php artisan migrate --force 2>/dev/null || true
fi

exec "$@"
