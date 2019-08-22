#!/bin/bash

# OPTIONAL: in case composer returns an error while fetching github repositories,
# configure composer to use personal access token:
# php /app/composer.phar config -g github-oauth.github.com [GITHUB TOKEN]

# update composer dependencies only if /app/vendor does not yet exist
[ ! -d "/app/vendor" ] && php /app/composer.phar update --no-dev

# create database if it doesn't exist
[ ! -s "/app/storage/database" ] && sqlite3 /app/storage/database < /app/schema.sql

# create data folder if it doesn't exist
mkdir -p /app/data

# watch
php -f /app/www/index.php Background:Watcher:Watch --folder=/app/data &

# serve
php -S 0.0.0.0:8080 -t /app/www