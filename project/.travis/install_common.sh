#!/usr/bin/env sh
set -ev

composer update --prefer-dist --no-interaction --prefer-stable ${COMPOSER_FLAGS}
