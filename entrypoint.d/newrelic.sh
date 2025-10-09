#!/bin/bash

# --- Configuration Variables ---

# Define the project root directory where the template file resides.
# Assuming the template is copied here by the Dockerfile.
PROJECT_ROOT="/var/www/html"

# The standard directory for PHP configuration files.
PHP_CONF_DIR="/usr/local/etc/php/conf.d"

# Define the input template file path
TEMPLATE_FILE="$PROJECT_ROOT/newrelic.ini.temp"

# Define the final output file path. It must be named 'newrelic.ini'
OUTPUT_FILE="$PHP_CONF_DIR/newrelic.ini"

# Define the list of environment variables to substitute into the template.
VARS_TO_SUBST='NEW_RELIC_LICENSE_KEY \
                NEW_RELIC_APP_NAME'

# --- Execution ---

echo "Starting New Relic configuration from /entrypoint.d/newrelic.sh..."

# 0. Ensure the target configuration directory exists
if [ ! -d "$PHP_CONF_DIR" ]; then
    echo "Creating PHP configuration directory: $PHP_CONF_DIR"
    mkdir -p "$PHP_CONF_DIR"
fi

# 1. Check for the template file
if [ ! -f "$TEMPLATE_FILE" ]; then
    echo "Error: Template file '$TEMPLATE_FILE' not found! Skipping configuration."
    exit 0 
fi

# 2. Perform the substitution
echo "Substituting variables from environment into $TEMPLATE_FILE..."

# Use envsubst to replace the listed variables from the template,
# output to a temporary file in /tmp.
envsubst "$VARS_TO_SUBST" < "$TEMPLATE_FILE" > /tmp/newrelic.ini.tmp

# 3. Check substitution success and move the file
if [ $? -eq 0 ]; then
    # Move the substituted temporary file to the final PHP configuration path, named newrelic.ini
    mv /tmp/newrelic.ini.tmp "$OUTPUT_FILE" \
        && echo "Successfully created New Relic configuration at $OUTPUT_FILE"
else
    echo "Error during envsubst operation."
    rm -f /tmp/newrelic.ini.tmp
    exit 1
fi