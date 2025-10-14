# 🏛️ Digital Court Case Management and Automation System

A comprehensive web-based court management system that digitizes and automates case registration, tracking, documentation, and reporting processes. Built with PHP, MySQL, and Bootstrap 5.

## 🎯 System Overview

This system modernizes court operations by replacing manual paperwork with a secure, centralized digital platform that provides:

- **Paperless Operations**: Complete digitization of case files and documents
- **Role-Based Access Control**: Secure access for Judges, Clerks, Prosecutors, and Administrators
- **Real-Time Dashboards**: Live monitoring of court activities and performance
- **Automated Workflows**: Streamlined case registration, hearing scheduling, and notifications
- **Comprehensive Reporting**: Daily, weekly, and monthly analytics with export capabilities

## 👥 User Roles & Permissions

| Role | Description | Key Permissions |
|------|-------------|----------------|
| 🧑‍⚖️ **Judge** | Reviews and decides cases | View assigned cases, add judgments, update case status |
| 👩‍💼 **Court Clerk** | Registers cases, manages documents | Create/edit cases, upload documents, schedule hearings |
| 👨‍💼 **Prosecutor/Defender** | Participates in proceedings | View assigned cases, upload legal arguments |
| 🏢 **Court Manager** | Oversees court operations | View all cases, generate reports, monitor performance |
| 👨‍💻 **System Administrator** | Maintains the system | Manage users, system settings, audit logs |

## 🚀 Key Features

