#!/usr/bin/env bash
set -e

setup

if [ ! -f vendor/autoload.php ]; then
	su "$UNIX_USERNAME" -c 'composer install'
fi

exec "$@"
