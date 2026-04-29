#!/bin/sh

echo "Waiting for DB..."

sleep 5

# DB
php artisan migrate --force

# 環境ごとに分ける
if [ "$APP_ENV" = "production" ]; then
  echo "Running in production mode"

  php artisan config:cache
  php artisan route:cache
  php artisan view:cache

  # 本番（※後でFPMに置き換える前提）
  php -S 0.0.0.0:8000 -t public

else
  echo "Running in local mode"

  php artisan config:clear

  # 開発
  php artisan serve --host=0.0.0.0 --port=8000
fi
