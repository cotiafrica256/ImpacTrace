#!/bin/bash
set -e

# Generate .env file from environment variables if .env doesn't exist
if [ ! -f /app/.env ]; then
    cp /app/.env.example /app/.env
fi

# Override .env with environment variables (for Railway deployment)
if [ ! -z "$APP_KEY" ]; then
    sed -i "s/APP_KEY=.*/APP_KEY=$APP_KEY/" /app/.env
fi

if [ ! -z "$DB_HOST" ]; then
    sed -i "s/DB_HOST=.*/DB_HOST=$DB_HOST/" /app/.env
fi

if [ ! -z "$DB_PORT" ]; then
    sed -i "s/DB_PORT=.*/DB_PORT=$DB_PORT/" /app/.env
fi

if [ ! -z "$DB_DATABASE" ]; then
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE/" /app/.env
fi

if [ ! -z "$DB_USERNAME" ]; then
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/" /app/.env
fi

if [ ! -z "$DB_PASSWORD" ]; then
    # Escape special characters in password for sed
    escaped_password=$(echo "$DB_PASSWORD" | sed 's/[\/&]/\\&/g')
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$escaped_password/" /app/.env
fi

if [ ! -z "$APP_URL" ]; then
    sed -i "s|APP_URL=.*|APP_URL=$APP_URL|" /app/.env
fi

if [ ! -z "$FRONTEND_URL" ]; then
    sed -i "s|FRONTEND_URL=.*|FRONTEND_URL=$FRONTEND_URL|" /app/.env
fi

# Generate APP_KEY if not set
if grep -q "^APP_KEY=$" /app/.env; then
    php /app/artisan key:generate --force
fi

# Run migrations if needed (optional, comment out if you prefer manual migration)
# php /app/artisan migrate --force

# Start FrankenPHP
exec frankenphp run --config /app/Caddyfile
