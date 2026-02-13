#!/bin/bash
set -e

###############################################################################
# SAHO Default Site Deployment Script
#
# Usage: ./scripts/deploy-default.sh [staging|production]
###############################################################################

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ENVIRONMENT="${1:-staging}"
SITE_URI="default"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

cd "$(dirname "$0")/.." || exit 1

# Use absolute paths
PROJECT_ROOT=$(pwd)
BACKUP_DIR="${PROJECT_ROOT}/backups/deploy"
LOG_FILE="${PROJECT_ROOT}/logs/deploy-${ENVIRONMENT}-${TIMESTAMP}.log"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}SAHO Default Site Deployment${NC}"
echo -e "${GREEN}Environment: ${ENVIRONMENT}${NC}"
echo -e "${GREEN}========================================${NC}"

mkdir -p "${BACKUP_DIR}" "logs"

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "${LOG_FILE}"
}

error_exit() {
    echo -e "${RED}ERROR: $1${NC}" | tee -a "${LOG_FILE}"
    exit 1
}

log "Starting deployment for default site"

# Get current version from git tag
CURRENT_TAG=$(git describe --tags --abbrev=0 2>/dev/null || echo "no-tag")
CURRENT_COMMIT_SHORT=$(git rev-parse --short HEAD)
if [ "$CURRENT_TAG" = "no-tag" ]; then
    VERSION="commit-${CURRENT_COMMIT_SHORT}"
else
    VERSION="${CURRENT_TAG}"
fi
log "Current version: ${VERSION}"
echo -e "${GREEN}Deploying version: ${VERSION}${NC}"

# Git pull (only if on a branch, not a tag)
echo -e "${YELLOW}[1/4] Updating code...${NC}"
CURRENT_COMMIT=$(git rev-parse HEAD)

# Check if we're on a detached HEAD (tag deployment)
if git symbolic-ref -q HEAD >/dev/null 2>&1; then
    # We're on a branch - pull latest
    log "On branch, pulling latest changes"
    git pull >> "${LOG_FILE}" 2>&1 || error_exit "Git pull failed"
    NEW_COMMIT=$(git rev-parse HEAD)
    echo -e "${GREEN}✓ Code updated (pulled latest)${NC}"
else
    # We're on a detached HEAD (tag) - already at exact version
    log "On tag/commit, skipping pull (already at exact version)"
    NEW_COMMIT=$(git rev-parse HEAD)
    echo -e "${GREEN}✓ Code ready (using tag ${VERSION})${NC}"
fi

# Composer
echo -e "${YELLOW}[2/4] Installing dependencies...${NC}"
composer install --no-dev --optimize-autoloader >> "${LOG_FILE}" 2>&1 || error_exit "Composer failed"
echo -e "${GREEN}✓ Dependencies updated${NC}"

# Maintenance mode
echo -e "${YELLOW}[3/4] Enabling maintenance mode...${NC}"
vendor/bin/drush state:set system.maintenance_mode 1 -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1
echo -e "${GREEN}✓ Maintenance ON${NC}"

# Deploy
echo -e "${YELLOW}[4/4] Running drush deploy...${NC}"
echo ""
vendor/bin/drush deploy -y -v -l "${SITE_URI}" 2>&1 | tee -a "${LOG_FILE}" || error_exit "Deploy 1 failed"
echo ""
echo -e "${GREEN}✓ Deploy 1/2 complete${NC}"
echo ""
vendor/bin/drush deploy -y -v -l "${SITE_URI}" 2>&1 | tee -a "${LOG_FILE}" || error_exit "Deploy 2 failed"
echo ""
echo -e "${GREEN}✓ Deploy 2/2 complete${NC}"

# Disable maintenance mode (production only, staging stays in maintenance)
if [ "${ENVIRONMENT}" = "production" ]; then
    echo -e "${YELLOW}Disabling maintenance mode (production)...${NC}"
    vendor/bin/drush state:set system.maintenance_mode 0 -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1
    echo -e "${GREEN}✓ Site is LIVE${NC}"
else
    echo -e "${YELLOW}Keeping maintenance mode enabled (staging)...${NC}"
    echo -e "${YELLOW}⚠ Site remains in MAINTENANCE MODE${NC}"
fi

# Final cache clear
echo -e "${YELLOW}Final cache clear...${NC}"
if vendor/bin/drush cr -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1; then
    echo -e "${GREEN}✓ Cache cleared${NC}"
else
    log "Warning: Final cache clear failed (non-fatal, site is still operational)"
    echo -e "${YELLOW}⚠ Cache clear failed (non-fatal)${NC}"
fi

echo ""
echo -e "${GREEN}✓ DEPLOYMENT SUCCESSFUL${NC}"
echo -e "Version: ${VERSION}"
echo -e "Log: ${LOG_FILE}"
echo ""
echo -e "${YELLOW}To tag a new version:${NC} git tag v1.2.3 && git push origin v1.2.3"
echo ""
