FROM serversideup/php:8.3-fpm-apache

# --- Environment Variables ---
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV SSL_MODE=off
ENV PHP_DISPLAY_ERRORS=1
# Overwritten in Docker Compose file
ENV DOCKER_COMPOSE_USAGE=0 
# --- System Setup (Root User) ---
USER root

# Install necessary PHP extensions AND gettext for envsubst
RUN apt-get update && \
    apt-get install -y gettext-base git dos2unix curl tar ca-certificates gnupg && \
    install-php-extensions bcmath intl mysqli && \
    mkdir -p /var/www/html/ && \
    chown -R www-data:www-data /var/www/html/ && \
    rm -rf /var/lib/apt/lists/* # Clean up apt cache to keep image small

# This container ENTRYPOINT as per serversiderup/php
COPY  --chown=root:root ./entrypoint.d/envsubst.sh /etc/entrypoint.d/

RUN dos2unix /etc/entrypoint.d/envsubst.sh && \ 
    chmod +x /etc/entrypoint.d/envsubst.sh


RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
&& php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
&& php -r "unlink('composer-setup.php');" 

# Install New Relic PHP Agent (Debian glibc build)

RUN mkdir -p /var/log/newrelic && \
    chown www-data:www-data /var/log/newrelic && \
    chmod 755 /var/log/newrelic

RUN set -eux; \
    cd /tmp; \
    NEW_RELIC_AGENT_VERSION=$(curl -s https://download.newrelic.com/php_agent/release/ \
    | grep -oE '[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+' | head -n1); \
    echo "Installing New Relic PHP Agent v${NEW_RELIC_AGENT_VERSION}"; \
    curl -fsSL -o newrelic-php-agent.tar.gz \
    "https://download.newrelic.com/php_agent/release/newrelic-php5-${NEW_RELIC_AGENT_VERSION}-linux.tar.gz"; \
    tar xzf newrelic-php-agent.tar.gz; \
    NR_INSTALL_USE_CP_NOT_LN=1 NR_INSTALL_SILENT=1 ./*/newrelic-install install;

# Configure New Relic Agent (lean inline config)

# Replace this path with the actual location of your newrelic.ini file
# You can check this location by running 'php -i | grep "Scan this dir"' in your container
ENV NEWRELIC_INI_PATH=/usr/local/etc/php/conf.d/newrelic.ini

RUN { \
    echo "extension=newrelic.so"; \
    echo "newrelic.enabled=true"; \
    echo "newrelic.license=${NEW_RELIC_LICENSE_KEY}"; \
    echo "newrelic.appname=${NEW_RELIC_APP_NAME}"; \
    echo "newrelic.loglevel=${NEW_RELIC_LOG_LEVEL}"; \
    echo "newrelic.log=/dev/stdout"; \
    echo "newrelic.distributed_tracing_enabled=true"; \
    } > $NEWRELIC_INI_PATH

RUN sed -i \
    -e "s/newrelic.license = .*/newrelic.license = \"\${NEW_RELIC_LICENSE_KEY}\"/g" \
    -e "s/newrelic.appname = .*/newrelic.appname = \"\${NEW_RELIC_APP_NAME}\"/g" \
    -e "s/newrelic.appname = .*/newrelic.appname = \"\${NEW_RELIC_APP_NAME}\"/g" \
    $NEWRELIC_INI_PATH


# This command appends the setting, or updates it if it already exists, ensuring 'true' is set.
RUN sed -i -E 's/^;?\s*newrelic\.distributed_tracing_enabled\s*=.*/newrelic.distributed_tracing_enabled = true/' "$NEWRELIC_INI_PATH"

# This command appends the setting, or updates it if it already exists, using the quotes for the string value.
RUN sed -i -E 's/^;?\s*newrelic\.log\s*=.*/newrelic.log = "\/dev\/stdout"/' "$NEWRELIC_INI_PATH"


USER www-data

COPY --chown=www-data:www-data composer.json composer.lock ./

RUN composer update

WORKDIR /var/www/html/

RUN composer install --no-dev --optimize-autoloader

COPY --chown=www-data:www-data . .
RUN ln -s /var/www/html/public  /var/www/html/public/devint
RUN ln -s /var/www/html/public /var/www/html/public/stage
RUN ln -s /var/www/html/public /var/www/html/public/etsandbox





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
