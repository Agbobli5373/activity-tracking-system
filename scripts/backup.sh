#!/bin/bash

# Activity Tracking System - Backup Script
# This script creates automated backups of the application and database

set -e  # Exit on any error

# Configuration
APP_NAME="activity-tracking-system"
APP_DIR="/var/www/${APP_NAME}"
BACKUP_DIR="/var/backups/${APP_NAME}"
LOG_FILE="/var/log/${APP_NAME}/backup.log"
RETENTION_DAYS=${BACKUP_RETENTION_DAYS:-30}
S3_BUCKET=${BACKUP_S3_BUCKET:-""}
ENCRYPTION_KEY=${BACKUP_ENCRYPTION_KEY:-""}

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

# Load environment variables
load_env() {
    if [ -f "${APP_DIR}/.env" ]; then
        source "${APP_DIR}/.env"
    else
        error "Environment file not found: ${APP_DIR}/.env"
    fi
}

# Create backup directory
create_backup_dir() {
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_PATH="${BACKUP_DIR}/${TIMESTAMP}"
    
    mkdir -p "$BACKUP_PATH"
    log "Created backup directory: $BACKUP_PATH"
}

# Backup database
backup_database() {
    log "Starting database backup..."
    
    if [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ]; then
        warn "Database credentials not found, skipping database backup"
        return
    fi
    
    local db_backup_file="${BACKUP_PATH}/database.sql"
    
    # Create database dump
    mysqldump \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --databases "$DB_DATABASE" > "$db_backup_file"
    
    # Compress database backup
    gzip "$db_backup_file"
    
    log "Database backup completed: ${db_backup_file}.gz"
}

# Backup application files
backup_application() {
    log "Starting application backup..."
    
    local app_backup_file="${BACKUP_PATH}/application.tar.gz"
    
    # Create application backup excluding unnecessary files
    tar -czf "$app_backup_file" \
        -C "$(dirname "$APP_DIR")" \
        --exclude="$(basename "$APP_DIR")/node_modules" \
        --exclude="$(basename "$APP_DIR")/vendor" \
        --exclude="$(basename "$APP_DIR")/storage/logs/*" \
        --exclude="$(basename "$APP_DIR")/storage/framework/cache/*" \
        --exclude="$(basename "$APP_DIR")/storage/framework/sessions/*" \
        --exclude="$(basename "$APP_DIR")/storage/framework/views/*" \
        --exclude="$(basename "$APP_DIR")/.git" \
        "$(basename "$APP_DIR")"
    
    log "Application backup completed: $app_backup_file"
}

# Backup storage files
backup_storage() {
    log "Starting storage backup..."
    
    local storage_backup_file="${BACKUP_PATH}/storage.tar.gz"
    local storage_dir="${APP_DIR}/storage/app"
    
    if [ -d "$storage_dir" ]; then
        tar -czf "$storage_backup_file" -C "$storage_dir" .
        log "Storage backup completed: $storage_backup_file"
    else
        warn "Storage directory not found: $storage_dir"
    fi
}

# Backup configuration files
backup_configs() {
    log "Starting configuration backup..."
    
    local config_dir="${BACKUP_PATH}/configs"
    mkdir -p "$config_dir"
    
    # Backup environment file
    if [ -f "${APP_DIR}/.env" ]; then
        cp "${APP_DIR}/.env" "${config_dir}/env"
    fi
    
    # Backup nginx configuration
    local nginx_config="/etc/nginx/sites-available/${APP_NAME}"
    if [ -f "$nginx_config" ]; then
        cp "$nginx_config" "${config_dir}/nginx.conf"
    fi
    
    # Backup PHP-FPM configuration
    local php_config="/etc/php/8.1/fpm/pool.d/${APP_NAME}.conf"
    if [ -f "$php_config" ]; then
        cp "$php_config" "${config_dir}/php-fpm.conf"
    fi
    
    # Backup systemd service
    local systemd_service="/etc/systemd/system/${APP_NAME}-worker.service"
    if [ -f "$systemd_service" ]; then
        cp "$systemd_service" "${config_dir}/systemd.service"
    fi
    
    log "Configuration backup completed"
}

