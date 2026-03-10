# --- STAGE 1: Builder ---
FROM docker.io/serversideup/php:8.3-fpm-apache AS builder

USER root

# Get Composer directly from the official image (Correct Path)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install build-only dependencies
RUN apt-get update && \
    apt-get install -y git curl tar && \
    install-php-extensions bcmath intl mysqli

WORKDIR /app
COPY composer.json composer.lock ./
# Running with --no-scripts prevents issues if your hooks require the full app code
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts

# --- STAGE 2: Production ---
FROM docker.io/serversideup/php:8.3-fpm-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    SSL_MODE=off \
    PHP_DISPLAY_ERRORS=1 \
    DOCKER_COMPOSE_USAGE=0

USER root

# Install only essential runtime tools
RUN apt-get update && \
    apt-get install -y gettext-base dos2unix && \
    install-php-extensions bcmath intl mysqli && \
    rm -rf /var/lib/apt/lists/*

# Install New Relic (Cleanly in one layer)
RUN set -eux; \
    tempDir="$(mktemp -d)"; cd "$tempDir"; \
    NEW_RELIC_AGENT_VERSION=$(curl -s https://download.newrelic.com/php_agent/release/ | grep -oE '[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+' | head -n1); \
    curl -fsSL https://download.newrelic.com/php_agent/release/newrelic-php5-${NEW_RELIC_AGENT_VERSION}-linux.tar.gz | tar xzf -; \
    NR_INSTALL_USE_CP_NOT_LN=1 NR_INSTALL_SILENT=1 ./*/newrelic-install install; \
    rm -rf "$tempDir"

RUN mkdir -p /var/log/newrelic /usr/local/etc/php/conf.d/ && \
    chown -R www-data:www-data /var/log/newrelic /var/www/html && \
    chown -R www-data:root /usr/local/etc/php/conf.d/

COPY --chown=root:root ./entrypoint.d/envsubst.sh /etc/entrypoint.d/
RUN dos2unix /etc/entrypoint.d/envsubst.sh && chmod +x /etc/entrypoint.d/envsubst.sh

USER www-data
WORKDIR /var/www/html/

# Copy the vendor folder from the builder stage
COPY --from=builder --chown=www-data:www-data /app/vendor ./vendor
# Copy application code
COPY --chown=www-data:www-data . .

# Finalize symlinks for your environment paths
RUN ln -s /var/www/html/public /var/www/html/public/devint && \
    ln -s /var/www/html/public /var/www/html/public/stage && \
    ln -s /var/www/html/public /var/www/html/public/etsandbox