# Installation Guide - Notary Management System

This guide will walk you through the complete installation process of the Notary Management System.

## 📋 Prerequisites

Before installing, ensure your server meets these requirements:

### System Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher (or MariaDB 10.2+)
- **Web Server**: Apache 2.4+ or Nginx 1.14+
- **Memory**: 512MB RAM minimum (1GB recommended)
- **Storage**: 1GB free disk space minimum

### PHP Extensions Required
```bash
# Check if extensions are installed
php -m | grep -E "(pdo|pdo_mysql|session|json|openssl|mbstring|fileinfo)"
```

Required extensions:
- PDO
- PDO_MySQL
- Session
- JSON
- OpenSSL (recommended)
- mbstring
- fileinfo

## 🚀 Step-by-Step Installation

### Step 1: Download the Application

Download or clone the Notary Management System files to your web server directory.

```bash
# Option 1: Download and extract
# Download the ZIP file and extract to your web directory

# Option 2: Clone from repository (if available)
git clone [repository-url] /var/www/html/notary-management-system
cd /var/www/html/notary-management-system
```

### Step 2: Set File Permissions

Set appropriate permissions for the application directories:

```bash
# Navigate to the application directory
cd /var/www/html/notary-management-system

# Set directory permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Set write permissions for upload directories
chmod -R 775 uploads/
chmod -R 775 uploads/documents/
chmod -R 775 uploads/signatures/

# Change ownership to web server user (example for Apache)
chown -R www-data:www-data .
```

### Step 3: Database Setup

#### Create the Database

```sql
-- Connect to MySQL as root
mysql -u root -p

-- Create database
CREATE DATABASE notary_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create database user (recommended for security)
CREATE USER 'notary_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON notary_management.* TO 'notary_user'@'localhost';
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

#### Import Database Schema

```bash
# Import the database schema
mysql -u notary_user -p notary_management < config/database.sql

# Verify tables were created
mysql -u notary_user -p notary_management -e "SHOW TABLES;"
```

### Step 4: Configure the Application

Edit the configuration file `config/config.php`:

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'notary_management');
define('DB_USER', 'notary_user');
define('DB_PASS', 'your_secure_password');

// Application Configuration
define('APP_NAME', 'Notary Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://your-domain.com/notary-management-system/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('ENCRYPTION_KEY', 'change-this-to-a-secure-random-string');

// Email Configuration (optional)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@your-domain.com');
define('FROM_NAME', 'Notary Management System');

// Time Zone
date_default_timezone_set('America/Los_Angeles');
?>
```

### Step 5: Web Server Configuration

#### Apache Configuration

Create or edit `.htaccess` file in the application root:

```apache
RewriteEngine On

# Redirect to HTTPS (recommended)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Handle routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Hide PHP version
Header unset X-Powered-By

# Prevent access to sensitive files
<FilesMatch "\.(htaccess|htpasswd|ini|log|sql)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Protect config directory
<Directory "config">
    Order Allow,Deny
    Deny from all
</Directory>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Browser caching
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
```

#### Nginx Configuration

Add this to your Nginx server block:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/notary-management-system;
    index index.php index.html;

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    # Hide server version
    server_tokens off;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_param HTTP_PROXY "";
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to sensitive files
    location ~ /\.(ht|git|svn) {
        deny all;
    }

    location ~ \.(sql|log|ini)$ {
        deny all;
    }

    # Protect config directory
    location ^~ /config/ {
        deny all;
    }

    # Static file caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript;
}
```

### Step 6: PHP Configuration

Edit your `php.ini` file for optimal performance:

```ini
# File upload settings
upload_max_filesize = 10M
post_max_size = 12M
max_file_uploads = 20

# Memory and execution
memory_limit = 256M
max_execution_time = 300
max_input_time = 300

# Session settings
session.cookie_httponly = 1
session.cookie_secure = 1  ; Set to 1 if using HTTPS
session.use_strict_mode = 1
session.gc_maxlifetime = 3600

# Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

# Error reporting (set to Off in production)
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
```

### Step 7: Test the Installation

1. **Access the application** in your browser:
   ```
   http://your-domain.com/notary-management-system/
   ```

2. **Test login** with default accounts:
   - **Admin**: username `admin`, password `admin123`
   - **Notary**: username `notary1`, password `notary123`
   - **Client**: username `client1`, password `client123`

3. **Verify functionality**:
   - Dashboard loads correctly
   - Navigation works
   - User can log in and out
   - Database connection is working

### Step 8: Security Hardening

#### Change Default Passwords
```sql
-- Connect to database
mysql -u notary_user -p notary_management

-- Update admin password (replace 'new_secure_password' with your password)
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE username = 'admin';

-- Remove or change demo accounts as needed
```

#### Generate Secure Encryption Key
```php
// Add to config/config.php
define('ENCRYPTION_KEY', bin2hex(random_bytes(32)));
```

#### Set Proper File Permissions
```bash
# Remove write permissions from config files
chmod 644 config/config.php

# Ensure uploads directory is not executable
chmod 644 uploads/documents/*
chmod 644 uploads/signatures/*
```

## 🔧 Optional Configurations

### SSL/HTTPS Setup

For production environments, configure SSL:

```bash
# Using Let's Encrypt with Certbot
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

### Email Configuration

For email notifications, configure SMTP settings in `config/config.php`:

```php
// Gmail example
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password'); // Use app-specific password
```

### Backup Configuration

Set up automated backups:

```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="notary_management"
BACKUP_DIR="/path/to/backups"

# Database backup
mysqldump -u notary_user -p$DB_PASSWORD $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Files backup
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/html/notary-management-system/uploads/
```

## 🐛 Troubleshooting

### Common Installation Issues

#### Database Connection Failed
```bash
# Check MySQL service
sudo systemctl status mysql
sudo systemctl start mysql

# Test connection
mysql -u notary_user -p notary_management -e "SELECT 1;"
```

#### Permission Denied Errors
```bash
# Check file ownership
ls -la /var/www/html/notary-management-system/

# Fix ownership
sudo chown -R www-data:www-data /var/www/html/notary-management-system/
```

#### PHP Extensions Missing
```bash
# Install missing extensions (Ubuntu/Debian)
sudo apt-get install php-mysql php-mbstring php-xml

# Restart web server
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

#### Upload Directory Not Writable
```bash
# Check permissions
ls -la uploads/

# Fix permissions
chmod 775 uploads/
chown www-data:www-data uploads/
```

### Log File Locations

Check these logs for debugging:
- Apache: `/var/log/apache2/error.log`
- Nginx: `/var/log/nginx/error.log`
- PHP: `/var/log/php/error.log`
- MySQL: `/var/log/mysql/error.log`

## ✅ Post-Installation Checklist

- [ ] Database created and imported successfully
- [ ] Configuration file updated with correct settings
- [ ] File permissions set correctly
- [ ] Web server configured properly
- [ ] Application accessible via browser
- [ ] Default login accounts working
- [ ] Upload functionality tested
- [ ] Default passwords changed
- [ ] SSL certificate installed (production)
- [ ] Backup system configured
- [ ] Monitoring setup (optional)

## 🔄 Updates and Maintenance

### Updating the Application
1. Backup database and files
2. Download new version
3. Replace files (preserve config/config.php)
4. Run any database migrations
5. Clear cache/sessions if needed

### Regular Maintenance
- Monitor disk space for uploads
- Rotate log files
- Update PHP and system packages
- Review security logs
- Test backup restoration

## 📞 Support

If you encounter issues during installation:
1. Check the troubleshooting section above
2. Review log files for error messages
3. Verify all requirements are met
4. Consult the main README.md file
5. Create an issue with detailed error information

---

**Installation complete! Your Notary Management System is ready to use.**