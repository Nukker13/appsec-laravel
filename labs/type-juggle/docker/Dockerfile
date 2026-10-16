FROM php:8.3.23-fpm-alpine3.21

LABEL authors="Evgeny F."

RUN apk add --no-cache sqlite-dev && docker-php-ext-install pdo_sqlite

WORKDIR /labs/type_juggle
