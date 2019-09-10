#!/bin/bash

# OPTIONAL: in case composer returns an error while fetching github repositories,
# configure composer to use personal access token:
# php /app/composer.phar config -g github-oauth.github.com [GITHUB TOKEN]

# update composer dependencies only if /app/vendor does not yet exist
[ ! -d "/app/vendor" ] && php /app/composer.phar update --no-dev

# create database if it doesn't exist
[ ! -s "/app/storage/database" ] && sqlite3 /app/storage/database < /app/schema.sql

# create folders if it doesn't exist
mkdir -p /app/data
mkdir -p /app/log
mkdir -p /app/storage
mkdir -p /app/storage/hjerson
mkdir -p /app/storage/precomputed_ngrams
mkdir -p /app/storage/ter
mkdir -p /app/storage/sacrebleu
mkdir -p /app/temp/cache

# watch
php -f /app/www/index.php Background:Watcher:Watch --folder=/app/data &

# serve
php -S 0.0.0.0:8080 -t /app/www
