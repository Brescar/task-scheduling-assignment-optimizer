FROM dunglas/frankenphp:1-php8.4-alpine

# Set server name to listen on standard port 80
ENV SERVER_NAME=":80"
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
    git \
    curl \
    bash \
    unzip \
    && install-php-extensions \
    intl \
    zip \
    opcache \
    pcntl

# Install latest Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy custom PHP configuration
COPY docker/backend/php.ini $PHP_INI_DIR/conf.d/99-custom.ini

WORKDIR /app

# Ensure dependencies exist on startup, then launch FrankenPHP
CMD ["sh", "-c", "if [ ! -f vendor/autoload.php ] && [ -f composer.json ]; then composer install --no-interaction; fi && frankenphp run --config /etc/caddy/Caddyfile"]
