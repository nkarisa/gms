FROM serversideup/php:8.3-fpm-nginx

# Add the NGINX_WEBROOT environment variable
ENV NGINX_WEBROOT=/var/www/html/public
ENV SSL_MODE=off
ENV PHP_DISPLAY_ERRORS=1

USER root

# Install the intl extension with root permissions
RUN install-php-extensions bcmath intl mysqli

USER www-data

COPY --chown=www-data:www-data composer.json .
COPY --chown=www-data:www-data composer.lock .
# Run composer update as www-data user
RUN composer update

COPY --chown=www-data:www-data . .
RUN mv .env.docker .env

