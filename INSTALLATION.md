# Activity Tracking System - Installation Guide

## Overview

The Activity Tracking System is a Laravel-based web application designed for support teams to manage and track daily activities. This guide will walk you through the complete installation and setup process.

## System Requirements

### Server Requirements

-   **PHP:** 8.0.2 or higher
-   **Web Server:** Apache 2.4+ or Nginx 1.18+
-   **Database:** MySQL 8.0+ or PostgreSQL 13+
-   **Node.js:** 16.x or higher (for frontend assets)
-   **Composer:** 2.x or higher
-   **Redis:** 6.x or higher (optional, for caching and sessions)

### PHP Extensions

Ensure the following PHP extensions are installed:

-   BCMath
-   Ctype
-   cURL
-   DOM
-   Fileinfo
-   JSON
-   Mbstring
-   OpenSSL
-   PCRE
-   PDO
-   Tokenizer
-   XML

## Installation Steps

### 1. Clone or Download the Project

```bash
# If using Git
git clone https://github.com/agbobli5373/activity-tracking-system.git
cd activity-tracking-system

# Or extract from archive if downloaded
```

### 2. Install PHP Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

For development environment:

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Environment Configuration

#### 4.1 Create Environment File

```bash
cp .env.example .env
```

#### 4.2 Configure Database Settings

Edit the `.env` file and update the database configuration:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=activity_tracking
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

For PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=activity_tracking
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### 4.3 Configure Application Settings

```env
APP_NAME="Activity Tracking System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

# Session and Cache Configuration
SESSION_DRIVER=database
CACHE_DRIVER=file

# Optional: Redis Configuration
# SESSION_DRIVER=redis
# CACHE_DRIVER=redis
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Database Setup

#### 6.1 Create Database

Create the database specified in your `.env` file:

**MySQL:**

```sql
CREATE DATABASE activity_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**PostgreSQL:**

```sql
CREATE DATABASE activity_tracking;
```

#### 6.2 Run Migrations

```bash
php artisan migrate
```

#### 6.3 Seed Initial Data

```bash
php artisan db:seed
```

This will create:

-   Default admin user (admin@company.com / password)
-   Sample departments and roles
-   Test activities (in development environment)

### 7. Build Frontend Assets

#### For Production:

```bash
npm run build
```

#### For Development:

```bash
npm run dev
```

### 8. Set File Permissions

#### Linux/macOS:

```bash
# Set ownership (replace www-data with your web server user)
sudo chown -R www-data:www-data storage bootstrap/cache

# Set permissions
sudo chmod -R 755 storage bootstrap/cache
sudo chmod -R 644 storage/logs
```

#### Windows:

Ensure the web server has read/write access to:

-   `storage/` directory and subdirectories
-   `bootstrap/cache/` directory

### 9. Web Server Configuration

#### Apache Configuration

Create a virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/activity-tracking-system/public

    <Directory /path/to/activity-tracking-system/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/activity-tracking-error.log
    CustomLog ${APACHE_LOG_DIR}/activity-tracking-access.log combined
</VirtualHost>
```

For HTTPS (recommended):

```apache
<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /path/to/activity-tracking-system/public

    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key

    <Directory /path/to/activity-tracking-system/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/activity-tracking-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/activity-tracking-ssl-access.log combined
</VirtualHost>
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/activity-tracking-system/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

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
}
```

### 10. Optional: Configure Caching and Queues

#### Enable Redis (Recommended for Production)

1. Install Redis server
2. Update `.env` file:

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

#### Configure Queue Worker (Optional)

For background job processing:

```bash
# Install supervisor (Linux)
sudo apt-get install supervisor

