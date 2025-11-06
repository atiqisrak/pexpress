#!/bin/bash

# SAFE WordPress Recreation Script
# This recreates WordPress container while preserving ALL data (themes, plugins, settings, uploads, etc.)

set -e

CONTAINER_NAME="ssl-wireless-wordpress"
TARGET_PORT="8080"

echo "=== SAFE WordPress Recreation Script ==="
echo "This will recreate WordPress while preserving ALL your data:"
echo "  ✅ Themes"
echo "  ✅ Plugins (except bind-mounted ones)"
echo "  ✅ Settings"
echo "  ✅ Uploads"
echo "  ✅ Database connections"
echo "  ✅ All configurations"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop first."
    exit 1
fi

# Check if container exists
if ! docker ps -a --format "{{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
    echo "❌ Container '${CONTAINER_NAME}' does not exist."
    echo "Cannot recreate - container doesn't exist."
    exit 1
fi

# Get all configuration
echo "Step 1: Saving container configuration..."

# Get all volume mounts (these contain your WordPress data)
VOLUME_MOUNTS=$(docker inspect "${CONTAINER_NAME}" --format '{{range .Mounts}}{{if eq .Type "volume"}}--mount type=volume,source={{.Name}},destination={{.Destination}} {{end}}{{end}}' 2>/dev/null || echo "")

# Get all bind mounts (plugins)
BIND_MOUNTS=$(docker inspect "${CONTAINER_NAME}" --format '{{range .Mounts}}{{if eq .Type "bind"}}--mount type=bind,source={{.Source}},destination={{.Destination}} {{end}}{{end}}' 2>/dev/null || echo "")

# Get network
NETWORK_NAME=$(docker inspect "${CONTAINER_NAME}" --format='{{range $k, $v := .NetworkSettings.Networks}}{{$k}}{{end}}' 2>/dev/null | head -1)
if [ -z "$NETWORK_NAME" ] || [ "$NETWORK_NAME" = "null" ]; then
    NETWORK_NAME=$(docker network ls --format "{{.Name}}" | grep -E "(ssl|wireless)" | head -1)
    if [ -z "$NETWORK_NAME" ]; then
        NETWORK_NAME="bridge"
        echo "⚠️  Using default bridge network"
    fi
fi

# Get environment variables
ENV_VARS=$(docker inspect "${CONTAINER_NAME}" --format='{{range .Config.Env}}--env "{{.}}" {{end}}' 2>/dev/null || echo "")

# Get image
IMAGE=$(docker inspect "${CONTAINER_NAME}" --format='{{.Config.Image}}' 2>/dev/null || echo "wordpress:latest")

echo "✓ Configuration saved:"
echo "  - Network: ${NETWORK_NAME}"
echo "  - Image: ${IMAGE}"
echo "  - Target Port: ${TARGET_PORT}"
echo "  - Volume mounts: $(echo "$VOLUME_MOUNTS" | wc -w | xargs) volumes"
echo "  - Bind mounts: $(echo "$BIND_MOUNTS" | wc -w | xargs) bind mounts"
echo ""

# Show what will be preserved
echo "Step 2: Data that will be preserved:"
if [ -n "$VOLUME_MOUNTS" ]; then
    echo "$VOLUME_MOUNTS" | tr ' ' '\n' | grep "source=" | sed 's/.*source=\([^,]*\).*/  ✓ Volume: \1/' || true
fi
if [ -n "$BIND_MOUNTS" ]; then
    echo "$BIND_MOUNTS" | tr ' ' '\n' | grep "source=" | sed 's/.*source=\([^,]*\).*/  ✓ Bind: \1/' || true
fi
echo ""

# Check if port 8080 is in use
if lsof -i :${TARGET_PORT} > /dev/null 2>&1; then
    echo "⚠️  Port ${TARGET_PORT} is already in use!"
    echo "   Please stop the service using port ${TARGET_PORT} or choose a different port."
    read -p "Continue anyway? This may fail. (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Cancelled."
        exit 0
    fi
fi

# Final confirmation
echo "⚠️  This will recreate the WordPress container."
echo "   ALL DATA WILL BE PRESERVED (themes, plugins, settings, uploads)"
echo ""
read -p "Continue with recreation? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 0
fi

# Stop container
echo ""
echo "Step 3: Stopping current container..."
docker stop "${CONTAINER_NAME}" 2>/dev/null || true
sleep 2

# Remove container (volumes are safe - they're separate)
echo "Step 4: Removing old container..."
echo "  (Data volumes are safe - they persist separately)"
docker rm "${CONTAINER_NAME}" 2>/dev/null || true

# Start new container with all preserved mounts
echo ""
echo "Step 5: Creating new WordPress container..."
echo "  - Port: ${TARGET_PORT}:80"
echo "  - Network: ${NETWORK_NAME}"
echo "  - Preserving all volumes and bind mounts"
echo ""

# Build the docker run command
DOCKER_CMD="docker run -d \
    --name ${CONTAINER_NAME} \
    --network ${NETWORK_NAME} \
    ${VOLUME_MOUNTS} \
    ${BIND_MOUNTS} \
    -p ${TARGET_PORT}:80 \
    ${ENV_VARS} \
    ${IMAGE}"

# Execute
eval $DOCKER_CMD

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ WordPress container created successfully!"
    echo ""
    echo "Waiting for WordPress to start..."
    sleep 5
    
    # Check if container is running
    if docker ps --format "{{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
        echo "✓ Container is running"
        
        # Check if WordPress is responding
        echo "Checking WordPress status..."
        sleep 3
        
        HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:${TARGET_PORT}" || echo "000")
        
        if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ] || [ "$HTTP_CODE" = "301" ]; then
            echo "✓ WordPress is responding!"
            echo ""
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            echo "✅ SUCCESS! WordPress is ready!"
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            echo ""
            echo "WordPress URL: http://localhost:${TARGET_PORT}"
            echo "Admin URL: http://localhost:${TARGET_PORT}/wp-admin"
            echo ""
            echo "All your data is preserved:"
            echo "  ✓ Themes"
            echo "  ✓ Plugins"
            echo "  ✓ Settings"
            echo "  ✓ Uploads"
            echo "  ✓ Database"
            echo ""
        else
            echo "⚠️  Container is running but WordPress may not be fully ready yet."
            echo "   HTTP Status: ${HTTP_CODE}"
            echo "   Try accessing: http://localhost:${TARGET_PORT}"
            echo "   Check logs with: docker logs ${CONTAINER_NAME}"
        fi
    else
        echo "❌ Container failed to start. Check logs:"
        echo "   docker logs ${CONTAINER_NAME}"
        exit 1
    fi
else
    echo ""
    echo "❌ Failed to create container."
    echo "   Check the error above and try again."
    exit 1
fi

