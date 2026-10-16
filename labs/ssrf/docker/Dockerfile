FROM php:8.3.23-fpm-alpine3.21

LABEL authors="Evgeny F."

RUN apk add --no-cache git unzip zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /labs/SSRF

ARG APP_DIR

COPY ${APP_DIR}/composer.json ${APP_DIR}/composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-dev

COPY ${APP_DIR}/ ./

ENV PORT=8080
CMD ["sh", "-lc", "php -S 0.0.0.0:${PORT}"]