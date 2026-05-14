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
    echo "[entrypoint] Starting php-fpm..."

else
    # queue / scheduler: tunggu app container selesai install vendor (maks 120 detik)
    if [ ! -f "$VENDOR_AUTOLOAD" ]; then
        echo "[entrypoint] Waiting for vendor (max 120s)..."
        i=0
        while [ ! -f "$VENDOR_AUTOLOAD" ] && [ $i -lt 60 ]; do
            sleep 2
            i=$((i + 1))
        done
        if [ ! -f "$VENDOR_AUTOLOAD" ]; then
            echo "[entrypoint] ERROR: vendor not found after 120s, exiting."
            exit 1
        fi
    fi
fi

exec "$@"
