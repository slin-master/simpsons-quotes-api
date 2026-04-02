# syntax=docker/dockerfile:1.7

FROM php:8.5-fpm-alpine AS php-base

LABEL name="simpsons-quotes-api"

RUN apk add --no-cache \
    bash \
    curl \
    git \
    sqlite \
    sqlite-dev \
    && docker-php-ext-install pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM php-base AS development

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS linux-headers

COPY docker/app/conf.d/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini
COPY docker/app/entrypoint.sh /usr/local/bin/app-entrypoint

RUN chmod +x /usr/local/bin/app-entrypoint

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    XDEBUG_MODE=off composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .

RUN mkdir -p bootstrap/cache storage/database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && XDEBUG_MODE=off composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache /var/www/html/storage

EXPOSE 8080

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm", "-F"]

FROM php-base AS production

COPY docker/app/entrypoint.sh /usr/local/bin/app-entrypoint
COPY docker/app/conf.d/production.ini /usr/local/etc/php/conf.d/99-production.ini

RUN chmod +x /usr/local/bin/app-entrypoint

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .

RUN mkdir -p bootstrap/cache storage/database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache /var/www/html/storage

EXPOSE 8080

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm", "-F"]
