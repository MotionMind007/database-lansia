#!/bin/sh
set -e

if [ "${SYNC_PUBLIC_ASSETS:-true}" = "true" ] && [ -d /opt/lansia-papua-public ]; then
    mkdir -p /var/www/html/public
    rm -rf /var/www/html/public/build
    cp -a /opt/lansia-papua-public/. /var/www/html/public/
fi

# Cache config/routes/views at runtime (when env is available)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations if AUTO_MIGRATE is set
if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