# Encrypt backup if encryption key is provided
encrypt_backup() {
    if [ -n "$ENCRYPTION_KEY" ]; then
        log "Encrypting backup..."
        
        local encrypted_file="${BACKUP_PATH}.tar.gz.enc"
        
        # Create encrypted archive
        tar -czf - -C "$BACKUP_DIR" "$(basename "$BACKUP_PATH")" | \
        openssl enc -aes-256-cbc -salt -k "$ENCRYPTION_KEY" -out "$encrypted_file"
        
        # Remove unencrypted backup
        rm -rf "$BACKUP_PATH"
        
        BACKUP_PATH="$encrypted_file"
        log "Backup encrypted: $encrypted_file"
    fi
}

# Upload to S3 if configured
upload_to_s3() {
    if [ -n "$S3_BUCKET" ] && command -v aws &> /dev/null; then
        log "Uploading backup to S3..."
        
        local s3_key="backups/$(basename "$BACKUP_PATH")"
        
        aws s3 cp "$BACKUP_PATH" "s3://${S3_BUCKET}/${s3_key}" \
            --storage-class STANDARD_IA \
            --server-side-encryption AES256
        
        log "Backup uploaded to S3: s3://${S3_BUCKET}/${s3_key}"
    fi
}

# Clean old backups
cleanup_old_backups() {
    log "Cleaning up old backups..."
    
    # Clean local backups
    find "$BACKUP_DIR" -type f -name "*.tar.gz*" -mtime +$RETENTION_DAYS -delete
    find "$BACKUP_DIR" -type d -empty -delete
    
    # Clean S3 backups if configured
    if [ -n "$S3_BUCKET" ] && command -v aws &> /dev/null; then
        local cutoff_date=$(date -d "${RETENTION_DAYS} days ago" +%Y-%m-%d)
        
        aws s3 ls "s3://${S3_BUCKET}/backups/" | \
        awk '{print $1" "$2" "$4}' | \
        while read -r date time file; do
            if [[ "$date" < "$cutoff_date" ]]; then
                aws s3 rm "s3://${S3_BUCKET}/backups/${file}"
                log "Deleted old S3 backup: $file"
            fi
        done
    fi
    
    log "Cleanup completed"
}

# Create backup manifest
create_manifest() {
    local manifest_file="${BACKUP_PATH}/manifest.json"
    
    cat > "$manifest_file" <<EOF
{
    "backup_date": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "app_name": "$APP_NAME",
    "app_version": "$(cd "$APP_DIR" && git rev-parse HEAD 2>/dev/null || echo 'unknown')",
    "database": "$DB_DATABASE",
    "files": [
        "database.sql.gz",
        "application.tar.gz",
        "storage.tar.gz",
        "configs/"
    ],
    "retention_days": $RETENTION_DAYS,
    "encrypted": $([ -n "$ENCRYPTION_KEY" ] && echo "true" || echo "false")
}
EOF
    
    log "Backup manifest created"
}

# Send notification
send_notification() {
    local status=$1
    local message=$2
    
    # Send email notification if configured
    if [ -n "$NOTIFICATION_EMAIL" ] && command -v mail &> /dev/null; then
        echo "$message" | mail -s "[$APP_NAME] Backup $status" "$NOTIFICATION_EMAIL"
    fi
    
    # Send Slack notification if configured
    if [ -n "$SLACK_WEBHOOK_URL" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"[$APP_NAME] Backup $status: $message\"}" \
            "$SLACK_WEBHOOK_URL"
    fi
}

# Main backup function
main() {
    log "Starting backup process..."
    
    # Create log directory
    mkdir -p "$(dirname "$LOG_FILE")"
    
    # Load environment
    load_env
    
    # Create backup directory
    create_backup_dir
    
    # Perform backups
    backup_database
    backup_application
    backup_storage
    backup_configs
    create_manifest
    
    # Post-processing
    encrypt_backup
    upload_to_s3
    cleanup_old_backups
    
    log "Backup process completed successfully"
    log "Backup location: $BACKUP_PATH"
    
    send_notification "Success" "Backup completed successfully at $(date)"
}

# Error handling
trap 'error "Backup failed at line $LINENO"' ERR

# Run main function
main "$@"