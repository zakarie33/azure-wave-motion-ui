-- Digital Court Case Management System Database Schema
-- Created: 2025-10-14

CREATE DATABASE IF NOT EXISTS court_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE court_management;

-- Users table for authentication and role management
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'judge', 'clerk', 'prosecutor') NOT NULL,
    department VARCHAR(100),
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    failed_login_attempts INT DEFAULT 0,
    lockout_until DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
);

-- Cases table for storing case information
CREATE TABLE cases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_no VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    case_type ENUM('Criminal', 'Civil', 'Family', 'Appeal', 'Administrative') NOT NULL,
    filing_date DATE NOT NULL,
    status ENUM('Filed', 'Pending', 'In Hearing', 'Judged', 'Closed', 'Dismissed') DEFAULT 'Filed',
    priority ENUM('High', 'Normal', 'Low') DEFAULT 'Normal',
    plaintiff VARCHAR(255),
    defendant VARCHAR(255),
    assigned_judge_id INT NULL,
    confidential TINYINT(1) DEFAULT 0,
    tags TEXT,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_judge_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_case_no (case_no),
    INDEX idx_case_type (case_type),
    INDEX idx_status (status),
    INDEX idx_assigned_judge (assigned_judge_id),
    INDEX idx_filing_date (filing_date),
    INDEX idx_confidential (confidential)
);

-- Documents table for case file management
CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    document_type ENUM('Filing', 'Evidence', 'Motion', 'Judgment', 'Other') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100),
    file_size INT,
    file_hash VARCHAR(64),
    visibility ENUM('public', 'case_staff', 'judge_only') DEFAULT 'case_staff',
    signed_by VARCHAR(255),
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_case_id (case_id),
    INDEX idx_document_type (document_type),
    INDEX idx_visibility (visibility),
    INDEX idx_uploaded_at (uploaded_at)
);

-- Hearings table for scheduling and agenda management
CREATE TABLE hearings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id INT NOT NULL,
    hearing_date DATETIME NOT NULL,
    hearing_duration INT DEFAULT 60, -- minutes
    court_room VARCHAR(50) NOT NULL,
    hearing_type ENUM('Pre-trial', 'Trial', 'Sentencing', 'Motion Hearing', 'Other') NOT NULL,
    status ENUM('Scheduled', 'Rescheduled', 'Cancelled', 'Completed', 'In Progress') DEFAULT 'Scheduled',
    notes TEXT,
    participants TEXT, -- JSON array of participant IDs
    scheduled_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (scheduled_by) REFERENCES users(id),
    INDEX idx_case_id (case_id),
    INDEX idx_hearing_date (hearing_date),
    INDEX idx_court_room (court_room),
    INDEX idx_status (status)
);

-- Judgments table for storing judge decisions
CREATE TABLE judgments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id INT NOT NULL,
    judge_id INT NOT NULL,
    judgment_date DATE NOT NULL,
    outcome ENUM('Guilty', 'Not Guilty', 'Dismissed', 'Settled', 'Other') NOT NULL,
    judgment_text TEXT NOT NULL,
    orders TEXT,
    attached_document VARCHAR(500),
    publish_public TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (judge_id) REFERENCES users(id),
    INDEX idx_case_id (case_id),
    INDEX idx_judge_id (judge_id),
    INDEX idx_judgment_date (judgment_date),
    INDEX idx_outcome (outcome)
);

-- Notifications table for system messaging
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_id INT NOT NULL,
    sender_id INT,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    case_id INT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    sent_via SET('email', 'sms', 'in_app') DEFAULT 'in_app',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE SET NULL,
    INDEX idx_recipient (recipient_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Audit logs table for system activity tracking
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
);

-- Court rooms table for hearing scheduling
CREATE TABLE court_rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(20) NOT NULL,
    room_name VARCHAR(100),
    capacity INT,
    equipment TEXT, -- JSON array of available equipment
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_number (room_number),
    INDEX idx_is_active (is_active)
);

-- Document transfer requests table
CREATE TABLE document_transfer_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    requestor_id INT NOT NULL,
    case_id INT NOT NULL,
    documents_requested TEXT, -- JSON array of document IDs
    reason TEXT NOT NULL,
    due_by DATETIME,
    status ENUM('pending', 'approved', 'delivered', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at DATETIME NULL,
    delivered_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requestor_id) REFERENCES users(id),
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_requestor (requestor_id),
    INDEX idx_case_id (case_id),
    INDEX idx_status (status)
);

-- System settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_by INT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
);

-- Case notes table for private notes
CREATE TABLE case_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id INT NOT NULL,
    user_id INT NOT NULL,
    note_text TEXT NOT NULL,
    is_private TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_case_id (case_id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_private (is_private)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, password_hash, role, department) VALUES 
('System', 'Administrator', 'admin@court.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'IT Department');

-- Insert sample court rooms
INSERT INTO court_rooms (room_number, room_name, capacity, equipment) VALUES 
('CR-001', 'Main Court Room', 100, '["projector", "microphone", "recording_system"]'),
('CR-002', 'Family Court', 50, '["projector", "microphone"]'),
('CR-003', 'Small Claims Court', 30, '["microphone"]');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES 
('site_name', 'Digital Court Management System', 'Application name'),
('timezone', 'UTC', 'System timezone'),
('max_upload_size', '20971520', 'Maximum file upload size in bytes'),
('notification_lead_times', '24,2', 'Notification lead times in hours (comma separated)'),
('email_notifications', '1', 'Enable email notifications'),
('sms_notifications', '0', 'Enable SMS notifications');