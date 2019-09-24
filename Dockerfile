FROM php:7.1

RUN apt-get update && apt-get install -y openssl sqlite3 curl unzip git python libpq-dev

WORKDIR /app

# Install PHP Composer
RUN curl -sS https://getcomposer.org/installer | php

RUN docker-php-ext-install pdo pdo_pgsql pdo_mysql

# PHP config
RUN echo 'max_execution_time=1200' >> /usr/local/etc/php/conf.d/timeout.ini
RUN echo 'memory_limit=512M' >> /usr/local/etc/php/conf.d/memory.ini

# Install XDebug
RUN pecl install -f xdebug \
&& echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini;

# enable XDebug remote debugging
# host.docker.internal is a reserved host entry for Docker for Windows and Mac to allow a container to communicate with the host
# for Linux: use the ip address of the docker bridge (docker0)
# ENV XDEBUG_CONFIG="remote_host=host.docker.internal remote_port=9001 remote_enable=1 profiler_enable=1 profiler_output_dir=/app/profiler"
ENV XDEBUG_CONFIG="remote_host=host.docker.internal remote_port=9001 remote_enable=1"

COPY . /app

RUN chmod +x bin/run.sh
ENTRYPOINT ["bin/run.sh"]

EXPOSE 8080
