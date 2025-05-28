#!/bin/bash
set -e

# Script for building frontend assets in CI environment
echo "Starting frontend build process..."

# Navigate to the theme directory if not already there
# Uncomment if needed in your CI setup
# cd "$(dirname "$0")"

# Install all dependencies (including dev dependencies)
echo "Installing dependencies..."
npm ci

# Run linting with auto-fix
echo "Running linting with auto-fix..."
npm run biome:check
npm run stylint-fix

# Build assets for production
echo "Building assets for production..."
npm run production

echo "Frontend build process completed successfully!"
