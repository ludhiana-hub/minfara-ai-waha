#!/bin/sh
set -e

VENDOR_AUTOLOAD="/var/www/vendor/autoload.php"

if [ "$1" = "php-fpm" ]; then
    # php-fpm (app container): install/update dependencies
    echo "[entrypoint] Running composer install..."
    cd /var/www && composer install \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-dev 2>/dev/null || true

    # Fix permissions
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

    echo "[entrypoint] Optimizing Laravel..."
    cd /var/www
    php artisan storage:link --force 2>/dev/null || true
    php artisan config:cache  || true
    php artisan route:cache   || true
    php artisan view:cache    || true
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force || true
    echo "[entrypoint] Seeding database..."
    php artisan db:seed --force || true
    echo "[entrypoint] Publishing Swagger UI assets..."
    php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force 2>/dev/null || true
    echo "[entrypoint] Generating Swagger docs..."
    php artisan l5-swagger:generate 2>/dev/null || true
    echo "[entrypoint] Starting php-fpm..."

else
    # queue / scheduler: vendor sudah ada di image (COPY . . + composer install)
    # Jalankan migrate juga agar tidak crash jika app belum selesai migrasi
    echo "[entrypoint] Running migrations (queue/scheduler)..."
    cd /var/www && php artisan migrate --force 2>/dev/null || true
fi

exec "$@"
