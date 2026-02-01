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
vendor/bin/drush deploy -y -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1 || error_exit "Deploy 1 failed"
echo -e "${GREEN}✓ Deploy 1/2${NC}"
vendor/bin/drush deploy -y -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1 || error_exit "Deploy 2 failed"
echo -e "${GREEN}✓ Deploy 2/2${NC}"

# Cleanup
vendor/bin/drush state:set system.maintenance_mode 0 -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1
vendor/bin/drush cr -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1

echo ""
echo -e "${GREEN}✓ DEPLOYMENT SUCCESSFUL${NC}"
echo -e "Log: ${LOG_FILE}"
echo ""
