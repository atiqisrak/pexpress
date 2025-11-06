#!/bin/bash

# Script to clean up old ssl-wireless containers
# This will stop and remove containers but preserve data volumes

set -e

echo "=== Cleanup Old SSL-Wireless Containers ==="
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop first."
    exit 1
fi

# Find all ssl-wireless containers
CONTAINERS=$(docker ps -a --format "{{.Names}}" | grep -E "ssl-wireless|ssl-wireless" || echo "")

if [ -z "$CONTAINERS" ]; then
    echo "✓ No ssl-wireless containers found to clean up."
    exit 0
fi

echo "Found containers to remove:"
echo "$CONTAINERS" | sed 's/^/  - /'
echo ""

# Find all volumes
VOLUMES=$(docker volume ls --format "{{.Name}}" | grep ssl-wireless || echo "")

if [ -n "$VOLUMES" ]; then
    echo "Found volumes (these will be preserved by default):"
    echo "$VOLUMES" | sed 's/^/  - /'
    echo ""
    echo "⚠️  Volumes contain your WordPress data (themes, plugins, uploads, database)."
    echo "   They will be preserved unless you explicitly choose to remove them."
    echo ""
fi

# Ask for confirmation
read -p "Stop and remove these containers? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 0
fi

# Stop containers
echo ""
echo "Stopping containers..."
echo "$CONTAINERS" | while read -r container; do
    if [ -n "$container" ]; then
        echo "  Stopping ${container}..."
        docker stop "${container}" 2>/dev/null || true
    fi
done

# Remove containers
echo ""
echo "Removing containers..."
echo "$CONTAINERS" | while read -r container; do
    if [ -n "$container" ]; then
        echo "  Removing ${container}..."
        docker rm "${container}" 2>/dev/null || true
    fi
done

echo ""
echo "✓ Containers removed successfully!"

# Ask about volumes
if [ -n "$VOLUMES" ]; then
    echo ""
    echo "⚠️  Volumes still exist:"
    echo "$VOLUMES" | sed 's/^/  - /'
    echo ""
    read -p "Remove volumes as well? This will DELETE all WordPress data! (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo ""
        echo "Removing volumes..."
        echo "$VOLUMES" | while read -r volume; do
            if [ -n "$volume" ]; then
                echo "  Removing volume ${volume}..."
                docker volume rm "${volume}" 2>/dev/null || true
            fi
        done
        echo ""
        echo "✓ Volumes removed."
    else
        echo ""
        echo "✓ Volumes preserved (you can remove them later if needed)."
    fi
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Cleanup complete!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Next steps:"
echo "  1. Run ./setup-fresh-wordpress.sh to create a fresh WordPress environment"
echo "  2. Or manually create your WordPress container"

