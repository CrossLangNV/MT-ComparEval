#!/bin/bash

php -f www/index.php Background:Watcher:Watch --folder=./data &

php -S 0.0.0.0:8080 -t ./www