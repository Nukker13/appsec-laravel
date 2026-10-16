# Stage 1: Composer
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

# Stage 2: PHP
FROM php:8.3.23-fpm-alpine3.21

LABEL authors="Evgeny F."

RUN apk add --no-cache --virtual .build-deps \
            $PHPIZE_DEPS autoconf g++ make linux-headers \
    && docker-php-ext-install pdo_mysql \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

WORKDIR /labs/oauth_lab

ENV PHP_INI_DIR=/usr/local/etc/php
ENV PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d

COPY docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/other-xdebug.ini

COPY --from=vendor /app/vendor /labs/oauth_lab/vendor

COPY . /labs/oauth_lab
