#!/bin/sh
set -e

# Cache config/routes/views at runtime (when env is available)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations if AUTO_MIGRATE is set
if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
