#!/usr/bin/env bash
set -e

setup

# Wait for composer vendor
if [ ! -f vendor/autoload.php ]; then
    echo "Run 'composer install' command to continue."
fi
while [ ! -f vendor/autoload.php ]; do
    sleep 1
done
echo "Composer vendor found, processing."

exec "$@"
