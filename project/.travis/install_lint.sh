#!/usr/bin/env sh
set -ev

mkdir --parents "${HOME}/bin"

composer global require sllh/composer-lint:@stable --prefer-dist --no-interaction

gem install yaml-lint
