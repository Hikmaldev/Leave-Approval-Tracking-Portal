#!/bin/sh
# Shared entrypoint for the app, worker, and scheduler services.
set -eu

cd /var/www/html

# Make sure runtime directories exist and are writable, including on a
# freshly created app-storage volume.
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/private \
    storage/app/public \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Create the public storage symlink when missing. Private attachments are
# streamed by the application, so Nginx does not need this link.
if [ ! -e public/storage ] && [ ! -L public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

# One-shot migrations. Enabled on the "app" service only (RUN_MIGRATIONS=1)
# so the worker and scheduler do not race it.
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    echo "Waiting for database ${DB_HOST:-db}:${DB_PORT:-3306}..."

    until php -r "exit(@fsockopen(getenv('DB_HOST') ?: 'db', (int) (getenv('DB_PORT') ?: 3306)) ? 0 : 1);"; do
        sleep 2
    done

    php artisan migrate --force
fi

exec "$@"
