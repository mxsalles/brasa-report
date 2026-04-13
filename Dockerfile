# =============================================================================
# Stage 1: PHP + Composer — instala deps e gera arquivos do Wayfinder
# =============================================================================
FROM php:8.4-cli-alpine AS php-builder

RUN apk add --no-cache unzip git postgresql-dev oniguruma-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

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

# Gera os arquivos TypeScript do Wayfinder (resources/js/actions/ e routes/)
RUN php artisan wayfinder:generate

# =============================================================================
# Stage 2: Node.js — build dos assets frontend (React + Vite + Tailwind)
# =============================================================================
FROM php:8.4-cli-alpine AS node-builder

# Instala Node.js no mesmo container que já tem PHP 8.4
RUN apk add --no-cache nodejs npm postgresql-dev oniguruma-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring

WORKDIR /app

# Recebe tudo do stage anterior (vendor + wayfinder gerado)
COPY --from=php-builder /app .

RUN npm ci --frozen-lockfile
RUN npm run build

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

COPY --from=php-builder /app/vendor ./vendor
COPY --from=php-builder /app .
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