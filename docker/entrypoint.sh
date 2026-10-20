#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -qE '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
mkdir -p "$(dirname "$DB_FILE")"
touch "$DB_FILE"

php artisan migrate --force --no-interaction --seed

exec php -S 0.0.0.0:8000 -t public docker/server.php
