#!/bin/bash

# Script to set up a fresh WordPress environment with both plugins bound

set -e

CONTAINER_NAME="wordpress-polar-express"
DB_CONTAINER_NAME="wordpress-db"
DB_NAME="wordpress"
DB_USER="wordpress"
DB_PASSWORD="wordpress"
DB_ROOT_PASSWORD="rootpassword"
WP_PORT="8080"
NETWORK_NAME="wordpress-polar-network"

PEXPRESS_PLUGIN_DIR="$(pwd)/pexpress"
WOOTP_PLUGIN_DIR="$(cd "$(dirname "$0")/../ethertech-wootp" && pwd)"

echo "=== Fresh WordPress Setup ==="
echo ""
echo "This will create a fresh WordPress environment with:"
echo "  - WordPress container"
echo "  - MySQL database container"
echo "  - Both plugins bound for development"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop first."
    exit 1
fi

# Check if containers already exist
if docker ps -a --format "{{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
    echo "⚠️  Container '${CONTAINER_NAME}' already exists."
    read -p "Remove and recreate? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker stop "${CONTAINER_NAME}" 2>/dev/null || true
        docker rm "${CONTAINER_NAME}" 2>/dev/null || true
    else
        echo "Cancelled."
        exit 0
    fi
fi

# Check if plugin directories exist
if [ ! -d "${PEXPRESS_PLUGIN_DIR}" ]; then
    echo "❌ Polar Express plugin directory not found: ${PEXPRESS_PLUGIN_DIR}"
    exit 1
fi

if [ ! -d "${WOOTP_PLUGIN_DIR}" ]; then
    echo "❌ EtherTech WOOTP plugin directory not found: ${WOOTP_PLUGIN_DIR}"
    exit 1
fi

echo "✓ Plugin directories found:"
echo "  - Polar Express: ${PEXPRESS_PLUGIN_DIR}"
echo "  - EtherTech WOOTP: ${WOOTP_PLUGIN_DIR}"
echo ""

# Check if port is available
if lsof -i :${WP_PORT} > /dev/null 2>&1; then
    echo "⚠️  Port ${WP_PORT} is already in use!"
    read -p "Use a different port? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        read -p "Enter port number (default 8081): " WP_PORT
        WP_PORT=${WP_PORT:-8081}
    else
        echo "Cancelled."
        exit 0
    fi
fi

# Create network if it doesn't exist
echo "Creating Docker network..."
docker network create "${NETWORK_NAME}" 2>/dev/null || echo "  Network already exists"

# Create database container
echo ""
echo "Step 1: Creating MySQL database container..."
if docker ps -a --format "{{.Names}}" | grep -q "^${DB_CONTAINER_NAME}$"; then
    echo "  Database container already exists. Starting it..."
    docker start "${DB_CONTAINER_NAME}" 2>/dev/null || true
else
    docker run -d \
        --name "${DB_CONTAINER_NAME}" \
        --network "${NETWORK_NAME}" \
        -e MYSQL_DATABASE="${DB_NAME}" \
        -e MYSQL_USER="${DB_USER}" \
        -e MYSQL_PASSWORD="${DB_PASSWORD}" \
        -e MYSQL_ROOT_PASSWORD="${DB_ROOT_PASSWORD}" \
        mysql:8.0
    
    echo "  ✓ Database container created"
    echo "  Waiting for database to be ready..."
    sleep 10
fi

# Create WordPress container with plugins bound
echo ""
echo "Step 2: Creating WordPress container with plugins..."
echo "  - Port: ${WP_PORT}:80"
echo "  - Database: ${DB_CONTAINER_NAME}"
echo "  - Polar Express: ${PEXPRESS_PLUGIN_DIR}"
echo "  - EtherTech WOOTP: ${WOOTP_PLUGIN_DIR}"
echo ""

docker run -d \
    --name "${CONTAINER_NAME}" \
    --network "${NETWORK_NAME}" \
    --mount type=bind,source="${PEXPRESS_PLUGIN_DIR}",target="/var/www/html/wp-content/plugins/polar-express" \
    --mount type=bind,source="${WOOTP_PLUGIN_DIR}",target="/var/www/html/wp-content/plugins/ethertech-wootp" \
    -p "${WP_PORT}:80" \
    -e WORDPRESS_DB_HOST="${DB_CONTAINER_NAME}" \
    -e WORDPRESS_DB_USER="${DB_USER}" \
    -e WORDPRESS_DB_PASSWORD="${DB_PASSWORD}" \
    -e WORDPRESS_DB_NAME="${DB_NAME}" \
    -e WORDPRESS_DEBUG=1 \
    wordpress:latest

if [ $? -eq 0 ]; then
    echo "  ✓ WordPress container created"
    echo ""
    echo "Waiting for WordPress to initialize..."
    sleep 10
    
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "✅ WordPress Setup Complete!"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "WordPress URL: http://localhost:${WP_PORT}"
    echo "Admin URL: http://localhost:${WP_PORT}/wp-admin"
    echo ""
    echo "Container names:"
    echo "  - WordPress: ${CONTAINER_NAME}"
    echo "  - Database: ${DB_CONTAINER_NAME}"
    echo ""
    echo "Plugins bound for development:"
    echo "  - Polar Express: /var/www/html/wp-content/plugins/polar-express"
    echo "  - EtherTech WOOTP: /var/www/html/wp-content/plugins/ethertech-wootp"
    echo ""
    echo "Next steps:"
    echo "  1. Complete WordPress installation at http://localhost:${WP_PORT}"
    echo "  2. Activate both plugins in WordPress admin"
    echo "  3. Start developing - changes will be reflected immediately!"
    echo ""
else
    echo ""
    echo "❌ Failed to create WordPress container."
    exit 1
fi

