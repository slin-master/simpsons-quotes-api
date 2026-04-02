#!/usr/bin/env sh

set -eu

cd /var/www/html

mkdir -p \
    bootstrap/cache \
    storage/database \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -f storage/database/database.sqlite ]; then
    touch storage/database/database.sqlite
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force >/dev/null 2>&1 || true
fi

if [ "${APP_ENV:-local}" = "production" ]; then
    php artisan config:cache >/dev/null 2>&1 || true
    php artisan route:cache >/dev/null 2>&1 || true
    php artisan event:cache >/dev/null 2>&1 || true
fi

exec "$@"
