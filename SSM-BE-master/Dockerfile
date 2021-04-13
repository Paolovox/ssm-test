FROM composer as vendor

WORKDIR /tmp/

COPY src/composer.json composer.json

RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --no-cache

FROM ottimis/php-all:latest

COPY src /var/www/html
COPY --from=vendor /tmp/vendor/ /var/www/html/vendor/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 8081

CMD ["apache2-foreground"]
