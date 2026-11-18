#!/bin/sh
# Starts the demo: creates the database on first run, migrates, seeds the demo data and serves the site.
set -e

mkdir -p /data

# The app key signs sessions. Generate it once and keep it in the data volume, so logins survive a restart.
if [ -z "$APP_KEY" ]; then
    if [ ! -f /data/app.key ]; then
        php -r 'echo "base64:" . base64_encode(random_bytes(32));' > /data/app.key
    fi
    APP_KEY="$(cat /data/app.key)"
    export APP_KEY
fi

if [ ! -f "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
fi

php artisan package:discover --ansi
php artisan migrate --force
php artisan db:seed --force

echo "SkillPath is running on http://localhost:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
