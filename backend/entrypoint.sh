#!/bin/bash
set -e

# Generate .env file from environment variables if .env doesn't exist
if [ ! -f /app/.env ]; then
    cp /app/.env.example /app/.env
fi

# Escape backslashes, the "|" delimiter, and "&" (which sed treats as the
# matched text in replacements) so values containing special characters
# (e.g. MySQL connection strings with "/", "@", ":", "&", "#") don't break
# the sed substitution below.
escape_sed_replacement() {
    printf '%s' "$1" | sed -e 's/[\\|&]/\\&/g'
}

# Override .env with environment variables (for Railway deployment)
if [ ! -z "$APP_KEY" ]; then
    escaped_value=$(escape_sed_replacement "$APP_KEY")
    sed -i "s|APP_KEY=.*|APP_KEY=$escaped_value|" /app/.env
fi

if [ ! -z "$DB_HOST" ]; then
    escaped_value=$(escape_sed_replacement "$DB_HOST")
    sed -i "s|DB_HOST=.*|DB_HOST=$escaped_value|" /app/.env
fi

if [ ! -z "$DB_PORT" ]; then
    escaped_value=$(escape_sed_replacement "$DB_PORT")
    sed -i "s|DB_PORT=.*|DB_PORT=$escaped_value|" /app/.env
fi

if [ ! -z "$DB_DATABASE" ]; then
    escaped_value=$(escape_sed_replacement "$DB_DATABASE")
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=$escaped_value|" /app/.env
fi

if [ ! -z "$DB_USERNAME" ]; then
    escaped_value=$(escape_sed_replacement "$DB_USERNAME")
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=$escaped_value|" /app/.env
fi

if [ ! -z "$DB_PASSWORD" ]; then
    escaped_value=$(escape_sed_replacement "$DB_PASSWORD")
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$escaped_value|" /app/.env
fi

if [ ! -z "$APP_URL" ]; then
    escaped_value=$(escape_sed_replacement "$APP_URL")
    sed -i "s|APP_URL=.*|APP_URL=$escaped_value|" /app/.env
fi

if [ ! -z "$FRONTEND_URL" ]; then
    escaped_value=$(escape_sed_replacement "$FRONTEND_URL")
    sed -i "s|FRONTEND_URL=.*|FRONTEND_URL=$escaped_value|" /app/.env
fi

# Generate APP_KEY if not set
if grep -q "^APP_KEY=$" /app/.env; then
    php /app/artisan key:generate --force
fi

# Run migrations if needed (optional, comment out if you prefer manual migration)
# php /app/artisan migrate --force

# Start FrankenPHP
exec frankenphp run --config /app/Caddyfile
