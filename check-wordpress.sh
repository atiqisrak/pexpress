#!/bin/bash

# Script to check WordPress container status and start if needed

CONTAINER_NAME="ssl-wireless-wordpress"

echo "=== WordPress Container Status Check ==="
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop first."
    exit 1
fi

# Check if container exists
if ! docker ps -a --format "{{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
    echo "❌ Container '${CONTAINER_NAME}' does not exist."
    echo ""
    echo "Please check your WordPress setup. The container may need to be created."
    exit 1
fi

# Check if container is running
if docker ps --format "{{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
    echo "✓ WordPress container is running"
    
    # Show container info
    echo ""
    echo "Container Details:"
    docker ps --filter "name=${CONTAINER_NAME}" --format "  Name: {{.Names}}\n  Status: {{.Status}}\n  Ports: {{.Ports}}"
    
    # Extract and show WordPress URL
    PORT=$(docker port "${CONTAINER_NAME}" 2>/dev/null | grep "80/tcp" | awk '{print $3}' | cut -d: -f1 || echo "")
    if [ -n "$PORT" ]; then
        echo ""
        echo "WordPress URL:"
        echo "  http://localhost:${PORT}"
        if [ "$PORT" != "8080" ]; then
            echo ""
            echo "⚠️  Note: WordPress is on port ${PORT}, not 8080."
            echo "   To fix this, run: ./fix-wordpress-port.sh"
        fi
    fi
    
    # Check mounts
    echo ""
    echo "Current Mounts:"
    docker inspect "${CONTAINER_NAME}" --format '{{range .Mounts}}{{println "  -" .Source "->" .Destination}}{{end}}' 2>/dev/null || echo "  (Unable to read mounts)"
    
    exit 0
else
    echo "⚠️  WordPress container exists but is not running"
    echo ""
    echo "Container Status:"
    docker ps -a --filter "name=${CONTAINER_NAME}" --format "  Name: {{.Names}}\n  Status: {{.Status}}"
    echo ""
    echo "Starting container..."
    docker start "${CONTAINER_NAME}"
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "✓ WordPress container started successfully!"
        echo ""
        echo "Waiting for WordPress to be ready..."
        sleep 3
        echo ""
        echo "WordPress should be available at: http://localhost:8080"
    else
        echo ""
        echo "❌ Failed to start container. Check logs with:"
        echo "   docker logs ${CONTAINER_NAME}"
        exit 1
    fi
fi

