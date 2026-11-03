# syntax=docker/dockerfile:1.7
# Production image for the SBBlog console: nginx + PHP-FPM 8.4 on port 8080, run as www-data.
# Built in CI and tagged with the commit sha (see .github/workflows/deploy.yml).
# Local development uses docker/dev.Dockerfile instead.

ARG PHP_IMAGE=serversideup/php:8.4-fpm-nginx@sha256:cdefb0bcd48c7d150919fe876db171a4a94e83a3cf6581af62899bdb3e4f4532

# --- PHP base with the extensions the console needs ---------------------------------------
FROM ${PHP_IMAGE} AS base
USER root
# gd: webp variants of uploads (ProcessMedia). exif: image orientation. intl and bcmath: Laravel helpers.
RUN install-php-extensions gd exif intl bcmath
USER www-data

# --- Composer dependencies -----------------------------------------------------------------
FROM base AS vendor
WORKDIR /var/www/html
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

# --- Frontend build (Wayfinder runs artisan, so this stage has PHP too) ---------------------
FROM base AS assets
USER root
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && corepack enable \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./
RUN corepack install && pnpm install --frozen-lockfile
COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
# Throwaway key: only route generation runs here, nothing is encrypted.
RUN composer dump-autoload --no-scripts \
    && APP_KEY=base64:$(head -c 32 /dev/urandom | base64) DB_CONNECTION=sqlite DB_DATABASE=:memory: \
       sh -c 'php artisan wayfinder:generate --with-form && pnpm build'

# --- Final image ---------------------------------------------------------------------------
FROM base AS app
WORKDIR /var/www/html

ENV PHP_OPCACHE_ENABLE=1 \
    LOG_CHANNEL=stderr \
    # Caches are built at start, from the runtime .env. Migrations run in deploy.sh, never here.
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false \
    AUTORUN_LARAVEL_STORAGE_LINK=true \
    SSL_MODE=off \
    # The CSP, session cookies and the preload Link header outgrow the 8k default.
    NGINX_FASTCGI_BUFFER_SIZE=32k \
    NGINX_FASTCGI_BUFFERS="16 16k"

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/app/public storage/logs bootstrap/cache

EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/up || exit 1
