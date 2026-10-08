# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Leave Approval Tracking Portal - production container images.
#
# Targets:
#   app - PHP 8.3 FPM image running the Laravel application (and artisan).
#   web - self-contained Nginx image serving the static front end.
#
# Multi-arch: builds on linux/amd64 (local dev) and linux/arm64
# (Oracle Cloud Always Free Ampere A1).
# ---------------------------------------------------------------------------

# ------------------------------
# 1. Front-end assets (Vite)
# ------------------------------
FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# ------------------------------
# 2. Nginx (static front end)
# ------------------------------
FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Static files only: PHP requests are proxied to the "app" service.
COPY public /usr/share/nginx/html
COPY --from=assets /app/public/build /usr/share/nginx/html/build

# ------------------------------
# 3. PHP-FPM application
# ------------------------------
FROM php:8.3-fpm-bookworm AS app

ENV COMPOSER_ALLOW_SUPERUSER=1

# unzip is required by Composer when installing dist packages.
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

# Laravel requires the default PHP extensions (mbstring, dom, curl, ...)
# which the official image already enables; pdo_mysql is added here.
RUN docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath opcache pcntl

COPY docker/php/extra.ini /usr/local/etc/php/conf.d/zz-app.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies first so Docker can cache this layer.
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader

# Application source (secrets, vendor, node_modules and build output are
# excluded through .dockerignore).
COPY . .

# Vite build output produced in the assets stage.
COPY --from=assets /app/public/build ./public/build

# Finish autoloading; dump-autoload triggers package:discover through the
# post-autoload-dump Composer script.
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# Runtime directories; at runtime they are persisted through the
# "app-storage" volume mounted at /var/www/html/storage.
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/private \
        storage/app/public \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# ---------------------------------------------------------------
# 4. Single-container PaaS image (Railway, Koyeb, Render, ...)
#    Nginx + PHP-FPM in one container listening on $PORT.
#    This is the default final stage, so PaaS builders that use the
#    Dockerfile without a target get a runnable HTTP service.
# ---------------------------------------------------------------
FROM app AS paas

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx gettext-base \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

COPY docker/paas/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/paas/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

ENV PORT=8080
EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]
