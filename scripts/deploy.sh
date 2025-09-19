#!/bin/bash

# Activity Tracking System - Production Deployment Script
# This script handles the deployment of the application to production

set -e  # Exit on any error

# Configuration
APP_NAME="activity-tracking-system"
APP_DIR="/var/www/${APP_NAME}"
BACKUP_DIR="/var/backups/${APP_NAME}"
LOG_FILE="/var/log/${APP_NAME}/deploy.log"
NGINX_CONFIG="/etc/nginx/sites-available/${APP_NAME}"
PHP_VERSION="8.1"

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

# Check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        error "This script should not be run as root for security reasons"
    fi
}

# Check system requirements
check_requirements() {
    log "Checking system requirements..."
    
    # Check PHP version
    if ! command -v php &> /dev/null; then
        error "PHP is not installed"
    fi
    
    PHP_CURRENT=$(php -r "echo PHP_VERSION;")
    if ! php -r "exit(version_compare(PHP_VERSION, '8.1.0', '>=') ? 0 : 1);"; then
        error "PHP 8.1.0 or higher is required. Current version: $PHP_CURRENT"
    fi
    
    # Check required PHP extensions
    REQUIRED_EXTENSIONS=("pdo" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath" "redis")
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            error "Required PHP extension '$ext' is not installed"
        fi
    done
    
    # Check Composer
    if ! command -v composer &> /dev/null; then
        error "Composer is not installed"
    fi
    
    # Check Node.js and npm
    if ! command -v node &> /dev/null; then
        error "Node.js is not installed"
    fi
    
    if ! command -v npm &> /dev/null; then
        error "npm is not installed"
    fi
    
    log "System requirements check passed"
}

# Create backup
create_backup() {
    log "Creating backup..."
    
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_PATH="${BACKUP_DIR}/backup_${TIMESTAMP}"
    
    # Create backup directory
    sudo mkdir -p "$BACKUP_PATH"
    
    # Backup application files
    if [ -d "$APP_DIR" ]; then
        sudo cp -r "$APP_DIR" "${BACKUP_PATH}/app"
        log "Application files backed up to ${BACKUP_PATH}/app"
    fi
    
    # Backup database
    if [ ! -z "$DB_DATABASE" ] && [ ! -z "$DB_USERNAME" ]; then
        sudo mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "${BACKUP_PATH}/database.sql"
        log "Database backed up to ${BACKUP_PATH}/database.sql"
    fi
    
    # Backup nginx configuration
    if [ -f "$NGINX_CONFIG" ]; then
        sudo cp "$NGINX_CONFIG" "${BACKUP_PATH}/nginx.conf"
        log "Nginx configuration backed up"
    fi
    
    # Clean old backups (keep last 5)
    sudo find "$BACKUP_DIR" -maxdepth 1 -type d -name "backup_*" | sort -r | tail -n +6 | sudo xargs rm -rf
    
    log "Backup completed: $BACKUP_PATH"
}

# Deploy application
deploy_application() {
    log "Deploying application..."
    
    # Create application directory
    sudo mkdir -p "$APP_DIR"
    sudo chown -R www-data:www-data "$APP_DIR"
    
    # Clone or update repository
    if [ -d "${APP_DIR}/.git" ]; then
        log "Updating existing repository..."
        cd "$APP_DIR"
        sudo -u www-data git pull origin main
    else
        log "Cloning repository..."
        sudo -u www-data git clone https://github.com/agbobli5373/activity-tracking-system.git "$APP_DIR"
        cd "$APP_DIR"
    fi
    
    # Install PHP dependencies
    log "Installing PHP dependencies..."
    sudo -u www-data composer install --optimize-autoloader --no-dev
    
    # Install Node.js dependencies
    log "Installing Node.js dependencies..."
    sudo -u www-data npm ci --production
    
    # Build assets
    log "Building assets..."
    sudo -u www-data npm run build
    
    # Copy environment file
    if [ ! -f "${APP_DIR}/.env" ]; then
        log "Copying production environment file..."
        sudo -u www-data cp "${APP_DIR}/.env.production" "${APP_DIR}/.env"
        warn "Please update .env file with production settings"
    fi
    
    # Set permissions
    sudo chown -R www-data:www-data "$APP_DIR"
    sudo chmod -R 755 "$APP_DIR"
    sudo chmod -R 775 "${APP_DIR}/storage"
    sudo chmod -R 775 "${APP_DIR}/bootstrap/cache"
    
    log "Application deployment completed"
}

# Configure application
configure_application() {
    log "Configuring application..."
    
    cd "$APP_DIR"
    
    # Run production setup
    sudo -u www-data php artisan app:production-setup --force
    
    # Create storage link
    sudo -u www-data php artisan storage:link
    
    # Clear and cache configurations
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache
    sudo -u www-data php artisan view:cache
    
    log "Application configuration completed"
}

# Configure web server
configure_nginx() {
    log "Configuring Nginx..."
    
    # Create Nginx configuration
    sudo tee "$NGINX_CONFIG" > /dev/null <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    root ${APP_DIR}/public;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/your-domain.com.crt;
    ssl_certificate_key /etc/ssl/private/your-domain.com.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Logging
    access_log /var/log/nginx/${APP_NAME}_access.log;
    error_log /var/log/nginx/${APP_NAME}_error.log;

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

    # Enable site
    sudo ln -sf "$NGINX_CONFIG" "/etc/nginx/sites-enabled/${APP_NAME}"
    
    # Test nginx configuration
    sudo nginx -t || error "Nginx configuration test failed"
    
    # Reload nginx
    sudo systemctl reload nginx
    
    log "Nginx configuration completed"
}

# Configure PHP-FPM
configure_php_fpm() {
    log "Configuring PHP-FPM..."
    
    # Create PHP-FPM pool configuration
    sudo tee "/etc/php/${PHP_VERSION}/fpm/pool.d/${APP_NAME}.conf" > /dev/null <<EOF
[${APP_NAME}]
user = www-data
group = www-data
listen = /var/run/php/php${PHP_VERSION}-fpm-${APP_NAME}.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.process_idle_timeout = 10s
pm.max_requests = 500
php_admin_value[error_log] = /var/log/php/${APP_NAME}_error.log
php_admin_flag[log_errors] = on
php_value[session.save_handler] = files
php_value[session.save_path] = ${APP_DIR}/storage/framework/sessions
EOF

    # Create log directory
    sudo mkdir -p /var/log/php
    sudo chown www-data:www-data /var/log/php
    
    # Restart PHP-FPM
    sudo systemctl restart "php${PHP_VERSION}-fpm"
    
    log "PHP-FPM configuration completed"
}

# Setup monitoring
setup_monitoring() {
    log "Setting up monitoring..."
    
    # Create log rotation configuration
    sudo tee "/etc/logrotate.d/${APP_NAME}" > /dev/null <<EOF
${APP_DIR}/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php${PHP_VERSION}-fpm
    endscript
}
EOF

    # Create systemd service for queue worker
    sudo tee "/etc/systemd/system/${APP_NAME}-worker.service" > /dev/null <<EOF
[Unit]
Description=${APP_NAME} Queue Worker
After=redis.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php ${APP_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

    # Enable and start queue worker
    sudo systemctl daemon-reload
    sudo systemctl enable "${APP_NAME}-worker"
    sudo systemctl start "${APP_NAME}-worker"
    
    log "Monitoring setup completed"
}

# Main deployment function
main() {
    log "Starting deployment of Activity Tracking System..."
    
    # Load environment variables if .env exists
    if [ -f "${APP_DIR}/.env" ]; then
        source "${APP_DIR}/.env"
    fi
    
    check_root
    check_requirements
    create_backup
    deploy_application
    configure_application
    configure_nginx
    configure_php_fpm
    setup_monitoring
    
    log "Deployment completed successfully!"
    log "Please complete the following manual steps:"
    log "1. Update SSL certificates"
    log "2. Configure database credentials in .env"
    log "3. Update domain name in Nginx configuration"
    log "4. Change default admin passwords"
    log "5. Test the application"
}

# Run main function
main "$@"