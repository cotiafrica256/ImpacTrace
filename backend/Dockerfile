FROM dunglas/frankenphp:php8.2

# Install PHP extensions required by Laravel and the application
RUN install-php-extensions \
    pcntl \
    pdo_mysql \
    gd \
    zip \
    mbstring \
    exif \
    intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy Composer files first for Docker layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts

# Copy the Laravel application
COPY . .

# Create required Laravel directories
RUN mkdir -p \
    bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# Set permissions
RUN chmod -R 775 bootstrap/cache storage

# Generate storage symlink
RUN php artisan storage:link || true

# Railway uses the PORT environment variable
EXPOSE 80

# Start Laravel through FrankenPHP / Octane
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=80"]