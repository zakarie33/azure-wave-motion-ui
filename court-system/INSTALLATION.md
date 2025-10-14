# 🚀 Installation Guide - Digital Court Case Management System

**Author**: Court System Development Team  
**Version**: 1.0.0  
**Date**: 2024  
**License**: MIT  

## 📋 System Requirements

### Minimum Requirements
- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Memory**: 512MB RAM minimum
- **Storage**: 2GB free space minimum
- **Extensions**: PDO, PDO_MySQL, JSON, MBString, FileInfo, GD

### Recommended Requirements
- **PHP**: 8.2 or higher
- **MySQL**: 8.0.30 or higher
- **Memory**: 2GB RAM
- **Storage**: 10GB free space
- **SSL Certificate**: For HTTPS (required for production)

## 🔧 Installation Steps

### Step 1: Download and Extract
```bash
# Download the system files
# Extract to your web server directory
cd /var/www/html/
# or
cd /path/to/your/webroot/

# Ensure the court-system folder is in your web directory
```

### Step 2: Set File Permissions
```bash
# Navigate to the court-system directory
cd court-system/

# Set proper permissions
chmod 755 .
chmod 755 uploads/
chmod 755 uploads/documents/
chmod 755 uploads/temp/
chmod 644 *.php
chmod 644 config/*.php
chmod 644 includes/*.php
chmod 644 models/*.php
chmod 644 views/**/*.php

# Make installation script executable
chmod +x database/install.php
```

### Step 3: Configure Database
1. **Create MySQL Database**:
   ```sql
   CREATE DATABASE court_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'court_user'@'localhost' IDENTIFIED BY 'secure_password';
   GRANT ALL PRIVILEGES ON court_management.* TO 'court_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Update Configuration**:
   Edit `config/config.php` and update database settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'court_management');
   define('DB_USER', 'court_user');
   define('DB_PASS', 'secure_password');
   ```

### Step 4: Run Installation
```bash
# Run the database installation script
php database/install.php

# Or access via web browser:
# http://your-domain.com/court-system/database/install.php
```

### Step 5: Web Server Configuration

#### Apache Configuration
The `.htaccess` file is already included. Ensure `mod_rewrite` is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx Configuration
Add this to your Nginx server block:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/court-system;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # Protect sensitive files
    location ~ /\.(htaccess|env) {
        deny all;
    }
    
    location ~ ^/(config|includes|database)/ {
        deny all;
    }
}
```

### Step 6: SSL Configuration (Production)
```bash
# Install SSL certificate (Let's Encrypt example)
sudo certbot --nginx -d your-domain.com

# Or configure your SSL certificate manually
```

### Step 7: Final Security Setup
1. **Change Default Credentials**:
   - Login: `admin@court.example.com`
   - Password: `admin123`
   - **⚠️ CHANGE IMMEDIATELY AFTER FIRST LOGIN**

2. **Update Configuration**:
   ```php
   // In config/config.php - Update for production
   error_reporting(0);
   ini_set('display_errors', 0);
   
   // Set strong session settings
   ini_set('session.cookie_secure', 1);
   ini_set('session.cookie_httponly', 1);
   ```

3. **Set Up Backups**:
   ```bash
   # Create backup script
   #!/bin/bash
   mysqldump -u court_user -p court_management > backup_$(date +%Y%m%d).sql
   tar -czf files_backup_$(date +%Y%m%d).tar.gz uploads/
   ```

## 🧪 Testing Installation

### 1. Access the System
- URL: `http://your-domain.com/court-system/`
- Should redirect to login page

### 2. Login Test
- Email: `admin@court.example.com`
- Password: `admin123`
- Should redirect to admin dashboard

### 3. Feature Tests
- ✅ Create a test case
- ✅ Upload a test document
- ✅ Schedule a test hearing
- ✅ Generate a test report

## 🔧 Configuration Options

### Email Configuration
Update in `config/config.php`:
```php
define('SMTP_HOST', 'your-smtp-server.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@domain.com');
define('SMTP_PASSWORD', 'your-email-password');
```

### File Upload Limits
```php
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_FILE_TYPES', ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png', 'txt']);
```

### Court-Specific Settings
```php
define('CASE_NUMBER_PREFIX', 'COURT');
define('COURT_ROOMS', [
    'Courtroom 1',
    'Courtroom 2',
    'Virtual Room A'
]);
```

## 🚨 Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Check database credentials in `config/config.php`
   - Ensure MySQL service is running
   - Verify user permissions

2. **File Upload Issues**:
   - Check folder permissions (755 for directories)
   - Verify PHP upload limits
   - Ensure `uploads/` directory exists

3. **Login Issues**:
   - Clear browser cache and cookies
   - Check session configuration
   - Verify database contains default admin user

4. **Permission Errors**:
   - Check file/folder permissions
   - Verify web server user ownership
   - Review `.htaccess` configuration

### Log Files
Check these locations for error logs:
- `/var/log/apache2/error.log` (Apache)
- `/var/log/nginx/error.log` (Nginx)
- PHP error logs (location varies)

## 📞 Support

### Documentation
- System documentation: `README.md`
- File manifest: `FILE_MANIFEST.md`
- API documentation: `/docs/api.md` (if available)

### Getting Help
1. Check the troubleshooting section above
2. Review system logs for error messages
3. Verify all requirements are met
4. Contact system administrator

## 🔄 Updates and Maintenance

### Regular Maintenance
- **Daily**: Check system logs
- **Weekly**: Database backup
- **Monthly**: Update dependencies
- **Quarterly**: Security review

### Update Process
1. Backup database and files
2. Download new version
3. Update files (preserve config)
4. Run update scripts
5. Test functionality

## 📊 Performance Optimization

### Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_cases_search ON cases(status, case_type, filing_date);
CREATE INDEX idx_documents_case ON documents(case_id, is_active);
CREATE INDEX idx_hearings_date ON hearings(hearing_date, status);
```

### PHP Optimization
```php
// Enable OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

---

## ✅ Installation Checklist

- [ ] System requirements verified
- [ ] Files extracted and permissions set
- [ ] Database created and configured
- [ ] Installation script executed successfully
- [ ] Web server configured
- [ ] SSL certificate installed (production)
- [ ] Default credentials changed
- [ ] System tested and functional
- [ ] Backup strategy implemented
- [ ] Documentation reviewed

**🎉 Installation Complete!**

Your Digital Court Case Management System is now ready for use.

---

**📝 Installation Guide**  
**Author**: Court System Development Team  
**Version**: 1.0.0  
**License**: MIT  
**Support**: Contact system administrator