### 📋 Case Management
- **Digital Case Registration**: Automated case numbering (COURT-YYYY-####)
- **Case Types**: Criminal, Civil, Family, Appeal, Administrative
- **Status Tracking**: Filed → Pending → In Hearing → Judged → Closed
- **Priority Levels**: High, Normal, Low
- **Confidential Cases**: Restricted access for sensitive matters
- **Case Notes**: Private and public annotations

### 📄 Document Management
- **Secure File Storage**: Encrypted document storage with access controls
- **File Types**: PDF, DOCX, DOC, JPG, PNG (up to 20MB per file)
- **Document Categories**: Filing, Evidence, Motion, Judgment, Other
- **Visibility Controls**: Public, Case Staff, Judge-Only
- **Version Control**: Document history and audit trail
- **Thumbnail Generation**: Preview for images and PDFs

### 📅 Hearing & Agenda Management
- **Digital Scheduling**: Replace handwritten agendas
- **Court Room Management**: Resource allocation and conflict prevention
- **Automated Notifications**: Email/SMS reminders 24h and 2h before hearings
- **FIDS-Style Display**: Public hearing boards with real-time updates
- **Judge's Personal Calendar**: Individual hearing schedules

### 📊 Reporting & Analytics
- **Dashboard Widgets**: Real-time case statistics
- **Performance Metrics**: Cases by judge, type, status, and timeline
- **Export Options**: PDF and Excel reports
- **Chart Visualizations**: Status distribution and trend analysis
- **Custom Date Ranges**: Daily, weekly, monthly, and custom reports

### 🔔 Notification System
- **Multi-Channel Alerts**: In-app, email, and SMS notifications
- **Event Triggers**: Case assignments, document uploads, hearing reminders
- **User Preferences**: Customizable notification settings
- **Audit Trail**: Complete activity logging

## 🛠️ Technical Specifications

### Backend
- **Language**: PHP 8+ (Object-Oriented)
- **Framework**: Custom MVC Architecture
- **Database**: MySQL 8.0+ with InnoDB engine
- **Security**: RBAC, CSRF protection, SQL injection prevention
- **File Storage**: Local filesystem with hash verification

### Frontend
- **UI Framework**: Bootstrap 5.3
- **Icons**: Bootstrap Icons
- **Charts**: Chart.js for data visualization
- **JavaScript**: Vanilla JS with AJAX for dynamic content
- **Responsive Design**: Mobile-friendly interface

### Security Features
- **Authentication**: Secure login with rate limiting
- **Session Management**: Timeout and token-based security
- **Password Hashing**: bcrypt encryption
- **File Validation**: Type and size restrictions
- **Audit Logging**: Complete user activity tracking
- **Data Encryption**: Sensitive data protection

## 📦 Installation Guide

### Prerequisites
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 8.0 or higher
- **MySQL**: Version 8.0 or higher
- **Extensions**: PDO, GD, mbstring, openssl, fileinfo

### Step 1: Download and Setup
```bash
# Clone or download the system files
git clone <repository-url> court-management
cd court-management

# Set proper permissions
chmod -R 755 .
chmod -R 777 uploads/
```

### Step 2: Database Setup
```sql
-- Create database and import schema
CREATE DATABASE court_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import the database schema
mysql -u root -p court_management < database/schema.sql
```

### Step 3: Configuration
```php
// Edit config/database.php
private $host = 'localhost';        // Your database host
private $db_name = 'court_management'; // Database name
private $username = 'your_username';   // Database username
private $password = 'your_password';   // Database password
```

### Step 4: Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/court-management;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Step 5: Initial Setup
1. **Access the system**: Navigate to your domain/installation
2. **Default Admin Login**:
   - Email: `admin@court.gov`
   - Password: `admin123`
3. **Change default password** immediately after first login
4. **Create user accounts** for your staff
5. **Configure system settings** in the admin panel

## 📁 Directory Structure

```
court-management/
├── config/                 # Configuration files
│   ├── config.php         # Main configuration
│   └── database.php       # Database connection
├── controllers/           # MVC Controllers
│   ├── AuthController.php
│   ├── CaseController.php
│   └── ...
├── models/               # Data Models
│   ├── User.php
│   ├── Case.php
│   ├── Document.php
│   └── ...
├── views/                # UI Templates
│   ├── layouts/          # Header/Footer layouts
│   ├── auth/            # Authentication views
│   ├── cases/           # Case management views
│   └── ...
├── middleware/           # Security middleware
│   └── AuthMiddleware.php
├── uploads/             # Document storage
│   └── documents/       # Organized by date
├── assets/              # Static assets
│   ├── css/
│   ├── js/
│   └── images/
├── database/            # Database files
│   └── schema.sql       # Database schema
├── api/                 # AJAX endpoints
└── README.md           # This file
```

## 🔧 Configuration Options

### System Settings
```php
// File Upload Limits
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20MB
define('ALLOWED_FILE_TYPES', ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png']);

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Email Configuration
define('SMTP_HOST', 'your-smtp-server.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@domain.com');
define('SMTP_PASSWORD', 'your-email-password');
```

## 📱 Usage Guide

### For Court Clerks
1. **Register New Cases**: Use the "New Case" form with all required details
2. **Upload Documents**: Attach evidence, filings, and other case materials
3. **Schedule Hearings**: Set dates, times, and court rooms
4. **Manage Case Status**: Update progress as cases move through the system

### For Judges
1. **Review Assigned Cases**: Access your personal case dashboard
2. **View Case Files**: Examine all documents and evidence digitally
3. **Add Judgments**: Record decisions and orders
4. **Check Daily Agenda**: View scheduled hearings and appointments

### For Court Managers
1. **Monitor Performance**: Use dashboard analytics and reports
2. **Generate Reports**: Export daily, weekly, and monthly statistics
3. **Oversee Operations**: Track case volumes and processing times
4. **Manage Resources**: Allocate judges and court rooms efficiently

## 🔒 Security Best Practices

### For Administrators
- Change default passwords immediately
- Enable HTTPS in production
- Regular database backups
- Monitor audit logs for suspicious activity
- Keep PHP and MySQL updated
- Implement firewall rules

### For Users
- Use strong, unique passwords
- Log out when finished
- Report suspicious activity
- Don't share login credentials
- Verify document authenticity

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
```
Solution: Check database credentials in config/database.php
Verify MySQL service is running
```

**File Upload Fails**
```
Solution: Check upload directory permissions (777)
Verify PHP upload_max_filesize setting
```

**Session Timeout Issues**
```
Solution: Adjust SESSION_TIMEOUT in config.php
Check PHP session.gc_maxlifetime setting
```

**Permission Denied Errors**
```
Solution: Verify user roles in database
Check AuthMiddleware permissions
```

## 📈 Performance Optimization

### Database Optimization
- Regular OPTIMIZE TABLE commands
- Index frequently queried columns
- Archive old cases periodically
- Monitor slow query log

### File Storage
- Implement file compression
- Use CDN for static assets
- Regular cleanup of temporary files
- Consider cloud storage for large deployments

## 🔄 Backup & Recovery

### Automated Backup Script
```bash
#!/bin/bash
# Daily backup script
DATE=$(date +%Y%m%d)
mysqldump -u username -p court_management > backup_$DATE.sql
tar -czf files_backup_$DATE.tar.gz uploads/
```

### Recovery Process
1. Restore database from backup
2. Restore uploaded files
3. Verify system functionality
4. Update file paths if necessary

## 🆘 Support & Maintenance

### Regular Maintenance Tasks
- [ ] Weekly database optimization
- [ ] Monthly log file cleanup
- [ ] Quarterly security updates
- [ ] Annual system backup verification

### Getting Help
- Check the troubleshooting section
- Review audit logs for error details
- Contact system administrator
- Consult PHP/MySQL documentation

## 📄 License

This Digital Court Case Management System is proprietary software developed for court administration. Unauthorized copying, distribution, or modification is prohibited.

## 🤝 Contributing

This system is designed for court environments. Contributions should focus on:
- Security enhancements
- Performance improvements
- Accessibility features
- Additional reporting capabilities

---

**Version**: 1.0.0  
**Last Updated**: October 2024  
**Minimum Requirements**: PHP 8.0+, MySQL 8.0+, 2GB RAM, 10GB Storage

For technical support or feature requests, please contact your system administrator.