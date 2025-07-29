# Notary Management System

A comprehensive web-based Notary Management System built with PHP and MySQL that allows notaries to manage their clients, appointments, notarized documents, and user activity efficiently.

## 🚀 Features

### 👥 User Management
- **Three User Roles**: Admin, Notary, and Client
- **Secure Authentication**: Password hashing, session management, and role-based access control
- **User Profiles**: Comprehensive user information management

### 🔐 Security Features
- Secure login/logout system
- Password encryption using PHP's password_hash()
- Session timeout management
- Activity logging and audit trails
- Role-based access control

### 👨‍💼 Admin Features
- **System Dashboard**: Overview of all system activities
- **User Management**: Add, edit, view, and delete users
- **System Reports**: Generate comprehensive reports
- **Activity Monitoring**: Real-time system activity tracking
- **System Settings**: Configure application settings

### 📋 Notary Features
- **Personal Dashboard**: Overview of appointments and documents
- **Client Management**: Manage client information and history
- **Appointment Scheduling**: Create and manage appointments
- **Document Management**: Upload, view, and manage documents
- **Notarization Logs**: Record and track notarization activities
- **Calendar View**: Visual appointment scheduling
- **Earnings Tracking**: Monitor income and fees

### 👤 Client Features
- **Personal Dashboard**: View appointments and documents
- **Appointment Booking**: Schedule appointments with notaries
- **Document Upload**: Upload documents for notarization
- **Notary Search**: Find and contact available notaries
- **History Tracking**: View past appointments and documents
- **Service Selection**: Choose from various notary services

### 📄 Document Management
- **File Upload**: Support for PDF, DOC, DOCX, JPG, JPEG, PNG
- **Document Tracking**: Track document status and history
- **Secure Storage**: Organized file storage system
- **Document Assignment**: Link documents to appointments and clients

### 📅 Appointment System
- **Flexible Scheduling**: Date and time selection
- **Status Management**: Track appointment progress
- **Email Notifications**: Automated appointment reminders
- **Calendar Integration**: Visual appointment management

### 📊 Reporting & Analytics
- **Activity Reports**: System usage and user activity
- **Appointment Reports**: Scheduling and completion statistics
- **Revenue Reports**: Earnings and fee tracking
- **User Statistics**: Registration and activity metrics

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5.1.3
- **Icons**: Font Awesome 6.0.0
- **Security**: PDO with prepared statements

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PHP Extensions:
  - PDO
  - PDO_MySQL
  - Session
  - JSON
  - OpenSSL (recommended)

## 🚀 Installation

### 1. Download and Extract
```bash
# Download the project files
# Extract to your web server directory
# Example: /var/www/html/notary-management-system/
```

### 2. Database Setup
```bash
# Create database
mysql -u root -p

# Import the database schema
mysql -u root -p < config/database.sql
```

### 3. Configuration
Edit `config/config.php` with your settings:
```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'notary_management');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Application Configuration
define('BASE_URL', 'http://your-domain.com/notary-management-system/');
```

### 4. File Permissions
```bash
# Set write permissions for upload directories
chmod 755 uploads/
chmod 755 uploads/documents/
chmod 755 uploads/signatures/
```

### 5. Web Server Configuration

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
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## 👤 Default User Accounts

The system comes with pre-configured demo accounts:

### Admin Account
- **Username**: `admin`
- **Password**: `admin123`
- **Email**: `admin@notary.com`

### Notary Account
- **Username**: `notary1`
- **Password**: `notary123`
- **Email**: `notary@example.com`

### Client Account
- **Username**: `client1`
- **Password**: `client123`
- **Email**: `client@example.com`

> **⚠️ Important**: Change these default passwords immediately after installation!

## 📖 Usage Guide

### For Administrators
1. Login with admin credentials
2. Access the admin dashboard
3. Manage users, view reports, and configure system settings
4. Monitor system activity through activity logs

### For Notaries
1. Login with notary credentials
2. Update your profile and license information
3. Manage client information
4. Schedule and track appointments
5. Upload and manage documents
6. Record notarization activities

### For Clients
1. Login with client credentials
2. Complete your profile information
3. Search and book appointments with notaries
4. Upload documents for notarization
5. Track appointment and document status

## 🔧 Customization

### Adding New User Roles
1. Update the users table ENUM values
2. Modify the Auth class role validation
3. Create new dashboard files
4. Update navigation menus

### Custom Email Templates
Edit email templates in the `includes/` directory and configure SMTP settings in `config/config.php`.

### Theme Customization
Modify `assets/css/style.css` to change colors, fonts, and layout.

## 📁 Project Structure

```
notary-management-system/
├── admin/              # Admin panel files
├── notary/            # Notary dashboard files
├── client/            # Client dashboard files
├── assets/
│   ├── css/           # Stylesheets
│   ├── js/            # JavaScript files
│   └── images/        # Image assets
├── config/
│   ├── config.php     # Main configuration
│   └── database.sql   # Database schema
├── includes/
│   ├── Auth.php       # Authentication class
│   ├── Database.php   # Database connection
│   ├── ActivityLogger.php # Activity logging
│   └── models/        # Data models
├── uploads/
│   ├── documents/     # Uploaded documents
│   └── signatures/    # Digital signatures
├── index.php          # Main entry point
├── login.php          # Login page
└── logout.php         # Logout handler
```

## 🔒 Security Considerations

### Password Security
- All passwords are hashed using PHP's `password_hash()`
- Minimum password length requirements
- Password complexity recommendations

### Session Security
- Secure session configuration
- Session timeout management
- Session hijacking protection

### File Upload Security
- File type validation
- File size limitations
- Secure file storage

### Database Security
- Prepared statements for all queries
- Input validation and sanitization
- SQL injection prevention

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
- Check database credentials in `config/config.php`
- Ensure MySQL service is running
- Verify database exists and user has permissions

**File Upload Issues**
- Check PHP upload limits (`upload_max_filesize`, `post_max_size`)
- Verify directory permissions (755 for upload directories)
- Check available disk space

**Session Issues**
- Ensure session directory is writable
- Check PHP session configuration
- Verify cookie settings

**Permission Denied**
- Check file and directory permissions
- Ensure web server has read/write access
- Verify SELinux settings (if applicable)

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

## 📧 Support

For support and questions:
- Create an issue on GitHub
- Check the documentation
- Review the troubleshooting section

## 🏗️ Development Roadmap

- [ ] Email notification system
- [ ] Digital signature integration
- [ ] Mobile app development
- [ ] API development
- [ ] Multi-language support
- [ ] Advanced reporting features
- [ ] Calendar integration (Google Calendar, Outlook)

## 📊 Version History

### v1.0.0 (Current)
- Initial release
- Complete user management system
- Appointment scheduling
- Document management
- Activity logging
- Responsive design
- Security features

---

**Built with ❤️ for the notary community**