FROM dunglas/frankenphp:php8.2

RUN install-php-extensions \
    pcntl \
    pdo_mysql \
    gd \
    zip \
    mbstring \
    exif \
    intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

# Create required directories BEFORE composer install (needed for post-autoload-dump scripts)
RUN mkdir -p \
    bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

# Create .env file from .env.example if it doesn't exist
RUN cp .env.example .env

# Generate APP_KEY if not present
RUN php artisan key:generate

RUN chmod -R 775 bootstrap/cache storage

RUN php artisan storage:link || true

# Copy Caddyfile
COPY Caddyfile /app/Caddyfile

# Copy and make entrypoint script executable
COPY entrypoint.sh /app/entrypoint.sh
RUN chmod +x /app/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/app/entrypoint.sh"]