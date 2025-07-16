FROM serversideup/php:8.3-fpm-nginx

# --- Build Arguments ---
# ARG BASE_URL="http://localhost:8090/"
# ARG LOGTAIL_TOKEN="your-logtail-token"
# ARG DB_HOST="host.docker.internal"
# ARG DB_PASS="Compassion123"
# ARG CI_ENVIRONMENT="devint"
# ARG SHA256_PASSWORD_SALT="salt-string"
# ARG IMAGE_APP_PATH="prod"

# --- Environment Variables ---
ENV NGINX_WEBROOT=/var/www/html
ENV SSL_MODE=off
ENV PHP_DISPLAY_ERRORS=1
ENV APP_DIR="devint"

# --- System Setup (Root User) ---
USER root

# Install necessary PHP extensions AND gettext for envsubst
RUN apt-get update && \
    apt-get install -y gettext-base git dos2unix && \
    install-php-extensions bcmath intl mysqli && \
    mkdir -p ${NGINX_WEBROOT} && \
    chown -R www-data:www-data ${NGINX_WEBROOT} && \
    rm -rf /var/lib/apt/lists/* # Clean up apt cache to keep image small

COPY  --chown=root:root ./entrypoint.d/envsubst.sh /etc/entrypoint.d/

# RUN chmod +x /var/www/html/entrypoint.sh
RUN dos2unix /etc/entrypoint.d/envsubst.sh && \ 
    chmod +x /etc/entrypoint.d/envsubst.sh

USER www-data

WORKDIR ${NGINX_WEBROOT}

COPY --chown=www-data:www-data composer.json composer.lock ./

COPY --chown=www-data:www-data default.conf /etc/nginx/conf.d/

RUN composer install --no-dev --optimize-autoloader

COPY --chown=www-data:www-data . .

RUN ln -s /var/www/html/public  /var/www/html/devint
RUN ln -s /var/www/html/public /var/www/html/stage

# ENTRYPOINT ["/var/www/html/entrypoint.sh"]

# Move env file into place and substitute variables using envsubst
# This assumes your .env file template uses ${VAR} syntax for envsubst

# RUN envsubst \
#     '$$BASE_URL $$LOGTAIL_TOKEN $$CI_ENVIRONMENT $$SHA256_PASSWORD_SALT $$DB_HOST $$DB_PASS' \
#     < env > .env.tmp && \
#     mv .env.tmp .env

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
