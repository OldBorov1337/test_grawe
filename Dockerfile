FROM php:8.3-cli-alpine

RUN apk add --no-cache sqlite-libs sqlite-dev icu-dev oniguruma-dev libzip-dev \
    && docker-php-ext-install pdo_sqlite bcmath \
    && apk del sqlite-dev icu-dev oniguruma-dev libzip-dev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist --no-progress

COPY . .
RUN composer dump-autoload --optimize \
    && chmod +x docker/entrypoint.sh \
    && mkdir -p /var/data \
    && chown -R www-data:www-data storage bootstrap/cache /var/data

EXPOSE 8000

ENTRYPOINT ["docker/entrypoint.sh"]
