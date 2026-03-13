FROM php:8.3-fpm

ARG PUID=1000
ARG PGID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libsqlite3-dev \
        libwebp-dev \
        libxpm-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        pcntl \
        pdo_pgsql \
        pdo_sqlite \
        zip \
    && pecl install pcov redis \
    && docker-php-ext-enable pcov redis \
    && groupmod -o -g "${PGID}" www-data \
    && usermod -o -u "${PUID}" -g www-data www-data \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/conf.d/memory.ini /usr/local/etc/php/conf.d/memory.ini

WORKDIR /var/www/html

USER www-data
