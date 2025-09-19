# Activity Tracking System - Deployment Guide

This document provides comprehensive instructions for deploying the Activity Tracking System to production environments.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Pre-deployment Checklist](#pre-deployment-checklist)
3. [Server Setup](#server-setup)
4. [Application Deployment](#application-deployment)
5. [Configuration](#configuration)
6. [SSL Certificate Setup](#ssl-certificate-setup)
7. [Monitoring and Logging](#monitoring-and-logging)
8. [Backup and Recovery](#backup-and-recovery)
9. [Maintenance](#maintenance)
10. [Troubleshooting](#troubleshooting)

## System Requirements

### Minimum Hardware Requirements

-   **CPU**: 2 cores (4 cores recommended)
-   **RAM**: 4GB (8GB recommended)
-   **Storage**: 20GB SSD (50GB recommended)
-   **Network**: 100Mbps connection

### Software Requirements

-   **Operating System**: Ubuntu 20.04 LTS or newer, CentOS 8+, or RHEL 8+
-   **Web Server**: Nginx 1.18+
-   **PHP**: 8.1 or newer
-   **Database**: MySQL 8.0+ or PostgreSQL 13+
-   **Cache**: Redis 6.0+
-   **Node.js**: 16.0+ (for asset compilation)

### Required PHP Extensions

```bash
php-fpm php-mysql php-redis php-mbstring php-xml php-curl
php-zip php-gd php-intl php-bcmath php-json php-tokenizer
```

## Pre-deployment Checklist

-   [ ] Server meets minimum requirements
-   [ ] Domain name configured and DNS pointing to server
-   [ ] SSL certificate obtained
-   [ ] Database server installed and configured
-   [ ] Redis server installed and configured
-   [ ] Backup storage configured (local or cloud)
-   [ ] Monitoring tools installed (optional)
-   [ ] Firewall configured
-   [ ] SSH access configured with key-based authentication

## Server Setup

### 1. Update System

```bash
# Ubuntu/Debian
sudo apt update && sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y
```

### 2. Install Required Packages

```bash
# Ubuntu/Debian
sudo apt install -y nginx mysql-server redis-server php8.1-fpm \
    php8.1-mysql php8.1-redis php8.1-mbstring php8.1-xml \
    php8.1-curl php8.1-zip php8.1-gd php8.1-intl php8.1-bcmath \
    php8.1-json php8.1-tokenizer composer nodejs npm git unzip

# CentOS/RHEL (with EPEL and Remi repositories)
sudo yum install -y epel-release
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm
sudo yum module enable php:remi-8.1 -y
sudo yum install -y nginx mysql-server redis php-fpm php-mysql \
    php-redis php-mbstring php-xml php-curl php-zip php-gd \
    php-intl php-bcmath php-json composer nodejs npm git unzip
```

### 3. Configure Services

```bash
# Start and enable services
sudo systemctl start nginx mysql redis php8.1-fpm
sudo systemctl enable nginx mysql redis php8.1-fpm

# Secure MySQL installation
sudo mysql_secure_installation
```

### 4. Create Application User

```bash
# Create dedicated user for the application
sudo useradd -r -s /bin/false -d /var/www/activity-tracking-system activity-tracker
sudo usermod -a -G www-data activity-tracker
```

### 5. Configure Firewall

```bash
# Ubuntu (UFW)
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable

# CentOS/RHEL (firewalld)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## Application Deployment

### Automated Deployment

Use the provided deployment script for automated setup:

```bash
# Download and run deployment script
curl -O https://raw.githubusercontent.com/your-org/activity-tracking-system/main/scripts/deploy.sh
chmod +x deploy.sh
sudo ./deploy.sh
```

### Manual Deployment

#### 1. Clone Repository

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone https://github.com/agbobli5373/activity-tracking-system.git
sudo chown -R www-data:www-data activity-tracking-system
cd activity-tracking-system
```

#### 2. Install Dependencies

```bash
# PHP dependencies
sudo -u www-data composer install --optimize-autoloader --no-dev

# Node.js dependencies
sudo -u www-data npm ci --production

# Build assets
sudo -u www-data npm run build
```

#### 3. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/activity-tracking-system
sudo chmod -R 755 /var/www/activity-tracking-system
sudo chmod -R 775 /var/www/activity-tracking-system/storage
sudo chmod -R 775 /var/www/activity-tracking-system/bootstrap/cache
```

## Configuration

### 1. Environment Configuration

```bash
# Copy production environment file
sudo -u www-data cp .env.production .env

# Generate application key
sudo -u www-data php artisan key:generate
```

### 2. Database Setup

```bash
# Create database and user
mysql -u root -p <<EOF
CREATE DATABASE activity_tracking_prod;
CREATE USER 'activity_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON activity_tracking_prod.* TO 'activity_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Update .env file with database credentials
sudo -u www-data nano .env
```

Update the following variables in `.env`:

```env
DB_DATABASE=activity_tracking_prod
DB_USERNAME=activity_user
DB_PASSWORD=secure_password
```

### 3. Run Migrations and Seeders

```bash
# Run production setup
sudo -u www-data php artisan app:production-setup
```

### 4. Configure Web Server

#### Nginx Configuration

Create `/etc/nginx/sites-available/activity-tracking-system`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    root /var/www/activity-tracking-system/public;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/your-domain.com.crt;
    ssl_certificate_key /etc/ssl/private/your-domain.com.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Logging
    access_log /var/log/nginx/activity-tracking-system_access.log;
    error_log /var/log/nginx/activity-tracking-system_error.log;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
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
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/activity-tracking-system /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. Configure PHP-FPM

Create `/etc/php/8.1/fpm/pool.d/activity-tracking-system.conf`:

```ini
[activity-tracking-system]
user = www-data
group = www-data
listen = /var/run/php/php8.1-fpm-activity-tracking-system.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.process_idle_timeout = 10s
pm.max_requests = 500
php_admin_value[error_log] = /var/log/php/activity-tracking-system_error.log
php_admin_flag[log_errors] = on
php_value[session.save_handler] = files
php_value[session.save_path] = /var/www/activity-tracking-system/storage/framework/sessions
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.1-fpm
```

## SSL Certificate Setup

### Using Let's Encrypt (Recommended)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Test automatic renewal
sudo certbot renew --dry-run
```

### Using Custom Certificate

```bash
# Copy certificate files
sudo cp your-domain.com.crt /etc/ssl/certs/
sudo cp your-domain.com.key /etc/ssl/private/
sudo chmod 600 /etc/ssl/private/your-domain.com.key
```

## Monitoring and Logging

### 1. Configure Log Rotation

Create `/etc/logrotate.d/activity-tracking-system`:

```
/var/www/activity-tracking-system/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.1-fpm
    endscript
}
```

### 2. Set Up Queue Worker

Create `/etc/systemd/system/activity-tracking-system-worker.service`:

```ini
[Unit]
Description=Activity Tracking System Queue Worker
After=redis.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/activity-tracking-system/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable activity-tracking-system-worker
sudo systemctl start activity-tracking-system-worker
```

### 3. Configure Monitoring (Optional)

Install monitoring tools like Prometheus, Grafana, or use cloud monitoring services.

## Backup and Recovery

### Automated Backups

Set up automated backups using the provided script:

```bash
# Copy backup script
sudo cp scripts/backup.sh /usr/local/bin/activity-tracking-backup
sudo chmod +x /usr/local/bin/activity-tracking-backup

# Create cron job for daily backups
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/activity-tracking-backup
```

### Manual Backup

```bash
# Run backup script
sudo /usr/local/bin/activity-tracking-backup
```

### Restore from Backup

```bash
# Copy restore script
sudo cp scripts/restore.sh /usr/local/bin/activity-tracking-restore
sudo chmod +x /usr/local/bin/activity-tracking-restore

# Restore from backup
sudo /usr/local/bin/activity-tracking-restore /path/to/backup
```

## Maintenance

### Regular Maintenance Tasks

1. **Update Dependencies**

    ```bash
    sudo -u www-data composer update
    sudo -u www-data npm update
    ```

2. **Clear Caches**

    ```bash
    sudo -u www-data php artisan cache:clear
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache
    sudo -u www-data php artisan view:cache
    ```

3. **Database Maintenance**

    ```bash
    sudo -u www-data php artisan migrate
    ```

4. **Log Cleanup**
    ```bash
    sudo logrotate -f /etc/logrotate.d/activity-tracking-system
    ```

### Security Updates

1. **System Updates**

    ```bash
    sudo apt update && sudo apt upgrade -y
    sudo systemctl restart nginx php8.1-fpm
    ```

2. **Application Updates**
    ```bash
    cd /var/www/activity-tracking-system
    sudo -u www-data git pull origin main
    sudo -u www-data composer install --optimize-autoloader --no-dev
    sudo -u www-data npm ci --production
    sudo -u www-data npm run build
    sudo -u www-data php artisan migrate --force
    sudo -u www-data php artisan config:cache
    ```

## Troubleshooting

### Common Issues

#### 1. Application Not Loading

**Symptoms**: 500 Internal Server Error or blank page

**Solutions**:

-   Check PHP-FPM status: `sudo systemctl status php8.1-fpm`
-   Check Nginx error logs: `sudo tail -f /var/log/nginx/activity-tracking-system_error.log`
-   Check application logs: `sudo tail -f /var/www/activity-tracking-system/storage/logs/laravel.log`
-   Verify file permissions: `sudo chown -R www-data:www-data /var/www/activity-tracking-system`

#### 2. Database Connection Issues

**Symptoms**: Database connection errors

**Solutions**:

-   Verify database credentials in `.env`
-   Check MySQL status: `sudo systemctl status mysql`
-   Test database connection: `mysql -u activity_user -p activity_tracking_prod`

#### 3. Queue Jobs Not Processing

**Symptoms**: Activities not updating, reports not generating

**Solutions**:

-   Check queue worker status: `sudo systemctl status activity-tracking-system-worker`
-   Restart queue worker: `sudo systemctl restart activity-tracking-system-worker`
-   Check Redis connection: `redis-cli ping`

#### 4. SSL Certificate Issues

**Symptoms**: SSL warnings or certificate errors

**Solutions**:

-   Verify certificate files exist and have correct permissions
-   Check certificate expiration: `openssl x509 -in /etc/ssl/certs/your-domain.com.crt -text -noout`
-   Renew Let's Encrypt certificate: `sudo certbot renew`

### Performance Optimization

1. **Enable OPcache**

    ```bash
    # Add to /etc/php/8.1/fpm/php.ini
    opcache.enable=1
    opcache.memory_consumption=256
    opcache.max_accelerated_files=20000
    opcache.validate_timestamps=0
    ```

2. **Optimize MySQL**

    ```bash
    # Add to /etc/mysql/mysql.conf.d/mysqld.cnf
    innodb_buffer_pool_size=1G
    innodb_log_file_size=256M
    query_cache_type=1
    query_cache_size=64M
    ```

3. **Configure Redis**
    ```bash
    # Add to /etc/redis/redis.conf
    maxmemory 512mb
    maxmemory-policy allkeys-lru
    ```

### Getting Help

-   Check application logs: `/var/www/activity-tracking-system/storage/logs/`
-   Check system logs: `sudo journalctl -u nginx -u php8.1-fpm -u mysql`
-   Review error logs: `/var/log/nginx/`, `/var/log/php/`
-   Contact support with error details and system information

## Security Considerations

1. **Regular Updates**: Keep all software components updated
2. **Strong Passwords**: Use complex passwords for all accounts
3. **Firewall**: Configure firewall to allow only necessary ports
4. **SSL/TLS**: Always use HTTPS in production
5. **Backup Encryption**: Encrypt sensitive backup data
6. **Access Control**: Limit SSH access and use key-based authentication
7. **Monitoring**: Set up monitoring and alerting for security events

## Conclusion

This deployment guide provides comprehensive instructions for setting up the Activity Tracking System in a production environment. Follow all steps carefully and ensure proper testing before going live. Regular maintenance and monitoring are essential for optimal performance and security.
