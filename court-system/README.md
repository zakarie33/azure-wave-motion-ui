# 🏛️ Digital Court Case Management and Automation System

A comprehensive web-based court management system designed to modernize court operations, eliminate paper-based processes, and provide real-time case tracking and analytics.

## 🎯 System Overview

This system addresses the challenges of traditional court operations by providing:
- **Paperless Operations**: Digital case filing, document management, and hearing scheduling
- **Role-Based Access Control**: Secure access for Judges, Clerks, Prosecutors, and Administrators
- **Real-Time Analytics**: Dashboard with case statistics and performance metrics
- **Automated Workflows**: Case number generation, notification system, and hearing reminders
- **Audit Trail**: Complete logging of all system activities for transparency and accountability

## 👥 User Roles & Permissions

| Role | Description | Key Permissions |
|------|-------------|----------------|
| **🧑‍⚖️ Judge** | Reviews and decides cases | View assigned cases, add judgments, update case status |
| **👩‍💼 Court Clerk** | Registers cases, manages documents | Create/edit cases, upload documents, schedule hearings |
| **👨‍💼 Prosecutor/Defender** | Participates in court sessions | View assigned cases, upload legal documents |
| **🏢 Court Manager/Admin** | Oversees court operations | Full system access, generate reports, manage users |
| **👨‍💻 System Administrator** | Maintains the system | System configuration, user management, audit logs |

## 🚀 Key Features

### 📁 Case Management
- **Digital Case Registration**: Automated case number generation with configurable format
- **Case Assignment**: Assign cases to judges and departments
- **Case Tracking**: Real-time status updates and progress monitoring
- **Search & Filtering**: Advanced search across cases, parties, and case types
- **Case Notes**: Internal notes with visibility controls

### 📄 Document Management
- **Secure Upload**: Support for PDF, Word documents, and images (up to 20MB)
- **Access Control**: Document visibility settings (Public, Staff Only, Judge Only, Admin Only)
- **Version Control**: Track document versions and changes
- **Digital Signatures**: Record signed document information
- **File Integrity**: SHA-256 hashing for document verification

### 📅 Hearing Management
- **Automated Scheduling**: Digital hearing calendar with conflict detection
- **Court Room Management**: Assign hearings to physical or virtual court rooms
- **Participant Notifications**: Email and SMS reminders for upcoming hearings
- **Daily Agenda**: FIDS-style display for court schedules
- **Hearing Outcomes**: Record hearing results and next steps

### ⚖️ Judgment System
- **Digital Judgments**: Rich text editor for judgment entry
- **Outcome Tracking**: Record case outcomes and orders
- **Public Publishing**: Option to publish redacted summaries
- **Appeal Deadlines**: Track appeal periods and deadlines

### 📊 Reporting & Analytics
- **Real-Time Dashboards**: Role-specific dashboards with key metrics
- **Case Statistics**: Cases by status, type, judge, and department
- **Performance Metrics**: Case resolution times and workload distribution
- **Export Options**: PDF and Excel export for all reports
- **Custom Date Ranges**: Flexible reporting periods

### 🔔 Notification System
- **Multi-Channel Notifications**: In-app, email, and SMS notifications
- **Automated Reminders**: Hearing reminders at configurable intervals
- **Case Updates**: Notifications for case assignments and status changes
- **Document Alerts**: Notifications for new document uploads

### 🔒 Security & Audit
- **Role-Based Access Control (RBAC)**: Granular permission system
- **Session Management**: Secure session handling with timeout
- **Password Security**: Argon2 password hashing
- **Audit Logging**: Complete activity logging with user tracking
- **Data Encryption**: Secure file storage and transmission

## 🛠️ Technical Specifications

### Backend
- **Language**: PHP 8+ (Object-Oriented)
- **Database**: MySQL 8.0+ with InnoDB engine
- **Architecture**: MVC (Model-View-Controller) pattern
- **Security**: Prepared statements, CSRF protection, input sanitization

