FROM dunglas/frankenphp:1-php8.4-alpine

WORKDIR /app

RUN apk add --no-cache git unzip \
    && install-php-extensions intl opcache zip

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    # Plain HTTP on port 80, no automatic HTTPS (local challenge setup).
    SERVER_NAME=":80" \
    APP_ENV=prod

# Install dependencies first to leverage the Docker layer cache.
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-scripts --no-autoloader --no-interaction --no-progress --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p var \
    && chmod +x docker/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
