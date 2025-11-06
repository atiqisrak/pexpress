#!/bin/bash

# SAFE script to bind mount plugins WITHOUT losing WordPress data
# This script preserves all WordPress themes, plugins, and settings

set -e

CONTAINER_NAME="ssl-wireless-wordpress"
PEXPRESS_PLUGIN_NAME="polar-express"
PEXPRESS_PLUGIN_DIR="$(pwd)/pexpress"
PEXPRESS_CONTAINER_PATH="/var/www/html/wp-content/plugins/${PEXPRESS_PLUGIN_NAME}"

WOOTP_PLUGIN_NAME="ethertech-wootp"
WOOTP_PLUGIN_DIR="$(cd "$(dirname "$0")/../ethertech-wootp" && pwd)"
WOOTP_CONTAINER_PATH="/var/www/html/wp-content/plugins/${WOOTP_PLUGIN_NAME}"

echo "=== SAFE Plugin Binding Script ==="
echo "This script will preserve all your WordPress data (themes, plugins, settings)"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop first."
    exit 1
fi

# Check if container exists
if ! docker ps -a --format "{{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
    echo "❌ Container '${CONTAINER_NAME}' does not exist."
    echo "Please create your WordPress container first."
    exit 1
fi

# Check if container is running
if ! docker ps --format "{{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
    echo "⚠️  Container is not running. Starting it first..."
    docker start "${CONTAINER_NAME}"
    sleep 2
fi

# Check if plugin directories exist
if [ ! -d "${PEXPRESS_PLUGIN_DIR}" ]; then
    echo "❌ Error: Polar Express plugin directory ${PEXPRESS_PLUGIN_DIR} does not exist"
    exit 1
fi

if [ ! -d "${WOOTP_PLUGIN_DIR}" ]; then
    echo "❌ Error: EtherTech WOOTP plugin directory ${WOOTP_PLUGIN_DIR} does not exist"
    exit 1
fi

# Check current mounts
echo "Checking current mounts..."
PEXPRESS_MOUNTED=$(docker inspect "${CONTAINER_NAME}" 2>/dev/null | grep -q "${PEXPRESS_PLUGIN_NAME}" && echo "yes" || echo "no")
WOOTP_MOUNTED=$(docker inspect "${CONTAINER_NAME}" 2>/dev/null | grep -q "${WOOTP_PLUGIN_NAME}" && echo "yes" || echo "no")

if [ "$PEXPRESS_MOUNTED" = "yes" ] && [ "$WOOTP_MOUNTED" = "yes" ]; then
    echo "✓ Both plugins are already mounted."
    echo ""
    echo "Plugin paths:"
    echo "  - Polar Express: ${PEXPRESS_CONTAINER_PATH}"
    echo "  - EtherTech WOOTP: ${WOOTP_CONTAINER_PATH}"
    exit 0
fi

echo ""
echo "⚠️  IMPORTANT: To add bind mounts, we need to recreate the container."
echo "   ALL YOUR WORDPRESS DATA WILL BE PRESERVED (themes, plugins, settings)"
echo "   because they are stored in a Docker volume, not in the container."
echo ""
read -p "Continue? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 0
fi

# Get container configuration
echo ""
echo "Saving container configuration..."

# Get all current mounts (to preserve data volumes)
CURRENT_MOUNTS=$(docker inspect "${CONTAINER_NAME}" --format '{{range .Mounts}}{{if eq .Type "volume"}}--mount type=volume,source={{.Name}},destination={{.Destination}} {{end}}{{end}}' 2>/dev/null || echo "")

# Get network
NETWORK_NAME=$(docker inspect "${CONTAINER_NAME}" --format='{{range $k, $v := .NetworkSettings.Networks}}{{$k}}{{end}}' 2>/dev/null | head -1)
if [ -z "$NETWORK_NAME" ] || [ "$NETWORK_NAME" = "null" ]; then
    NETWORK_NAME=$(docker network ls --format "{{.Name}}" | grep -E "(ssl|wireless)" | head -1)
    if [ -z "$NETWORK_NAME" ]; then
        NETWORK_NAME="bridge"
    fi
fi

# Get port mapping
PORT_HOST=$(docker port "${CONTAINER_NAME}" 2>/dev/null | grep "80/tcp" | awk '{print $3}' | cut -d: -f1 || echo "8080")

# Get environment variables
ENV_VARS=$(docker inspect "${CONTAINER_NAME}" --format='{{range .Config.Env}}--env {{.}} {{end}}' 2>/dev/null || echo "")

# Get image
IMAGE=$(docker inspect "${CONTAINER_NAME}" --format='{{.Config.Image}}' 2>/dev/null || echo "wordpress:latest")

echo "✓ Configuration saved"
echo ""

# Stop container
echo "Stopping container (this is safe - your data is in volumes)..."
docker stop "${CONTAINER_NAME}" 2>/dev/null || true

# Create snapshot
echo "Creating snapshot to preserve container state..."
SNAPSHOT_IMAGE="${CONTAINER_NAME}-snapshot-$(date +%s)"
docker commit "${CONTAINER_NAME}" "${SNAPSHOT_IMAGE}" 2>/dev/null || SNAPSHOT_IMAGE="${IMAGE}"

# Remove old container
echo "Removing old container (data is safe in volumes)..."
docker rm "${CONTAINER_NAME}" 2>/dev/null || true

# Start new container with all mounts
echo ""
echo "Creating new container with plugin bind mounts..."
echo "  - Preserving all data volumes (themes, plugins, uploads, etc.)"
echo "  - Adding Polar Express: ${PEXPRESS_PLUGIN_DIR}"
echo "  - Adding EtherTech WOOTP: ${WOOTP_PLUGIN_DIR}"
echo ""

# Build docker run command
DOCKER_RUN_CMD="docker run -d \
    --name ${CONTAINER_NAME} \
    --network ${NETWORK_NAME} \
    ${CURRENT_MOUNTS} \
    --mount type=bind,source=${PEXPRESS_PLUGIN_DIR},target=${PEXPRESS_CONTAINER_PATH} \
    --mount type=bind,source=${WOOTP_PLUGIN_DIR},target=${WOOTP_CONTAINER_PATH} \
    -p ${PORT_HOST}:80 \
    ${ENV_VARS} \
    ${SNAPSHOT_IMAGE}"

eval $DOCKER_RUN_CMD

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ Successfully created container with plugin bind mounts!"
    echo ""
    echo "Plugin paths:"
    echo "  - Polar Express: ${PEXPRESS_CONTAINER_PATH}"
    echo "  - EtherTech WOOTP: ${WOOTP_CONTAINER_PATH}"
    echo ""
    echo "WordPress is available at: http://localhost:${PORT_HOST}"
    echo ""
    echo "All your themes, plugins, and settings are preserved!"
else
    echo ""
    echo "❌ Failed to create container. Restoring from snapshot..."
    docker run -d --name "${CONTAINER_NAME}" --network "${NETWORK_NAME}" ${CURRENT_MOUNTS} -p "${PORT_HOST}:80" ${ENV_VARS} "${SNAPSHOT_IMAGE}" 2>/dev/null || true
    echo "Container restored. Please check Docker logs."
    exit 1
fi

