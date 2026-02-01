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
BACKUP_DIR="backups/deploy"
LOG_FILE="logs/deploy-${ENVIRONMENT}-${TIMESTAMP}.log"

cd "$(dirname "$0")/.." || exit 1

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
    echo -e "${YELLOW}Rollback: vendor/bin/drush sql:cli < ${BACKUP_DIR}/db-default-${TIMESTAMP}.sql${NC}"
    exit 1
}

log "Starting deployment for default site"

# Database backup
echo -e "${YELLOW}[1/5] Creating database backup...${NC}"
BACKUP_FILE="${BACKUP_DIR}/db-default-${TIMESTAMP}.sql"
vendor/bin/drush sql:dump --result-file="${BACKUP_FILE}" -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1 || error_exit "Backup failed"
BACKUP_SIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
echo -e "${GREEN}✓ Backed up (${BACKUP_SIZE})${NC}"

# Git pull
echo -e "${YELLOW}[2/5] Pulling code...${NC}"
CURRENT_COMMIT=$(git rev-parse HEAD)
git pull >> "${LOG_FILE}" 2>&1 || error_exit "Git pull failed"
NEW_COMMIT=$(git rev-parse HEAD)
echo -e "${GREEN}✓ Code updated${NC}"

# Composer
echo -e "${YELLOW}[3/5] Installing dependencies...${NC}"
composer install --no-dev --optimize-autoloader >> "${LOG_FILE}" 2>&1 || error_exit "Composer failed"
echo -e "${GREEN}✓ Dependencies updated${NC}"

# Maintenance mode
echo -e "${YELLOW}[4/5] Enabling maintenance mode...${NC}"
vendor/bin/drush state:set system.maintenance_mode 1 -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1
echo -e "${GREEN}✓ Maintenance ON${NC}"

# Deploy
echo -e "${YELLOW}[5/5] Running drush deploy...${NC}"
vendor/bin/drush deploy -y -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1 || error_exit "Deploy 1 failed"
echo -e "${GREEN}✓ Deploy 1/2${NC}"
vendor/bin/drush deploy -y -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1 || error_exit "Deploy 2 failed"
echo -e "${GREEN}✓ Deploy 2/2${NC}"

# Cleanup
vendor/bin/drush state:set system.maintenance_mode 0 -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1
vendor/bin/drush cr -l "${SITE_URI}" >> "${LOG_FILE}" 2>&1
ls -t "${BACKUP_DIR}"/db-default-*.sql | tail -n +3 | xargs -r rm

echo ""
echo -e "${GREEN}✓ DEPLOYMENT SUCCESSFUL${NC}"
echo -e "Backup: ${BACKUP_FILE} (${BACKUP_SIZE})"
echo -e "Log: ${LOG_FILE}"
echo ""
