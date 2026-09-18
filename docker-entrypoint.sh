#!/bin/sh
set -e

# Generate APP_KEY only if one isn't already set (first boot convenience;
# a real deployment should set APP_KEY as a fixed environment variable so
# it never changes between deploys/restarts, which would invalidate all
# existing sessions and encrypted data).
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Cache config/routes/views for production performance.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Apply any pending migrations. Safe to run on every boot: Laravel tracks
# which migrations have already run and skips them.
php artisan migrate --force

# Make the public/storage symlink for profile-photo uploads if it doesn't
# already exist (see config/filesystems.php's 'public' disk).
php artisan storage:link || true

exec "$@"
