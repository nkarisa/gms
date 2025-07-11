FROM serversideup/php:8.3-fpm-nginx

# --- Build Arguments ---
ARG BASE_URL=""
ARG LOGTAIL_TOKEN=""
ARG DB_HOST=""
ARG DB_PASS=""
ARG CI_ENVIRONMENT="production"
ARG SHA256_PASSWORD_SALT=""
ARG IMAGE_APP_PATH="prod"

# --- Environment Variables ---
ENV NGINX_WEBROOT=/var/www/html/public
ENV SSL_MODE=off
ENV PHP_DISPLAY_ERRORS=1

# --- System Setup (Root User) ---
USER root

# Install necessary PHP extensions AND gettext for envsubst
RUN apt-get update && \
    apt-get install -y gettext-base git && \
    install-php-extensions bcmath intl mysqli && \
    mkdir -p ${NGINX_WEBROOT} && \
    chown -R www-data:www-data /var/www/html && \
    rm -rf /var/lib/apt/lists/* # Clean up apt cache to keep image small

# Switch back to the non-root user for subsequent operations
USER www-data

# --- Application Setup (www-data User) ---

WORKDIR /var/www/html/$IMAGE_APP_PATH

COPY --chown=www-data:www-data composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader

COPY --chown=www-data:www-data . .

# Move env file into place and substitute variables using envsubst
# This assumes your .env file template uses ${VAR} syntax for envsubst

RUN envsubst \
    '$$BASE_URL $$LOGTAIL_TOKEN $$CI_ENVIRONMENT $$SHA256_PASSWORD_SALT $$DB_HOST $$DB_PASS' \
    < env > .env.tmp && \
    mv .env.tmp .env

# If your .env file template doesn't directly use ${VAR} for all values,
# and you need specific string replacements, you would still use sed
# after envsubst or instead of it for those specific cases.
# Example if your .env was exactly like the original and you didn't convert it to envsubst syntax:
# RUN mv env .env && \
#     sed -i "s|app.baseURL = 'http://localhost/'|app.baseURL = ${BASE_URL}|g" .env && \
#     sed -i "s|LOGTAIL_TOKEN = token|LOGTAIL_TOKEN = ${LOGTAIL_TOKEN}|g" .env && \
#     sed -i "s|CI_ENVIRONMENT = development|CI_ENVIRONMENT = ${CI_ENVIRONMENT}|g" .env && \
#     sed -i "s|SHA256-PASSWORD-SALT = salt|SHA256-PASSWORD-SALT = ${SHA256_PASSWORD_SALT}|g" .env && \
#     sed -i "s|database.default.hostname = localhost|database.default.hostname = ${DB_HOST}|g" .env && \
#     sed -i "s|database.read.hostname = localhost|database.read.hostname = ${DB_HOST}|g" .env && \
#     sed -i "s|database.write.hostname = localhost|database.write.hostname = ${DB_HOST}|g" .env && \
#     sed -i "s|database.default.password = password|database.default.password = ${DB_PASS}|g" .env && \
#     sed -i "s|database.read.password = password|database.read.password = ${DB_PASS}|g" .env && \
#     sed -i "s|database.write.password = password|database.write.password = ${DB_PASS}|g" .env