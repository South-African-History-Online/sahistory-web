#!/bin/bash
set -e

###############################################################################
# SAHO Full Deployment (Default + Shop)
#
# Usage: ./scripts/deploy-all.sh [staging|production]
###############################################################################

BLUE='\033[0;34m'
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

ENVIRONMENT="${1:-staging}"
SCRIPT_DIR="$(dirname "$0")"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}SAHO Full Deployment${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Deploy default
echo -e "${BLUE}[1/2] Deploying DEFAULT site...${NC}"
if "${SCRIPT_DIR}/deploy-default.sh" "${ENVIRONMENT}"; then
    echo -e "${GREEN}✓ Default deployed${NC}"
else
    echo -e "${RED}✗ Default failed${NC}"
    exit 1
fi

sleep 2

# Deploy shop
echo -e "${BLUE}[2/2] Deploying SHOP site...${NC}"
if "${SCRIPT_DIR}/deploy-shop.sh" "${ENVIRONMENT}"; then
    echo -e "${GREEN}✓ Shop deployed${NC}"
else
    echo -e "${RED}✗ Shop failed${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}✓ FULL DEPLOYMENT SUCCESSFUL${NC}"
echo ""
