-- Notary Management System Database Schema
-- Create Database
CREATE DATABASE IF NOT EXISTS notary_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE notary_management;

-- Users table (Admin, Notary, Client roles)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'notary', 'client') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_email (email)
);

-- Clients table (Extended client information)
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    address VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(50),
    zip_code VARCHAR(10),
    date_of_birth DATE,
    identification_type VARCHAR(50),
    identification_number VARCHAR(100),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- Notaries table (Extended notary information)
CREATE TABLE notaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    license_number VARCHAR(100) UNIQUE NOT NULL,
    license_expiry DATE NOT NULL,
    commission_state VARCHAR(50) NOT NULL,
    bond_amount DECIMAL(10,2),
    insurance_provider VARCHAR(100),
    specializations TEXT,
    hourly_rate DECIMAL(8,2),
    availability_hours JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_license (license_number)
);

-- Appointments table
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    notary_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    duration_minutes INT DEFAULT 60,
    location VARCHAR(255),
    appointment_type VARCHAR(100),
    purpose TEXT,
    status ENUM('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    fee DECIMAL(8,2),
    notes TEXT,
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (notary_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_notary (notary_id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status)
);

-- Documents table
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    client_id INT NOT NULL,
    notary_id INT,
    document_name VARCHAR(255) NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notarized_date TIMESTAMP NULL,
    status ENUM('uploaded', 'pending_notarization', 'notarized', 'rejected') DEFAULT 'uploaded',
    document_hash VARCHAR(64),
    notes TEXT,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (notary_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_notary (notary_id),
    INDEX idx_appointment (appointment_id),
    INDEX idx_status (status)
);

-- Notarization logs table
CREATE TABLE notarization_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    appointment_id INT,
    notary_id INT NOT NULL,
    client_id INT NOT NULL,
    notarization_type VARCHAR(100) NOT NULL,
    notarization_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    witness_present BOOLEAN DEFAULT FALSE,
    witness_name VARCHAR(100),
    witness_contact VARCHAR(100),
    identification_verified BOOLEAN DEFAULT FALSE,
    signature_method ENUM('in_person', 'electronic', 'remote') DEFAULT 'in_person',
    signature_path VARCHAR(500),
    seal_applied BOOLEAN DEFAULT FALSE,
    fee_charged DECIMAL(8,2),
    additional_notes TEXT,
    journal_entry_number VARCHAR(50),
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (notary_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_document (document_id),
    INDEX idx_notary (notary_id),
    INDEX idx_client (client_id),
    INDEX idx_date (notarization_date)
);

-- Activity logs table (Audit trail)
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_table (table_name),
    INDEX idx_created (created_at)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    related_type VARCHAR(50),
    related_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
);

-- System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, first_name, last_name, role) VALUES 
('admin', 'admin@notary.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin');

-- Insert sample notary (password: notary123)
INSERT INTO users (username, email, password, first_name, last_name, phone, role) VALUES 
('notary1', 'notary@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', '555-0123', 'notary');

-- Insert sample client (password: client123)
INSERT INTO users (username, email, password, first_name, last_name, phone, role) VALUES 
('client1', 'client@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Doe', '555-0456', 'client');

-- Insert notary details
INSERT INTO notaries (user_id, license_number, license_expiry, commission_state, bond_amount, insurance_provider) VALUES 
(2, 'NOT123456', '2025-12-31', 'California', 15000.00, 'Notary Insurance Co.');

-- Insert client details
INSERT INTO clients (user_id, address, city, state, zip_code, identification_type, identification_number) VALUES 
(3, '123 Main St', 'Los Angeles', 'CA', '90210', 'Driver License', 'DL123456789');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES 
('site_name', 'Notary Management System', 'Name of the application'),
('timezone', 'America/Los_Angeles', 'Default system timezone'),
('appointment_duration', '60', 'Default appointment duration in minutes'),
('max_file_size', '10485760', 'Maximum file upload size in bytes (10MB)'),
('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png', 'Allowed file types for uploads'),
('email_notifications', '1', 'Enable email notifications (1=enabled, 0=disabled)'),
('session_timeout', '3600', 'Session timeout in seconds'),
('password_min_length', '8', 'Minimum password length requirement');