FROM php:8.5-fpm

# Docker CLI + compose plugin: the app, queue and scheduler containers drive the
# host daemon through the mounted socket (see docs/decisions/0002).
COPY --from=docker:cli /usr/local/bin/docker /usr/local/bin/docker
COPY --from=docker:cli /usr/local/libexec/docker/cli-plugins/docker-compose /usr/local/libexec/docker/cli-plugins/docker-compose
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pdo_mysql pcntl bcmath zip intl opcache redis

COPY php.ini /usr/local/etc/php/conf.d/zz-appsec.ini