# Create supervisor configuration
sudo nano /etc/supervisor/conf.d/activity-tracking-worker.conf
```

Supervisor configuration:

```ini
[program:activity-tracking-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/activity-tracking-system/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/activity-tracking-system/storage/logs/worker.log
stopwaitsecs=3600
```

Start the worker:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start activity-tracking-worker:*
```

## Post-Installation Configuration

### 1. Create Admin User

If you need to create additional admin users:

```bash
php artisan tinker
```

```php
use App\Models\User;

User::create([
    'name' => 'Admin User',
    'email' => 'admin@yourcompany.com',
    'employee_id' => 'EMP001',
    'role' => 'admin',
    'department' => 'IT',
    'password' => bcrypt('secure-password'),
    'email_verified_at' => now(),
]);
```

### 2. Configure Application Settings

Access the application at your configured URL and log in with:

-   **Email:** admin@company.com
-   **Password:** password

**Important:** Change the default password immediately after first login.

### 3. Set Up Backup Strategy

#### Database Backup Script

Create a backup script (`scripts/backup.sh`):

```bash
#!/bin/bash
BACKUP_DIR="/path/to/backups"
DB_NAME="activity_tracking"
DB_USER="your_username"
DB_PASS="your_password"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# MySQL backup
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/activity_tracking_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/activity_tracking_$DATE.sql

# Remove backups older than 30 days
find $BACKUP_DIR -name "activity_tracking_*.sql.gz" -mtime +30 -delete

echo "Backup completed: activity_tracking_$DATE.sql.gz"
```

Make it executable and add to cron:

```bash
chmod +x scripts/backup.sh

# Add to crontab for daily backups at 2 AM
crontab -e
# Add: 0 2 * * * /path/to/activity-tracking-system/scripts/backup.sh
```

## Troubleshooting

### Common Issues

#### 1. Permission Errors

**Error:** "The stream or file could not be opened in append mode"

**Solution:**

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache
```

#### 2. Database Connection Issues

**Error:** "SQLSTATE[HY000] [2002] Connection refused"

**Solutions:**

-   Verify database server is running
-   Check database credentials in `.env`
-   Ensure database exists
-   Check firewall settings

#### 3. 500 Internal Server Error

**Solutions:**

-   Check Laravel logs: `storage/logs/laravel.log`
-   Verify `.env` file configuration
-   Ensure `APP_KEY` is set
-   Check file permissions

#### 4. Assets Not Loading

**Solutions:**

-   Run `npm run build` for production
-   Check web server configuration
-   Verify `APP_URL` in `.env` matches your domain

#### 5. Session Issues

**Error:** Users getting logged out frequently

**Solutions:**

-   Check `SESSION_DRIVER` in `.env`
-   Ensure session storage is writable
-   Consider using Redis for sessions

### Performance Optimization

#### 1. Enable Caching

```bash
# Cache configuration
php artisan config:cache

# Cache routes (production only)
php artisan route:cache

# Cache views
php artisan view:cache
```

#### 2. Optimize Autoloader

```bash
composer install --optimize-autoloader --no-dev
```

#### 3. Enable OPcache

Add to your PHP configuration:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

## Security Considerations

### 1. Environment Security

-   Keep `.env` file secure and never commit to version control
-   Use strong, unique passwords
-   Enable HTTPS in production
-   Regular security updates

### 2. Database Security

-   Use dedicated database user with minimal privileges
-   Enable database firewall rules
-   Regular database backups
-   Monitor for suspicious activity

### 3. Application Security

-   Keep Laravel and dependencies updated
-   Monitor application logs
-   Implement rate limiting if needed
-   Regular security audits

## Maintenance

### Regular Tasks

1. **Daily:** Monitor application logs
2. **Weekly:** Check disk space and performance
3. **Monthly:** Update dependencies and security patches
4. **Quarterly:** Review user access and permissions

### Update Process

```bash
# Backup database and files
./scripts/backup.sh

# Update dependencies
composer update
npm update

# Run migrations
php artisan migrate

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Rebuild assets
npm run build

# Update caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Support

For technical support or questions:

-   Check application logs in `storage/logs/`
-   Review Laravel documentation: https://laravel.com/docs
-   Contact system administrator

## Docker Deployment

The Activity Tracking System includes comprehensive Docker support for both development and production environments.

### Docker Requirements

-   **Docker:** 20.10+ or higher
-   **Docker Compose:** 2.0+ or higher
-   **System Memory:** Minimum 2GB RAM available for containers
-   **Disk Space:** Minimum 5GB free space

### Quick Start with Docker

#### Production Deployment

1. **Clone the repository:**

```bash
git clone <repository-url> activity-tracking-system
cd activity-tracking-system
```

2. **Configure environment:**

```bash
cp .env.example .env
```

Edit `.env` file with production settings:

```env
APP_NAME="Activity Tracking System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=activity_tracking
DB_USERNAME=activity_user
DB_PASSWORD=secure_password_here

CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

3. **Start the application:**

```bash
docker-compose up -d
```

4. **Initialize the database:**

```bash
# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Seed initial data
docker-compose exec app php artisan db:seed
```

5. **Access the application:**

-   Application: http://localhost
-   Database: localhost:3306
-   Redis: localhost:6379

#### Development Environment

For development with hot reloading and debugging:

1. **Start development environment:**

```bash
docker-compose -f docker-compose.dev.yml up -d
```

2. **Initialize development database:**

```bash
docker-compose -f docker-compose.dev.yml exec app php artisan key:generate
docker-compose -f docker-compose.dev.yml exec app php artisan migrate
docker-compose -f docker-compose.dev.yml exec app php artisan db:seed
```

3. **Access development services:**

-   Application: http://localhost:8000
-   Database: localhost:3307
-   Redis: localhost:6380
-   MailHog (email testing): http://localhost:8025

### Docker Architecture

The Docker setup includes the following services:

#### Production Services (`docker-compose.yml`)

-   **app**: Laravel application with PHP-FPM, Nginx, and Supervisor
-   **db**: MySQL 8.0 database server
-   **redis**: Redis cache and session store
-   **nginx**: Reverse proxy and static file server

#### Development Services (`docker-compose.dev.yml`)

-   **app**: Laravel application with development tools
-   **db**: MySQL 8.0 database server (separate from production)
-   **redis**: Redis cache and session store
-   **mailhog**: Email testing service

### Container Configuration

#### Application Container (Dockerfile)

The main application container includes:

-   **Base Image**: PHP 8.1 FPM Alpine
-   **Web Server**: Nginx
-   **Process Manager**: Supervisor
-   **Queue Worker**: Laravel queue worker
-   **Extensions**: All required PHP extensions
-   **Optimization**: Multi-stage build with asset compilation

#### Key Features:

-   Multi-stage build for optimized production images
-   Automatic asset compilation with Node.js
-   Supervisor for process management
-   Optimized PHP and Nginx configurations
-   Security headers and best practices

### Docker Commands

#### Basic Operations

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app

# Restart a service
docker-compose restart app

# Execute commands in container
docker-compose exec app php artisan migrate
docker-compose exec app php artisan cache:clear
```

#### Development Commands

```bash
# Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install

# Run tests
docker-compose exec app php artisan test

# Access container shell
docker-compose exec app sh

# Watch for file changes (development)
docker-compose exec app npm run dev
```

#### Database Operations

```bash
# Access MySQL shell
docker-compose exec db mysql -u activity_user -p activity_tracking

# Backup database
docker-compose exec db mysqldump -u activity_user -p activity_tracking > backup.sql

# Restore database
docker-compose exec -T db mysql -u activity_user -p activity_tracking < backup.sql

# Reset database
docker-compose exec app php artisan migrate:fresh --seed
```

### Environment Variables

#### Required Environment Variables

```env
# Application
APP_NAME="Activity Tracking System"
APP_ENV=production
APP_KEY=base64:generated_key_here
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=activity_tracking
DB_USERNAME=activity_user
DB_PASSWORD=secure_password

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis
```

#### Optional Environment Variables

```env
# Mail Configuration (if using email features)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error

# Security
SESSION_LIFETIME=120
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,your-domain.com
```

### SSL/HTTPS Configuration

#### Using Let's Encrypt with Docker

1. **Install Certbot:**

```bash
# Create SSL directory
mkdir -p docker/ssl

# Generate certificates (replace your-domain.com)
docker run --rm -v $(pwd)/docker/ssl:/etc/letsencrypt certbot/certbot \
  certonly --standalone -d your-domain.com --email admin@your-domain.com --agree-tos
```

2. **Update nginx configuration:**

Uncomment the HTTPS server block in `docker/nginx/default.conf` and update paths:

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /etc/nginx/ssl/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/live/your-domain.com/privkey.pem;

    # ... rest of configuration
}
```

3. **Update docker-compose.yml:**

```yaml
nginx:
    volumes:
        - ./docker/ssl:/etc/nginx/ssl:ro
