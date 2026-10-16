FROM php:8.3.23-fpm-alpine3.21

LABEL authors="Evgeny F."

RUN sed -i 's/user = www-data/user = root/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/group = www-data/group = root/' /usr/local/etc/php-fpm.d/www.conf

COPY php.ini /usr/local/etc/php/conf.d/security.ini

WORKDIR /labs/insec_deser

CMD ["php-fpm", "--allow-to-run-as-root"]
