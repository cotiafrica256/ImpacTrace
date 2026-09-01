FROM dunglas/frankenphp:php8.2

RUN install-php-extensions \
    ctype \
    curl \
    dom \
    fileinfo \
    filter \
    gd \
    hash \
    mbstring \
    openssl \
    pcre \
    pdo \
    pdo_mysql \
    session \
    tokenizer \
    xml \
    zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .

# Create Laravel required directories and make them writable
RUN mkdir -p \
    bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 bootstrap/cache storage

RUN php artisan storage:link || true

EXPOSE 80

CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=80"]