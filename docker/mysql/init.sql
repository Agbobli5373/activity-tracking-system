-- Initialize database with proper charset and collation
CREATE DATABASE IF NOT EXISTS activity_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user with proper permissions
CREATE USER IF NOT EXISTS 'activity_user'@'%' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON activity_tracking.* TO 'activity_user'@'%';
FLUSH PRIVILEGES;