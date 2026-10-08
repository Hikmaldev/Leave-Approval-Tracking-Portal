#!/bin/sh
# Single-container start script for PaaS platforms (Railway, Koyeb, Render,
# Northflank). Runs after docker/entrypoint.sh, which prepares storage and
# optionally runs migrations (RUN_MIGRATIONS=1).
set -eu

cd /var/www/html

# Railway's MySQL plugin exposes MYSQL* variables; map them when DB_* is not
# set explicitly.
if [ -z "${DB_HOST:-}" ] && [ -n "${MYSQLHOST:-}" ]; then
    export DB_HOST="$MYSQLHOST"
    export DB_PORT="${MYSQLPORT:-3306}"
    export DB_DATABASE="${MYSQLDATABASE:-railway}"
    export DB_USERNAME="${MYSQLUSER:-root}"
    export DB_PASSWORD="${MYSQLPASSWORD:-}"
fi

# PaaS platforms inject the port to listen on.
export PORT="${PORT:-8080}"

envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

# Send Nginx logs to the platform log collector.
ln -sf /dev/stdout /var/log/nginx/access.log
ln -sf /dev/stderr /var/log/nginx/error.log

php-fpm -D
exec nginx -g 'daemon off;'
