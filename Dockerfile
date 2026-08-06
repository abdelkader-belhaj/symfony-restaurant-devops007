FROM composer:2.7 AS composer_builder
WORKDIR /app
COPY composer.json composer.lock ./
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_ALLOW_PLUGINS="symfony/flex symfony/runtime"
RUN composer install --no-interaction --prefer-dist --no-progress --no-dev --optimize-autoloader

FROM php:8.2-fpm

WORKDIR /var/www/symfony

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip gd opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php-fpm-pool.conf /usr/local/etc/php-fpm.d/zz-clear-env.conf

# Copy only the vendor directory from the composer builder stage (do NOT copy the composer binary)
COPY --from=composer_builder /app/vendor /var/www/symfony/vendor

# Copy application sources
COPY . .

RUN printf 'APP_ENV=prod\nAPP_SECRET=change_me_for_prod\nDATABASE_URL=mysql://app:app@db:3306/symfresto07?serverVersion=8.0.32&charset=utf8mb4\nADMIN_EMAIL=admin@restaurant.local\nADMIN_PASSWORD=admin123\nADMIN_NAME=Administrateur\n' > .env \
    && mkdir -p public/uploads var/cache var/log \
    && chown -R www-data:www-data public/uploads var \
    && chmod -R 775 public/uploads var \
    && chmod +x docker/entrypoint.sh \
    && php bin/console cache:warmup --env=prod --no-debug \
    && php bin/console asset-map:compile --env=prod --no-debug

EXPOSE 9000

ENTRYPOINT ["/bin/sh", "/var/www/symfony/docker/entrypoint.sh"]
CMD ["php-fpm"]
