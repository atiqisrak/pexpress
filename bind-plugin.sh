#!/bin/bash

# Script to bind mount the plugin directory to the existing WordPress container
# This allows real-time changes during development

set -e

# Configuration
CONTAINER_NAME="ssl-wireless-wordpress"
PLUGIN_NAME="polar-express"
PLUGIN_DIR="$(pwd)/pexpress"
CONTAINER_PLUGIN_PATH="/var/www/html/wp-content/plugins/${PLUGIN_NAME}"

# Check if container is running
if ! docker ps | grep -q "${CONTAINER_NAME}"; then
    echo "Error: Container ${CONTAINER_NAME} is not running"
    exit 1
fi

# Check if plugin directory exists
if [ ! -d "${PLUGIN_DIR}" ]; then
    echo "Error: Plugin directory ${PLUGIN_DIR} does not exist"
    exit 1
fi

# Check if mount already exists
if docker inspect "${CONTAINER_NAME}" | grep -q "${PLUGIN_NAME}"; then
    echo "Plugin mount already exists. Skipping..."
    exit 0
fi

# Stop the container
echo "Stopping container ${CONTAINER_NAME}..."
docker stop "${CONTAINER_NAME}"

# Commit the container to create a new image
echo "Creating snapshot..."
docker commit "${CONTAINER_NAME}" "${CONTAINER_NAME}-snapshot"

# Remove the old container
echo "Removing old container..."
docker rm "${CONTAINER_NAME}"

# Start new container with bind mount
echo "Starting container with bind mount..."
docker run -d \
    --name "${CONTAINER_NAME}" \
    --network ssl-wireless-sms-notification_wordpress-network \
    --mount type=bind,source="${PLUGIN_DIR}",target="${CONTAINER_PLUGIN_PATH}" \
    -p 8080:80 \
    "${CONTAINER_NAME}-snapshot"

echo "Plugin successfully bound to WordPress container!"
echo "Plugin path: ${CONTAINER_PLUGIN_PATH}"

