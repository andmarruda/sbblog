FROM php:8.3-fpm

ARG user
ARG uid

RUN apt update && apt install -y \
    git \
    curl \
    libcurl4-openssl-dev \
    sudo \
    nano \
    apt \
    wget \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libjpeg-dev \
    libwebp-dev \
    libgif-dev \
    libfreetype6-dev \
    zip \
    libzip-dev \
    unzip \
    software-properties-common && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath curl opcache xml zip && \
    docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ --with-webp=/usr/include/ && \
    docker-php-ext-install -j$(nproc) gd && \
    echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory-limit.ini

RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user && \
    chmod -R 777 /home/$user/.composer && \
    composer config --global process-timeout 2000

WORKDIR /var/www

USER $user