### Frontend
- **Framework**: Bootstrap 5 for responsive design
- **JavaScript**: Vanilla JS with Chart.js for analytics
- **Icons**: Bootstrap Icons
- **Styling**: Custom CSS with CSS Grid and Flexbox

### Database Schema
- **Users & Roles**: User management with role-based permissions
- **Cases**: Complete case lifecycle management
- **Documents**: Secure document storage with metadata
- **Hearings**: Scheduling and calendar management
- **Judgments**: Decision tracking and outcomes
- **Audit Logs**: Complete activity tracking
- **Notifications**: Multi-channel messaging system

## 📋 Installation Guide

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- Composer (for dependency management)

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone <repository-url>
   cd court-system
   ```

2. **Configure Database**
   - Update database credentials in `config/config.php`
   - Run the installation script:
   ```bash
   php database/install.php
   ```

3. **Set File Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/documents/
   chmod 755 uploads/temp/
   ```

4. **Configure Web Server**
   - Point document root to the `court-system` directory
   - Ensure URL rewriting is enabled
   - Configure HTTPS for production

5. **Default Login**
   - **Email**: admin@court.example.com
   - **Password**: admin123
   - **⚠️ Change default credentials immediately after first login**

### Configuration Options

Edit `config/config.php` to customize:
- Database connection settings
- File upload limits and allowed types
- Email/SMS notification settings
- Session timeout and security settings
- Court-specific settings (case number format, court rooms, etc.)

## 📱 User Interface

### Dashboard Features
- **Role-Specific Dashboards**: Customized views for each user role
- **Real-Time Statistics**: Live case counts and status distribution
- **Quick Actions**: Fast access to common tasks
- **Recent Activity**: Timeline of recent system activities
- **Upcoming Events**: Hearing schedules and deadlines

### Responsive Design
- **Mobile-Friendly**: Optimized for tablets and smartphones
- **Print-Friendly**: Clean print layouts for documents and reports
- **Accessibility**: WCAG 2.1 compliant with keyboard navigation
- **Dark Mode**: Automatic dark mode support

## 🔧 API Endpoints

The system includes RESTful API endpoints for:
- Case management operations
- Document upload and retrieval
- Notification management
- User authentication
- Reporting and analytics

## 📈 Performance Features

- **Optimized Queries**: Indexed database queries for fast searches
- **Pagination**: Efficient data loading with pagination
- **Caching**: Strategic caching for improved performance
- **File Optimization**: Compressed assets and optimized images
- **Database Optimization**: Proper indexing and query optimization

## 🔐 Security Measures

- **Input Validation**: Server-side validation for all inputs
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Output encoding and CSP headers
- **CSRF Protection**: Token-based CSRF prevention
- **File Upload Security**: Type validation and secure storage
- **Session Security**: Secure session configuration
- **Password Policy**: Enforced strong password requirements

## 📊 Monitoring & Maintenance

### Audit Logging
- All user actions are logged with timestamps
- IP address and user agent tracking
- Database change tracking
- File access logging

### System Health
- Database connection monitoring
- File system health checks
- User session monitoring
- Error logging and reporting

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Check the documentation in the `docs/` directory
- Review the FAQ section
- Submit issues via the issue tracker
- Contact the development team

## 🔄 Version History

- **v1.0.0** - Initial release with core functionality
- **v1.1.0** - Enhanced reporting and analytics
- **v1.2.0** - Mobile optimization and accessibility improvements
- **v2.0.0** - API integration and advanced workflow features

## 🎯 Future Enhancements

- **AI-Powered Features**: Document analysis and case prediction
- **Mobile Apps**: Native iOS and Android applications
- **Integration APIs**: Connect with external legal databases
- **Advanced Analytics**: Machine learning insights
- **Voice Recognition**: Voice-to-text for hearing transcription
- **Blockchain**: Immutable record keeping for critical documents

---

**Built with ❤️ for the justice system**

*Modernizing courts, one case at a time.*