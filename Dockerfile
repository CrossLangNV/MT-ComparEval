FROM php:7.1

RUN apt-get update && apt-get install -y sqlite3 curl unzip git python

WORKDIR /

COPY composer.json /

RUN curl -sS https://getcomposer.org/installer | php

# OPTIONAL: configure composer to use personal access token
# RUN php composer.phar config -g github-oauth.github.com <GITHUB TOKEN>

RUN php composer.phar update --no-dev
RUN mkdir data storage

COPY schema.sql /
RUN sqlite3 storage/database < schema.sql

RUN echo 'max_execution_time=1200' >> /usr/local/etc/php/conf.d/timeout.ini
RUN echo 'memory_limit=512M' >> /usr/local/etc/php/conf.d/memory.ini

COPY . /
RUN chmod +x bin/watchAndServe.sh

ENTRYPOINT ["bin/watchAndServe.sh"]

EXPOSE 8080