FROM serversideup/php:8.3-fpm-nginx
USER root

# Install the intl extension with root permissions
RUN install-php-extensions bcmath intl mysqli

USER www-data
COPY --chown=www-data:www-data . .
RUN mv env .env

# Run composer update as www-data user
RUN composer install