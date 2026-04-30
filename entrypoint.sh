#!/bin/sh
set -e

echo "Waiting for DB..."
until pg_isready -h "${DB_HOST}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -q; do
  sleep 2
done
echo "DB is ready."

# APP_KEY が未設定の場合は生成（Render ダッシュボードで設定することを推奨）
if [ -z "$APP_KEY" ]; then
  echo "WARNING: APP_KEY is not set. Generating a temporary key..."
  export APP_KEY=$(php artisan key:generate --show --no-interaction)
fi

php artisan migrate --force

if [ "$APP_ENV" = "production" ]; then
  echo "Running in production mode"

  php artisan config:cache
  php artisan route:cache
  php artisan view:cache

  php -S 0.0.0.0:8000 -t public
else
  echo "Running in local mode"

  php artisan config:clear

  php artisan serve --host=0.0.0.0 --port=8000
fi
