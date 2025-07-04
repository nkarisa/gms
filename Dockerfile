FROM serversideup/php:8.3-fpm-nginx

# Define a build argument
ARG BASE_URL=""
ARG LOGTAIL_TOKEN=""
ARG DB_HOST=""
ARG DB_PASS=""
ARG CI_ENVIRONMENT="development"
ARG SHA256_PASSWORD_SALT=""

# Add the NGINX_WEBROOT environment variable
ENV NGINX_WEBROOT=/var/www/html/public
ENV SSL_MODE=off
ENV PHP_DISPLAY_ERRORS=1

USER root

# Install the intl extension with root permissions
RUN install-php-extensions bcmath intl mysqli

USER www-data

COPY --chown=www-data:www-data composer.json .
# COPY --chown=www-data:www-data composer.lock .
# Run composer update as www-data user
RUN composer update

COPY --chown=www-data:www-data . .

RUN mv env .env

RUN sed -i "s|app.baseURL = 'http://localhost/'|app.baseURL = ${BASE_URL}|g" .env
RUN sed -i "s|LOGTAIL_TOKEN = token|LOGTAIL_TOKEN = ${LOGTAIL_TOKEN}|g" .env
RUN sed -i "s|CI_ENVIRONMENT = development|CI_ENVIRONMENT = ${CI_ENVIRONMENT}|g" .env
RUN sed -i "s|SHA256-PASSWORD-SALT = salt|SHA256-PASSWORD-SALT = ${SHA256_PASSWORD_SALT}|g" .env

RUN sed -i "s|database.default.hostname = localhost|database.default.hostname = ${DB_HOST}|g" .env
RUN sed -i "s|database.read.hostname = localhost|database.read.hostname = ${DB_HOST}|g" .env
RUN sed -i "s|database.write.hostname = localhost|database.write.hostname = ${DB_HOST}|g" .env

RUN sed -i "s|database.default.password = password|database.default.password = ${DB_PASS}|g" .env
RUN sed -i "s|database.read.password = password|database.read.password = ${DB_PASS}|g" .env
RUN sed -i "s|database.write.password = password|database.write.password = ${DB_PASS}|g" .env
