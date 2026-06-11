# ═══════════════════════════════════════════
# Stage 1: Node — Build Vite assets
# ═══════════════════════════════════════════
FROM node:20-alpine AS assets

WORKDIR /build
COPY package.json package-lock.json* ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources/ ./resources/
RUN npm run build

# ═══════════════════════════════════════════
# Stage 2: Composer — Install PHP dependencies
# ═══════════════════════════════════════════
FROM composer:2 AS composer

WORKDIR /deps
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# ═══════════════════════════════════════════
# Stage 3: Final — PHP-FPM production image
# ═══════════════════════════════════════════
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        zip \
        intl \
        mbstring \
        opcache \
        gd \
        pcntl \
        bcmath

# Install Redis extension
RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .phpize-deps

# PHP production config
COPY deploy/docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY deploy/docker/php/php-production.ini /usr/local/etc/php/conf.d/zzz-production.ini

WORKDIR /var/www/html

# Copy application code (respects .dockerignore)
COPY . .

# Copy built assets from Node stage
COPY --from=assets /build/public/build ./public/build

# Copy vendor from Composer stage
COPY --from=composer /deps/vendor ./vendor

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint script handles runtime config
COPY deploy/docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

USER www-data

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
