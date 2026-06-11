# Stage 1: build Vite assets
FROM node:20-alpine AS assets

WORKDIR /build
COPY package.json package-lock.json* ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources/ ./resources/
RUN npm run build

# Stage 2: shared PHP runtime with required extensions
FROM php:8.4-fpm-alpine AS php-base

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        zip \
        intl \
        mbstring \
        opcache \
        gd \
        pcntl \
        bcmath \
    && apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .phpize-deps

COPY deploy/docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY deploy/docker/php/php-production.ini /usr/local/etc/php/conf.d/zzz-production.ini

# Stage 3: install production Composer dependencies against the same PHP platform
FROM php-base AS composer-deps

WORKDIR /deps
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Stage 4: final PHP-FPM production image
FROM php-base AS final

WORKDIR /var/www/html

# Copy application code (respects .dockerignore)
COPY . .

COPY --from=assets /build/public/build ./public/build
COPY --from=composer-deps /deps/vendor ./vendor

# Keep an immutable public copy outside the public named volume for runtime sync.
RUN mkdir -p /opt/lansia-papua-public \
    && cp -a public/. /opt/lansia-papua-public/ \
    && chown -R www-data:www-data storage bootstrap/cache public /opt/lansia-papua-public \
    && chmod -R 775 storage bootstrap/cache public

COPY deploy/docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

USER www-data

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
