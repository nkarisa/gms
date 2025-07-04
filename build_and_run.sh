#!/bin/bash

# --- Configuration Variables ---
# You can customize these values
IMAGE_NAME="safina-app"
IMAGE_TAG="latest"
CONTAINER_NAME="safina-app-container"

# --- Default Build Arguments ---
# These defaults will be used if parameters are not provided
BUILD_ARG_BASE_URL_DEFAULT="http://localhost/"
BUILD_ARG_LOGTAIL_TOKEN_DEFAULT="your_logtail_token_default"
BUILD_ARG_DB_HOST_DEFAULT="host.docker.internal"
BUILD_ARG_DB_PASS_DEFAULT="Compassion123"
BUILD_ARG_CI_ENVIRONMENT_DEFAULT="development" # Often useful to default to dev
BUILD_ARG_SHA256_PASSWORD_SALT_DEFAULT="your_secure_salt_default"
BUILD_ARG_APP_PORT_DEFAULT=8080

# --- Parse Command Line Arguments ---
# Usage: ./build_and_run.sh <BASE_URL> <LOGTAIL_TOKEN> <DB_HOST> <DB_PASS> <CI_ENVIRONMENT> <SHA256_PASSWORD_SALT>

BUILD_ARG_BASE_URL="${1:-$BUILD_ARG_BASE_URL_DEFAULT}"            # Use $1 or default
BUILD_ARG_LOGTAIL_TOKEN="${2:-$BUILD_ARG_LOGTAIL_TOKEN_DEFAULT}"  # Use $2 or default
BUILD_ARG_DB_HOST="${3:-$BUILD_ARG_DB_HOST_DEFAULT}"              # Use $3 or default
BUILD_ARG_DB_PASS="${4:-$BUILD_ARG_DB_PASS_DEFAULT}"              # Use $4 or default
BUILD_ARG_CI_ENVIRONMENT="${5:-$BUILD_ARG_CI_ENVIRONMENT_DEFAULT}" # Use $5 or default
BUILD_ARG_SHA256_PASSWORD_SALT="${6:-$BUILD_ARG_SHA256_PASSWORD_SALT_DEFAULT}" # Use $6 or default
BUILD_ARG_APP_PORT="${7:-$BUILD_ARG_APP_PORT_DEFAULT}"

# --- Display current build arguments ---
echo "--- Docker Build Arguments ---"
echo "BASE_URL:             ${BUILD_ARG_BASE_URL}"
echo "LOGTAIL_TOKEN:        (hidden)"
echo "DB_HOST:              ${BUILD_ARG_DB_HOST}"
echo "DB_PASS:              (hidden)" # Never echo sensitive info directly
echo "CI_ENVIRONMENT:       ${BUILD_ARG_CI_ENVIRONMENT}"
echo "SHA256_PASSWORD_SALT: (hidden)" # Never echo sensitive info directly
echo "APP_PORT:             ${BUILD_ARG_APP_PORT}"
echo "------------------------------"

# --- Build the Docker Image ---
echo "--- Building Docker image: ${IMAGE_NAME}:${IMAGE_TAG} ---"
docker build \
  --build-arg BASE_URL="${BUILD_ARG_BASE_URL}" \
  --build-arg LOGTAIL_TOKEN="${BUILD_ARG_LOGTAIL_TOKEN}" \
  --build-arg DB_HOST="${BUILD_ARG_DB_HOST}" \
  --build-arg DB_PASS="${BUILD_ARG_DB_PASS}" \
  --build-arg CI_ENVIRONMENT="${BUILD_ARG_CI_ENVIRONMENT}" \
  --build-arg SHA256_PASSWORD_SALT="${BUILD_ARG_SHA256_PASSWORD_SALT}" \
  -t "${IMAGE_NAME}:${IMAGE_TAG}" .

# Check if the build was successful
if [ $? -ne 0 ]; then
  echo "Docker image build failed!"
  exit 1
fi

echo "Docker image built successfully."

# --- Run the Docker Container ---
echo "--- Stopping and removing existing container (if any): ${CONTAINER_NAME} ---"
# Stop and remove any existing container with the same name to avoid conflicts
docker stop "${CONTAINER_NAME}" > /dev/null 2>&1
docker rm "${CONTAINER_NAME}" > /dev/null 2>&1

echo "--- Running Docker container: ${CONTAINER_NAME} ---"
docker run \
  -d \
  -p ${BUILD_ARG_APP_PORT}:8080 \
  -p 443:443 \
  --name "${CONTAINER_NAME}" \
  "${IMAGE_NAME}:${IMAGE_TAG}"

# Check if the container started successfully
if [ $? -ne 0 ]; then
  echo "Docker container failed to start!"
  exit 1
fi

echo "Docker container '${CONTAINER_NAME}' started successfully in detached mode."
echo "You can access your application at http://localhost (and https://localhost if configured)."
echo "To view logs: docker logs ${CONTAINER_NAME}"
echo "To stop the container: docker stop ${CONTAINER_NAME}"
echo "To remove the container: docker rm ${CONTAINER_NAME}"