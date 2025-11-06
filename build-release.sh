#!/bin/bash

# Script to build a release zip file of the plugin
# Usage: ./build-release.sh [version]
# Example: ./build-release.sh 1.0.0

set -e

# Get version from argument or use default
VERSION=${1:-"1.0.0"}
PLUGIN_NAME="polar-express"
SOURCE_DIR="pexpress"
RELEASE_DIR="release"
ZIP_NAME="${PLUGIN_NAME}-${VERSION}.zip"

# Create release directory if it doesn't exist
mkdir -p "${RELEASE_DIR}"

# Remove old zip if it exists
if [ -f "${RELEASE_DIR}/${ZIP_NAME}" ]; then
    rm "${RELEASE_DIR}/${ZIP_NAME}"
fi

# Create temporary directory for building
TEMP_DIR=$(mktemp -d)
BUILD_DIR="${TEMP_DIR}/${PLUGIN_NAME}"

# Copy plugin files to temp directory (renamed to plugin name)
echo "Copying plugin files..."
rsync -av --progress \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='*.log' \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    --exclude='*.zip' \
    --exclude='release' \
    --exclude='docker-compose.yml' \
    --exclude='Dockerfile' \
    --exclude='.dockerignore' \
    --exclude='bind-plugin.sh' \
    --exclude='build-release.sh' \
    --exclude='README.md' \
    --exclude='LICENSE' \
    "${SOURCE_DIR}/" "${BUILD_DIR}/"

# Update version in main plugin file
if [ -f "${BUILD_DIR}/${PLUGIN_NAME}.php" ]; then
    sed -i.bak "s/Version:.*/Version: ${VERSION}/" "${BUILD_DIR}/${PLUGIN_NAME}.php"
    rm "${BUILD_DIR}/${PLUGIN_NAME}.php.bak"
fi

# Create zip file
echo "Creating zip file..."
cd "${TEMP_DIR}"
zip -r "${ZIP_NAME}" "${PLUGIN_NAME}" > /dev/null

# Move zip to release directory
mv "${ZIP_NAME}" "$(pwd)/${RELEASE_DIR}/"

# Cleanup
cd - > /dev/null
rm -rf "${TEMP_DIR}"

echo "Release built successfully!"
echo "Location: ${RELEASE_DIR}/${ZIP_NAME}"

