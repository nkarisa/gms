#!/bin/sh
# List of variables to substitute, matching what ECS will provide
VARS_TO_SUBST='BASE_URL LOGTAIL_TOKEN CI_ENVIRONMENT SHA256_PASSWORD_SALT DB_HOST DB_PASS'

# Generate the .env file from the template, substituting actual environment variables
envsubst "$VARS_TO_SUBST" < env > .env.tmp && mv .env.tmp .env

# Execute the main application command
exec "$@"