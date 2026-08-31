# syntax=docker/dockerfile:1

FROM node:20-alpine AS frontend-build

WORKDIR /app

COPY frontend/package.json frontend/package-lock.json* ./
RUN npm ci

COPY frontend/ ./

ARG VITE_API_URL=/backend/public/api/v1
ARG VITE_MAIN_DOMAIN=
ENV VITE_API_URL=$VITE_API_URL
ENV VITE_MAIN_DOMAIN=$VITE_MAIN_DOMAIN

RUN npm run build

FROM composer:2 AS vendor

WORKDIR /app

COPY backend/composer.json backend/composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY backend/ ./

RUN composer dump-autoload --optimize --no-dev

FROM php:8.2-fpm-alpine AS runtime

RUN apk add --no-cache \
        nginx \
        wget \
        mariadb-client \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        libxml2-dev \
        curl-dev \
        oniguruma-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(getconf _NPROCESSORS_ONLN 2>/dev/null || echo 2)" \
        pdo_mysql \
        mbstring \
        xml \
        curl \
        gd \
        zip \
        bcmath \
    && apk del --no-network $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/* /tmp/pear /var/www/html/*

RUN addgroup -g 1000 -S www \
    && adduser -u 1000 -S www -G www

COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/entrypoint.sh /entrypoint.sh
COPY scripts/coolify-import-db.sh /usr/local/bin/import-database.sh
RUN chmod +x /entrypoint.sh /usr/local/bin/import-database.sh

WORKDIR /var/www/html

COPY --from=vendor --chown=www:www /app /var/www/html/backend
COPY --from=frontend-build --chown=www:www /app/dist /var/www/html/public

RUN mkdir -p /var/www/html/backend/storage/framework/{cache/data,sessions,views} \
    && mkdir -p /var/www/html/backend/storage/logs \
    && mkdir -p /var/www/html/backend/storage/app/public/tenants \
    && mkdir -p /var/www/html/backend/bootstrap/cache \
    && chown -R www:www /var/www/html/backend/storage /var/www/html/backend/bootstrap/cache

RUN sed -i 's/user = www-data/user = www/g' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/group = www-data/group = www/g' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^;clear_env = no/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
