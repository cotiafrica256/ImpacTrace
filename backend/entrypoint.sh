#!/bin/bash
set -e

# Generate .env file from environment variables if .env doesn't exist
if [ ! -f /app/.env ]; then
    cp /app/.env.example /app/.env
fi

# Set (or replace) a KEY=value line in /app/.env without ever passing the
# value through sed/regex substitution. MySQL connection strings and
# generated APP_KEYs routinely contain characters such as @, :, /, \, &, |
# and # that are meaningful to sed (delimiters, backreferences, escapes) and
# reliably broke the previous sed-based implementation (e.g. "sed: -e
# expression #1, char 46: number option to `s' command may not be zero").
#
# awk's -v assigns "value" as a plain string that is only ever concatenated
# into the output line, never interpreted as part of a regex or replacement
# expression, so no escaping of the value is required. Only "key" is used to
# build the match pattern, and all keys below are simple, fixed identifiers.
set_env_var() {
    local key="$1"
    local value="$2"
    local file="/app/.env"
    local tmp="${file}.tmp.$RANDOM.$RANDOM"

    awk -v key="$key" -v value="$value" '
        BEGIN { found = 0; plen = length(key) + 1 }
        substr($0, 1, plen) == key "=" { print key "=" value; found = 1; next }
        { print }
        END { if (!found) print key "=" value }
    ' "$file" > "$tmp" && mv "$tmp" "$file"
}

# Override .env with environment variables (for Railway deployment)
if [ ! -z "$APP_KEY" ]; then
    set_env_var "APP_KEY" "$APP_KEY"
fi

if [ ! -z "$DB_HOST" ]; then
    set_env_var "DB_HOST" "$DB_HOST"
fi

if [ ! -z "$DB_PORT" ]; then
    set_env_var "DB_PORT" "$DB_PORT"
fi

if [ ! -z "$DB_DATABASE" ]; then
    set_env_var "DB_DATABASE" "$DB_DATABASE"
fi

if [ ! -z "$DB_USERNAME" ]; then
    set_env_var "DB_USERNAME" "$DB_USERNAME"
fi

if [ ! -z "$DB_PASSWORD" ]; then
    set_env_var "DB_PASSWORD" "$DB_PASSWORD"
fi

if [ ! -z "$APP_URL" ]; then
    set_env_var "APP_URL" "$APP_URL"
fi

if [ ! -z "$FRONTEND_URL" ]; then
    set_env_var "FRONTEND_URL" "$FRONTEND_URL"
fi

# Generate APP_KEY if not set
if grep -q "^APP_KEY=$" /app/.env; then
    php /app/artisan key:generate --force
fi

# Install/verify Composer dependencies on every startup (ensures vendor/ is complete)
echo "Installing/verifying Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Run migrations (creates/updates all database tables)
php /app/artisan migrate --force

# Run seeders (creates demo accounts and test data - idempotent, safe to run multiple times)
# php /app/artisan db:seed --force

# Start FrankenPHP
exec frankenphp run --config /app/Caddyfile
