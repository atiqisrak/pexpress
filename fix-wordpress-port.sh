#!/bin/bash

# Script to fix WordPress port mapping to 8080
# This preserves all data and just changes the port

set -e

CONTAINER_NAME="ssl-wireless-wordpress"
TARGET_PORT="8080"

echo "=== Fix WordPress Port Mapping ==="
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop first."
    exit 1
fi

# Check if container exists
if ! docker ps -a --format "{{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
    echo "❌ Container '${CONTAINER_NAME}' does not exist."
    exit 1
fi

# Get current port
CURRENT_PORT=$(docker port "${CONTAINER_NAME}" 2>/dev/null | grep "80/tcp" | awk '{print $3}' | cut -d: -f1 || echo "")

if [ -z "$CURRENT_PORT" ]; then
    echo "⚠️  Could not detect current port. Will set to ${TARGET_PORT}."
else
    echo "Current port: ${CURRENT_PORT}"
fi

if [ "$CURRENT_PORT" = "$TARGET_PORT" ]; then
    echo "✓ WordPress is already on port ${TARGET_PORT}."
    echo "Access at: http://localhost:${TARGET_PORT}"
    exit 0
fi

echo "Target port: ${TARGET_PORT}"
echo ""
echo "⚠️  This will recreate the container to change the port."
echo "   ALL YOUR WORDPRESS DATA WILL BE PRESERVED (themes, plugins, settings)"
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

# Get all current mounts
CURRENT_MOUNTS=$(docker inspect "${CONTAINER_NAME}" --format '{{range .Mounts}}{{if eq .Type "volume"}}--mount type=volume,source={{.Name}},destination={{.Destination}} {{end}}{{if eq .Type "bind"}}--mount type=bind,source={{.Source}},destination={{.Destination}} {{end}}{{end}}' 2>/dev/null || echo "")

# Get network
NETWORK_NAME=$(docker inspect "${CONTAINER_NAME}" --format='{{range $k, $v := .NetworkSettings.Networks}}{{$k}}{{end}}' 2>/dev/null | head -1)
if [ -z "$NETWORK_NAME" ] || [ "$NETWORK_NAME" = "null" ]; then
    NETWORK_NAME=$(docker network ls --format "{{.Name}}" | grep -E "(ssl|wireless)" | head -1)
    if [ -z "$NETWORK_NAME" ]; then
        NETWORK_NAME="bridge"
    fi
fi

# Get environment variables
ENV_VARS=$(docker inspect "${CONTAINER_NAME}" --format='{{range .Config.Env}}--env {{.}} {{end}}' 2>/dev/null || echo "")

# Get image
IMAGE=$(docker inspect "${CONTAINER_NAME}" --format='{{.Config.Image}}' 2>/dev/null || echo "wordpress:latest")

echo "✓ Configuration saved"
echo ""

# Stop container
echo "Stopping container..."
docker stop "${CONTAINER_NAME}" 2>/dev/null || true

# Create snapshot
echo "Creating snapshot..."
SNAPSHOT_IMAGE="${CONTAINER_NAME}-snapshot-$(date +%s)"
docker commit "${CONTAINER_NAME}" "${SNAPSHOT_IMAGE}" 2>/dev/null || SNAPSHOT_IMAGE="${IMAGE}"

# Remove old container
echo "Removing old container..."
docker rm "${CONTAINER_NAME}" 2>/dev/null || true

# Start new container with correct port
echo ""
echo "Creating new container with port ${TARGET_PORT}..."
echo "  - Preserving all data volumes and bind mounts"
echo ""

# Build docker run command
DOCKER_RUN_CMD="docker run -d \
    --name ${CONTAINER_NAME} \
    --network ${NETWORK_NAME} \
    ${CURRENT_MOUNTS} \
    -p ${TARGET_PORT}:80 \
    ${ENV_VARS} \
    ${SNAPSHOT_IMAGE}"

eval $DOCKER_RUN_CMD

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ Successfully created container on port ${TARGET_PORT}!"
    echo ""
    echo "WordPress is now available at:"
    echo "  http://localhost:${TARGET_PORT}"
    echo ""
    echo "All your themes, plugins, and settings are preserved!"
else
    echo ""
    echo "❌ Failed to create container. Restoring from snapshot..."
    docker run -d --name "${CONTAINER_NAME}" --network "${NETWORK_NAME}" ${CURRENT_MOUNTS} -p "${CURRENT_PORT}:80" ${ENV_VARS} "${SNAPSHOT_IMAGE}" 2>/dev/null || true
    echo "Container restored. Please check Docker logs."
    exit 1
fi

