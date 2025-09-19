#!/bin/bash

# Activity Tracking System - Restore Script
# This script restores the application from a backup

set -e  # Exit on any error

# Configuration
APP_NAME="activity-tracking-system"
APP_DIR="/var/www/${APP_NAME}"
BACKUP_DIR="/var/backups/${APP_NAME}"
LOG_FILE="/var/log/${APP_NAME}/restore.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}" | tee -a "$LOG_FILE"
    exit 1
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}" | tee -a "$LOG_FILE"
}

# Show usage
show_usage() {
    echo "Usage: $0 [OPTIONS] BACKUP_PATH"
    echo ""
    echo "Options:"
    echo "  -d, --database-only    Restore database only"
    echo "  -f, --files-only       Restore files only"
    echo "  -c, --configs-only     Restore configurations only"
    echo "  -k, --encryption-key   Encryption key for encrypted backups"
    echo "  -y, --yes             Skip confirmation prompts"
    echo "  -h, --help            Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 /var/backups/activity-tracking-system/20231201_120000"
    echo "  $0 --database-only /var/backups/activity-tracking-system/20231201_120000"
    echo "  $0 --encryption-key 'mykey' backup.tar.gz.enc"
}

# Parse command line arguments
parse_args() {
    DATABASE_ONLY=false
    FILES_ONLY=false
    CONFIGS_ONLY=false
    ENCRYPTION_KEY=""
    SKIP_CONFIRMATION=false
    BACKUP_PATH=""
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            -d|--database-only)
                DATABASE_ONLY=true
                shift
                ;;
            -f|--files-only)
                FILES_ONLY=true
                shift
                ;;
            -c|--configs-only)
                CONFIGS_ONLY=true
                shift
                ;;
            -k|--encryption-key)
                ENCRYPTION_KEY="$2"
                shift 2
                ;;
            -y|--yes)
                SKIP_CONFIRMATION=true
                shift
                ;;
            -h|--help)
                show_usage
                exit 0
                ;;
            -*)
                error "Unknown option: $1"
                ;;
            *)
                BACKUP_PATH="$1"
                shift
                ;;
        esac
    done
    
    if [ -z "$BACKUP_PATH" ]; then
        error "Backup path is required"
    fi
}

# Validate backup
validate_backup() {
    log "Validating backup..."
    
    if [ ! -e "$BACKUP_PATH" ]; then
        error "Backup path does not exist: $BACKUP_PATH"
    fi
    
    # Check if it's an encrypted backup
    if [[ "$BACKUP_PATH" == *.enc ]]; then
        if [ -z "$ENCRYPTION_KEY" ]; then
            error "Encryption key is required for encrypted backup"
        fi
        
        # Decrypt backup
        local decrypted_path="${BACKUP_PATH%.enc}"
        openssl enc -aes-256-cbc -d -salt -k "$ENCRYPTION_KEY" -in "$BACKUP_PATH" | tar -xzf - -C "$BACKUP_DIR"
        BACKUP_PATH="$decrypted_path"
    fi
    
    # Check if backup directory exists
    if [ -d "$BACKUP_PATH" ]; then
        RESTORE_DIR="$BACKUP_PATH"
    elif [ -f "$BACKUP_PATH" ] && [[ "$BACKUP_PATH" == *.tar.gz ]]; then
        # Extract backup archive
        local extract_dir="${BACKUP_DIR}/restore_$(date +%Y%m%d_%H%M%S)"
        mkdir -p "$extract_dir"
        tar -xzf "$BACKUP_PATH" -C "$extract_dir"
        RESTORE_DIR="$extract_dir"
    else
        error "Invalid backup format"
    fi
    
    # Check manifest
    if [ -f "${RESTORE_DIR}/manifest.json" ]; then
        log "Backup manifest found"
        cat "${RESTORE_DIR}/manifest.json"
    else
        warn "No backup manifest found"
    fi
    
    log "Backup validation completed"
}

