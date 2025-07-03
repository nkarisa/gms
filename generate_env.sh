#!/bin/bash

# Start with an empty .env file or copy from env.example
cp env .env

# Loop through a predefined list of variables and populate them
# This is safer than iterating through all environment variables
# as it only includes variables explicitly defined as needed.

echo "app.baseURL=${BASE_URL}" >> .env
echo "LOGTAIL_TOKEN = ${LOGTAIL_TOKEN}" >> .env
echo "database.default.hostname = ${DB_HOST}" >> .env
echo "database.default.password = ${DB_PASS}" >> .env

echo "database.read.hostname = ${DB_HOST}" >> .env
echo "database.read.password = ${DB_PASS}" >> .env

echo "database.write.hostname = ${DB_HOST}" >> .env
echo "database.write.password = ${DB_PASS}" >> .env



# echo "DB_HOST=${DB_HOST}" >> .env
# echo "DB_PORT=${DB_PORT}" >> .env
# echo "DB_USER=${DB_USER}" >> .env
# echo "DB_PASSWORD=${DB_PASSWORD}" >> .env
# echo "API_KEY=${API_KEY}" >> .env
# echo "NODE_ENV=${NODE_ENV}" >> .env

# Example for a variable with type 'File' in GitLab CI/CD
# if [ -n "$MY_PRIVATE_KEY_FILE" ]; then
#   echo "MY_PRIVATE_KEY=$(cat $MY_PRIVATE_KEY_FILE)" >> .env
# fi

# You can add more complex logic here if needed
# For instance, conditional variables based on the environment

echo "Generated .env file:"
# For debugging, remove in production
cat .env