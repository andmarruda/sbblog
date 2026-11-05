# Local development image: PHP-FPM 8.4 with the console's extensions, Node 22 and pnpm
# (Vite's Wayfinder plugin calls artisan, so PHP and Node live together). Used by docker-compose.yml.
FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git curl unzip ca-certificates gnupg postgresql-client \
        libpq-dev libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql gd exif intl bcmath zip pcntl opcache \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && corepack enable \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN { echo 'memory_limit=512M'; echo 'upload_max_filesize=20M'; echo 'post_max_size=24M'; \
      echo 'opcache.validate_timestamps=1'; echo 'opcache.revalidate_freq=0'; } > /usr/local/etc/php/conf.d/dev.ini

# Same uid/gid as the host user, so files created in the mounted project stay yours.
RUN groupadd -g "${GID}" dev 2>/dev/null || true \
    && useradd -m -u "${UID}" -g "${GID}" -s /bin/bash dev \
    && mkdir -p /home/dev/.cache /home/dev/.local/share/pnpm && chown -R "${UID}:${GID}" /home/dev

COPY docker/dev-entrypoint.sh /usr/local/bin/dev-entrypoint
RUN chmod 755 /usr/local/bin/dev-entrypoint

USER dev
ENV COREPACK_ENABLE_DOWNLOAD_PROMPT=0
WORKDIR /var/www/html
EXPOSE 9000 5173
ENTRYPOINT ["dev-entrypoint"]
CMD ["php-fpm", "-F"]
