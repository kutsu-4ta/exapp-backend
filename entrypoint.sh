#!/bin/sh
set -e

# Cloud Run は $PORT を動的に設定する（デフォルト 8080）
PORT="${PORT:-8080}"

# 外部DB（常時起動）への接続確認。タイムアウトを短縮
echo "Checking DB connectivity..."
MAX_WAIT=30
WAITED=0
until pg_isready -h "${DB_HOST}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -q; do
  if [ "$WAITED" -ge "$MAX_WAIT" ]; then
    echo "ERROR: DB not ready after ${MAX_WAIT}s"
    exit 1
  fi
  sleep 2
  WAITED=$((WAITED + 2))
done
echo "DB is ready."

php artisan migrate --force

if [ "$APP_ENV" = "production" ]; then
  echo "Starting production server on port ${PORT}"

  php artisan config:cache
  php artisan route:cache
  php artisan view:cache

  exec php -S "0.0.0.0:${PORT}" -t public
else
  echo "Starting local server on port ${PORT}"

  php artisan config:clear

  exec php artisan serve --host=0.0.0.0 --port="${PORT}"
fi