# Confirm restore operation
confirm_restore() {
    if [ "$SKIP_CONFIRMATION" = true ]; then
        return
    fi
    
    echo ""
    warn "This operation will restore the application from backup."
    warn "Current data will be overwritten and cannot be recovered."
    echo ""
    
    read -p "Are you sure you want to continue? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        log "Restore operation cancelled by user"
        exit 0
    fi
}

# Create pre-restore backup
create_pre_restore_backup() {
    log "Creating pre-restore backup..."
    
    local pre_restore_dir="${BACKUP_DIR}/pre_restore_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$pre_restore_dir"
    
    # Backup current application
    if [ -d "$APP_DIR" ]; then
        tar -czf "${pre_restore_dir}/current_application.tar.gz" -C "$(dirname "$APP_DIR")" "$(basename "$APP_DIR")"
    fi
    
    # Backup current database
    if [ -f "${APP_DIR}/.env" ]; then
        source "${APP_DIR}/.env"
        if [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ]; then
            mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "${pre_restore_dir}/current_database.sql"
        fi
    fi
    
    log "Pre-restore backup created: $pre_restore_dir"
}

# Restore database
restore_database() {
    if [ "$FILES_ONLY" = true ] || [ "$CONFIGS_ONLY" = true ]; then
        return
    fi
    
    log "Restoring database..."
    
    local db_backup="${RESTORE_DIR}/database.sql.gz"
    if [ ! -f "$db_backup" ]; then
        db_backup="${RESTORE_DIR}/database.sql"
    fi
    
    if [ ! -f "$db_backup" ]; then
        warn "Database backup not found, skipping database restore"
        return
    fi
    
    # Load current environment for database credentials
    if [ -f "${APP_DIR}/.env" ]; then
        source "${APP_DIR}/.env"
    else
        error "Environment file not found: ${APP_DIR}/.env"
    fi
    
    # Drop and recreate database
    mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "DROP DATABASE IF EXISTS $DB_DATABASE;"
    mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE $DB_DATABASE;"
    
    # Restore database
    if [[ "$db_backup" == *.gz ]]; then
        gunzip -c "$db_backup" | mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
    else
        mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$db_backup"
    fi
    
    log "Database restore completed"
}

# Restore application files
restore_application() {
    if [ "$DATABASE_ONLY" = true ] || [ "$CONFIGS_ONLY" = true ]; then
        return
    fi
    
    log "Restoring application files..."
    
    local app_backup="${RESTORE_DIR}/application.tar.gz"
    if [ ! -f "$app_backup" ]; then
        warn "Application backup not found, skipping application restore"
        return
    fi
    
    # Stop services
    sudo systemctl stop nginx
    sudo systemctl stop "php8.1-fpm"
    sudo systemctl stop "${APP_NAME}-worker" || true
    
    # Backup current application
    if [ -d "$APP_DIR" ]; then
        sudo mv "$APP_DIR" "${APP_DIR}.old.$(date +%Y%m%d_%H%M%S)"
    fi
    
    # Extract application
    sudo mkdir -p "$(dirname "$APP_DIR")"
    sudo tar -xzf "$app_backup" -C "$(dirname "$APP_DIR")"
    
    # Set permissions
    sudo chown -R www-data:www-data "$APP_DIR"
    sudo chmod -R 755 "$APP_DIR"
    sudo chmod -R 775 "${APP_DIR}/storage"
    sudo chmod -R 775 "${APP_DIR}/bootstrap/cache"
    
    log "Application files restore completed"
}

# Restore storage files
restore_storage() {
    if [ "$DATABASE_ONLY" = true ] || [ "$CONFIGS_ONLY" = true ]; then
        return
    fi
    
    log "Restoring storage files..."
    
    local storage_backup="${RESTORE_DIR}/storage.tar.gz"
    if [ ! -f "$storage_backup" ]; then
        warn "Storage backup not found, skipping storage restore"
        return
    fi
    
    local storage_dir="${APP_DIR}/storage/app"
    sudo mkdir -p "$storage_dir"
    sudo tar -xzf "$storage_backup" -C "$storage_dir"
    sudo chown -R www-data:www-data "$storage_dir"
    
    log "Storage files restore completed"
}

