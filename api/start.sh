#!/bin/bash
set -e

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Starting queue worker in background..."
php artisan queue:work --daemon --sleep=3 --tries=3 --timeout=60 &

echo "==> Starting web server on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
