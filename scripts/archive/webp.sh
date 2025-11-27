#!/bin/bash

# WebP Optimization Script Runner
# Usage: ./webp.sh [command] [options]

set -e

# Get absolute path to project root (parent of scripts directory)  
SCRIPT_DIR="$(dirname "$(readlink -f "$0")")"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Print colored output
print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_success() {
    echo -e "${GREEN}✅${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}❌${NC} $1"
}

print_header() {
    echo -e "\n${BLUE}🚀 $1${NC}"
    echo "=================================="
}

# Change to project root directory
cd "$PROJECT_ROOT"

# Check if we're in the right directory
if [[ ! -f "composer.json" ]]; then
    print_error "Could not find project root with composer.json"
    print_error "Script dir: $SCRIPT_DIR"
    print_error "Project root: $PROJECT_ROOT"
    exit 1
fi

# Show usage
show_usage() {
    cat << EOF
🚀 WebP Optimization Script Runner

Usage: scripts/webp.sh [command] [options]

Commands:
  status          - Check WebP conversion status
  clean           - Remove fake HTML error pages
  convert         - Convert all images to WebP
  audit           - Run production audit
  debug [file]    - Debug specific file conversion
  fix-names       - Fix double extension files
  help            - Show this help

Examples:
  scripts/webp.sh status
  scripts/webp.sh clean
  scripts/webp.sh convert
  scripts/webp.sh debug sites/default/files/bio_pics/image.jpg
  scripts/webp.sh audit

All scripts run from project root and detect paths automatically.
EOF
}

# Main script logic
case "${1:-help}" in
    "status")
        print_header "WebP Conversion Status"
        php "$SCRIPT_DIR/comprehensive_webp_status.php"
        ;;
    
    "clean")
        print_header "Cleaning Fake Images"
        php "$SCRIPT_DIR/clean_fake_images.php"
        ;;
    
    "convert")
        print_header "Converting Images to WebP"
        php "$SCRIPT_DIR/complete_webp_conversion.php"
        ;;
    
    "audit")
        print_header "Production WebP Audit"
        php "$SCRIPT_DIR/production_webp_audit.php"
        ;;
    
    "debug")
        if [[ -z "$2" ]]; then
            print_error "Debug command requires a file path"
            echo "Usage: scripts/webp.sh debug sites/default/files/path/to/image.jpg"
            exit 1
        fi
        print_header "Debugging File: $2"
        php "$SCRIPT_DIR/debug_specific_file.php" "$2"
        ;;
    
    "fix-names")
        print_header "Fixing Double Extension Files"
        php "$SCRIPT_DIR/fix_webp_names.php"
        ;;
    
    "safe")
        print_header "Safe WebP Generation"
        php "$SCRIPT_DIR/safe_webp_generator.php" "${2:-500}" "${3:-0}"
        ;;
    
    "help"|*)
        show_usage
        ;;
esac

if [[ "$1" != "help" && -n "$1" ]]; then
    print_success "Command completed successfully"
    print_info "Run 'scripts/webp.sh status' to check current conversion rate"
fi
