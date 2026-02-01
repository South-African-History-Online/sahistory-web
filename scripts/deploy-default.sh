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

# Increment deploy version
if [ -f "${PROJECT_ROOT}/DEPLOY_VERSION" ]; then
    CURRENT_VERSION=$(cat "${PROJECT_ROOT}/DEPLOY_VERSION")
    NEW_VERSION=$((CURRENT_VERSION + 1))
else
    NEW_VERSION=1
fi
echo "$NEW_VERSION" > "${PROJECT_ROOT}/DEPLOY_VERSION"
log "Deploy version: ${NEW_VERSION}"
echo -e "${GREEN}Deploy #${NEW_VERSION}${NC}"

# Git pull
echo -e "${YELLOW}[1/4] Pulling code...${NC}"
CURRENT_COMMIT=$(git rev-parse HEAD)
git pull >> "${LOG_FILE}" 2>&1 || error_exit "Git pull failed"
NEW_COMMIT=$(git rev-parse HEAD)
echo -e "${GREEN}✓ Code updated${NC}"

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
vendor/bin/drush cr -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1

echo ""
echo -e "${GREEN}✓ DEPLOYMENT SUCCESSFUL${NC}"
echo -e "Deploy version: #${NEW_VERSION}"
echo -e "Log: ${LOG_FILE}"
echo ""