```

### Production Deployment Best Practices

#### 1. Security Configuration

```bash
# Use strong passwords
DB_PASSWORD=$(openssl rand -base64 32)
MYSQL_ROOT_PASSWORD=$(openssl rand -base64 32)

# Restrict network access
# Only expose necessary ports
```

#### 2. Resource Limits

Add resource limits to `docker-compose.yml`:

```yaml
services:
    app:
        deploy:
            resources:
                limits:
                    memory: 512M
                    cpus: "0.5"
                reservations:
                    memory: 256M
                    cpus: "0.25"
```

#### 3. Health Checks

```yaml
services:
    app:
        healthcheck:
            test: ["CMD", "curl", "-f", "http://localhost/health"]
            interval: 30s
            timeout: 10s
            retries: 3
            start_period: 40s
```

#### 4. Backup Strategy

```bash
# Create backup script
cat > docker-backup.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="./backups"

mkdir -p $BACKUP_DIR

# Database backup
docker-compose exec -T db mysqldump -u activity_user -p$DB_PASSWORD activity_tracking | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Application files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz storage/ .env

echo "Backup completed: $DATE"
EOF

chmod +x docker-backup.sh
```

### Monitoring and Logging

#### Container Logs

```bash
# View all logs
docker-compose logs -f

# View application logs
docker-compose exec app tail -f storage/logs/laravel.log

