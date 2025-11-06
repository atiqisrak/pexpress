#!/bin/bash

# Script to bind mount both Polar Express and EtherTech WOOTP plugins
# This allows real-time changes during development

set -e

# Configuration
CONTAINER_NAME="ssl-wireless-wordpress"
PEXPRESS_PLUGIN_NAME="polar-express"
PEXPRESS_PLUGIN_DIR="$(pwd)/pexpress"
PEXPRESS_CONTAINER_PATH="/var/www/html/wp-content/plugins/${PEXPRESS_PLUGIN_NAME}"

WOOTP_PLUGIN_NAME="ethertech-wootp"
WOOTP_PLUGIN_DIR="$(cd "$(dirname "$0")/../ethertech-wootp" && pwd)"
WOOTP_CONTAINER_PATH="/var/www/html/wp-content/plugins/${WOOTP_PLUGIN_NAME}"

echo "=== Binding Plugins to WordPress Container ==="
echo ""

# Check if container is running
if ! docker ps | grep -q "${CONTAINER_NAME}"; then
    echo "Error: Container ${CONTAINER_NAME} is not running"
    echo "Please start the WordPress container first"
    exit 1
fi

# Check if plugin directories exist
if [ ! -d "${PEXPRESS_PLUGIN_DIR}" ]; then
    echo "Error: Polar Express plugin directory ${PEXPRESS_PLUGIN_DIR} does not exist"
    exit 1
fi

if [ ! -d "${WOOTP_PLUGIN_DIR}" ]; then
    echo "Error: EtherTech WOOTP plugin directory ${WOOTP_PLUGIN_DIR} does not exist"
    exit 1
fi

echo "Found plugins:"
echo "  ✓ Polar Express: ${PEXPRESS_PLUGIN_DIR}"
echo "  ✓ EtherTech WOOTP: ${WOOTP_PLUGIN_DIR}"
echo ""

# Check current mounts
echo "Checking current mounts..."
PEXPRESS_MOUNTED=$(docker inspect "${CONTAINER_NAME}" 2>/dev/null | grep -q "${PEXPRESS_PLUGIN_NAME}" && echo "yes" || echo "no")
WOOTP_MOUNTED=$(docker inspect "${CONTAINER_NAME}" 2>/dev/null | grep -q "${WOOTP_PLUGIN_NAME}" && echo "yes" || echo "no")

if [ "$PEXPRESS_MOUNTED" = "yes" ] && [ "$WOOTP_MOUNTED" = "yes" ]; then
    echo "Both plugins are already mounted. Exiting."
    exit 0
fi

# Stop the container
echo "Stopping container ${CONTAINER_NAME}..."
docker stop "${CONTAINER_NAME}" 2>/dev/null || true

# Get container configuration
ORIGINAL_IMAGE=$(docker inspect "${CONTAINER_NAME}" --format='{{.Config.Image}}' 2>/dev/null || echo "wordpress:latest")
NETWORK_NAME=$(docker inspect "${CONTAINER_NAME}" --format='{{range $k, $v := .NetworkSettings.Networks}}{{$k}}{{end}}' 2>/dev/null | head -1 || echo "ssl-wireless-sms-notification_wordpress-network")
PORT_MAPPING=$(docker port "${CONTAINER_NAME}" 2>/dev/null | grep "80/tcp" | awk '{print $3}' || echo "8080:80")
PORT_HOST=$(echo $PORT_MAPPING | cut -d: -f1 || echo "8080")

# Create snapshot if needed
if ! docker images --format "{{.Repository}}:{{.Tag}}" | grep -q "${CONTAINER_NAME}-snapshot"; then
    echo "Creating snapshot..."
    docker commit "${CONTAINER_NAME}" "${CONTAINER_NAME}-snapshot" 2>/dev/null || true
fi

SNAPSHOT_IMAGE="${CONTAINER_NAME}-snapshot"
if ! docker images --format "{{.Repository}}:{{.Tag}}" | grep -q "${SNAPSHOT_IMAGE}"; then
    SNAPSHOT_IMAGE="${ORIGINAL_IMAGE}"
fi

# Remove the old container
echo "Removing old container..."
docker rm "${CONTAINER_NAME}" 2>/dev/null || true

# Get network name from list if not found
if [ -z "$NETWORK_NAME" ] || [ "$NETWORK_NAME" = "null" ]; then
    NETWORK_NAME=$(docker network ls --format "{{.Name}}" | grep -E "(ssl|wireless)" | head -1)
    if [ -z "$NETWORK_NAME" ]; then
        NETWORK_NAME="bridge"
    fi
fi

# Start new container with both bind mounts
echo ""
echo "Starting container with bind mounts..."
echo "  - Polar Express: ${PEXPRESS_PLUGIN_DIR}"
echo "    -> ${PEXPRESS_CONTAINER_PATH}"
echo "  - EtherTech WOOTP: ${WOOTP_PLUGIN_DIR}"
echo "    -> ${WOOTP_CONTAINER_PATH}"
echo ""

docker run -d \
    --name "${CONTAINER_NAME}" \
    --network "${NETWORK_NAME}" \
    --mount type=bind,source="${PEXPRESS_PLUGIN_DIR}",target="${PEXPRESS_CONTAINER_PATH}" \
    --mount type=bind,source="${WOOTP_PLUGIN_DIR}",target="${WOOTP_CONTAINER_PATH}" \
    -p "${PORT_HOST}:80" \
    "${SNAPSHOT_IMAGE}"

echo ""
echo "✓ Successfully bound both plugins to WordPress container!"
echo ""
echo "Plugin paths in container:"
echo "  - Polar Express: ${PEXPRESS_CONTAINER_PATH}"
echo "  - EtherTech WOOTP: ${WOOTP_CONTAINER_PATH}"
echo ""
echo "Changes to both plugins will be reflected immediately."
echo "Access WordPress at: http://localhost:${PORT_HOST}"