# Restore configurations
restore_configs() {
    if [ "$DATABASE_ONLY" = true ] || [ "$FILES_ONLY" = true ]; then
        return
    fi
    
    log "Restoring configurations..."
    
    local config_dir="${RESTORE_DIR}/configs"
    if [ ! -d "$config_dir" ]; then
        warn "Configuration backup not found, skipping configuration restore"
        return
    fi
    
    # Restore environment file
    if [ -f "${config_dir}/env" ]; then
        sudo cp "${config_dir}/env" "${APP_DIR}/.env"
        sudo chown www-data:www-data "${APP_DIR}/.env"
    fi
    
    # Restore nginx configuration
    if [ -f "${config_dir}/nginx.conf" ]; then
        sudo cp "${config_dir}/nginx.conf" "/etc/nginx/sites-available/${APP_NAME}"
        sudo ln -sf "/etc/nginx/sites-available/${APP_NAME}" "/etc/nginx/sites-enabled/${APP_NAME}"
    fi
    
    # Restore PHP-FPM configuration
    if [ -f "${config_dir}/php-fpm.conf" ]; then
        sudo cp "${config_dir}/php-fpm.conf" "/etc/php/8.1/fpm/pool.d/${APP_NAME}.conf"
    fi
    
    # Restore systemd service
    if [ -f "${config_dir}/systemd.service" ]; then
        sudo cp "${config_dir}/systemd.service" "/etc/systemd/system/${APP_NAME}-worker.service"
        sudo systemctl daemon-reload
    fi
    
    log "Configuration restore completed"
}

# Post-restore tasks
post_restore_tasks() {
    log "Running post-restore tasks..."
    
    cd "$APP_DIR"
    
    # Install dependencies
    sudo -u www-data composer install --optimize-autoloader --no-dev
    sudo -u www-data npm ci --production
    sudo -u www-data npm run build
    
    # Clear and cache configurations
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache
    sudo -u www-data php artisan view:cache
    
    # Create storage link
    sudo -u www-data php artisan storage:link
    
    # Start services
    sudo systemctl start "php8.1-fpm"
    sudo systemctl start nginx
    sudo systemctl start "${APP_NAME}-worker"
    
    # Test nginx configuration
    sudo nginx -t
    
    log "Post-restore tasks completed"
}

# Verify restore
verify_restore() {
    log "Verifying restore..."
    
    # Check if application is accessible
    local app_url=$(grep APP_URL "${APP_DIR}/.env" | cut -d'=' -f2)
    if [ -n "$app_url" ]; then
        if curl -s -o /dev/null -w "%{http_code}" "$app_url" | grep -q "200\|302"; then
            log "Application is accessible"
        else
            warn "Application may not be accessible"
        fi
    fi
    
    # Check services
    if sudo systemctl is-active --quiet nginx; then
        log "Nginx is running"
    else
        warn "Nginx is not running"
    fi
    
    if sudo systemctl is-active --quiet "php8.1-fpm"; then
        log "PHP-FPM is running"
    else
        warn "PHP-FPM is not running"
    fi
    
    log "Restore verification completed"
}

# Main restore function
main() {
    log "Starting restore process..."
    
    # Create log directory
    mkdir -p "$(dirname "$LOG_FILE")"
    
    parse_args "$@"
    validate_backup
    confirm_restore
    create_pre_restore_backup
    
    restore_database
    restore_application
    restore_storage
    restore_configs
    
    post_restore_tasks
    verify_restore
    
    log "Restore process completed successfully"
    log "Please verify the application is working correctly"
}

# Error handling
trap 'error "Restore failed at line $LINENO"' ERR

# Run main function
main "$@"