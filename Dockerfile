# =============================================================================
# Stage 1: Node.js — build dos assets frontend (React + Vite + Tailwind)
# =============================================================================
FROM node:22-alpine AS node-builder

# PHP é necessário para o vite-plugin-wayfinder gerar os tipos durante o build
RUN apk add --no-cache php83 php83-phar php83-mbstring php83-openssl php83-tokenizer php83-xml php83-xmlwriter php83-simplexml php83-dom php83-pdo php83-pdo_sqlite php83-sqlite3 php83-ctype php83-fileinfo php83-session \
    && ln -sf /usr/bin/php83 /usr/bin/php

WORKDIR /app

# Copia vendor (dependências PHP) para o artisan funcionar
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist

COPY . .

RUN npm ci --frozen-lockfile
RUN npm run build

# =============================================================================
# Stage 2: Composer — instala dependências PHP (sem dev)
# =============================================================================
FROM composer:2.8 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-scripts \
    --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev

# =============================================================================
# Stage 3: Imagem final de produção
# =============================================================================
FROM php:8.4-fpm-alpine AS production

RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

WORKDIR /var/www/html

COPY --from=composer-builder /app/vendor ./vendor
COPY --from=composer-builder /app .
COPY --from=node-builder /app/public/build ./public/build

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]