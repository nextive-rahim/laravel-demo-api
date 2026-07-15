#!/usr/bin/env bash
set -e

# Render injects PORT; default to 8080 for local runs. Only ${PORT} is
# substituted so nginx's own $variables are preserved.
export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Cache framework config/routes for production performance.
php artisan config:cache
php artisan route:cache

# Apply database migrations (safe to run on every boot).
php artisan migrate --force

# Seed demo content the first time only (no-op once courses exist).
php artisan app:seed-if-empty

exec supervisord -n -c /etc/supervisor/supervisord.conf