# View nginx access logs
docker-compose logs nginx | grep "GET\|POST"

# View database logs
docker-compose logs db
```

#### Performance Monitoring

```bash
# Container resource usage
docker stats

# Specific container stats
docker stats activity-tracking-app

# Container processes
docker-compose exec app ps aux
```

### Troubleshooting Docker Issues

#### Common Problems

1. **Port conflicts:**

```bash
# Check port usage
netstat -tulpn | grep :80
netstat -tulpn | grep :3306

# Use different ports
docker-compose up -d --scale nginx=0
```

2. **Permission issues:**

```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 755 storage bootstrap/cache
```

3. **Database connection issues:**

```bash
# Check database status
docker-compose exec db mysql -u root -p -e "SHOW DATABASES;"

# Verify network connectivity
docker-compose exec app ping db
```

4. **Memory issues:**

```bash
# Check container memory usage
docker stats --no-stream

# Increase memory limits in docker-compose.yml
```

#### Container Debugging

```bash
# Access container shell
docker-compose exec app sh

# Check PHP configuration
docker-compose exec app php -i

# Check nginx configuration
docker-compose exec nginx nginx -t

# View container processes
docker-compose exec app ps aux
```

### Scaling and Load Balancing

#### Horizontal Scaling

```bash
# Scale application containers
docker-compose up -d --scale app=3

# Use load balancer
# Update nginx configuration for upstream servers
```

#### Database Scaling

```yaml
# Add read replica
services:
    db-replica:
        image: mysql:8.0
        environment:
            MYSQL_MASTER_HOST: db
            MYSQL_REPLICATION_USER: replica
            MYSQL_REPLICATION_PASSWORD: replica_password
```

### Docker Maintenance

#### Regular Tasks

```bash
# Update images
docker-compose pull
docker-compose up -d

# Clean up unused resources
docker system prune -f

# Remove old images
docker image prune -f

# Backup before updates
./docker-backup.sh
```

#### Container Updates

```bash
# Rebuild containers
docker-compose build --no-cache
docker-compose up -d

# Rolling updates
docker-compose up -d --no-deps app
```

---

**Installation completed successfully!** Your Activity Tracking System should now be accessible at your configured URL.

### Default Login Credentials

-   **Email:** admin@company.com
-   **Password:** password

**Important:** Change the default password immediately after first login.
