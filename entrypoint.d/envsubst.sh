#!/bin/sh

VARS_TO_SUBST='BASE_URL \
                LOGTAIL_TOKEN \ 
                CI_ENVIRONMENT \
                SHA256_PASSWORD_SALT \
                DB_USER \
                DB_NAME \
                DB_HOST \
                DB_PASS \
                NEW_RELIC_LOG_LEVEL \
                NEW_RELIC_APP_NAME \
                NEW_RELIC_LOG_LEVEL'

envsubst "$VARS_TO_SUBST" < env > .env.tmp && mv .env.tmp .env