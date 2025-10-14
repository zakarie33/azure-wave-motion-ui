-- Digital Court Case Management System Database Schema
-- MySQL 8.0+ Compatible

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `judgments`;
DROP TABLE IF EXISTS `hearings`;
DROP TABLE IF EXISTS `documents`;
DROP TABLE IF EXISTS `case_participants`;
DROP TABLE IF EXISTS `cases`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- Users table - stores all system users
CREATE TABLE `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'manager', 'judge', 'clerk', 'prosecutor') NOT NULL,
    `department_id` INT NULL,
    `phone` VARCHAR(20) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `email_verified` TINYINT(1) DEFAULT 0,
    `last_login` DATETIME NULL,
    `failed_login_attempts` INT DEFAULT 0,
    `locked_until` DATETIME NULL,
    `password_reset_token` VARCHAR(255) NULL,
    `password_reset_expires` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Departments table
CREATE TABLE `departments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `head_judge_id` INT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cases table - main case information
CREATE TABLE `cases` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `case_no` VARCHAR(50) UNIQUE NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `description` TEXT NOT NULL,
    `case_type` VARCHAR(50) NOT NULL,
    `filing_date` DATE NOT NULL,
    `status` VARCHAR(50) DEFAULT 'Filed',
    `priority` ENUM('High', 'Normal', 'Low') DEFAULT 'Normal',
    `assigned_judge_id` INT NULL,
    `department_id` INT NULL,
    `plaintiff` VARCHAR(255) NOT NULL,
    `defendant` VARCHAR(255) NOT NULL,
    `confidential` TINYINT(1) DEFAULT 0,
    `tags` TEXT NULL,
    `next_hearing_date` DATETIME NULL,
    `estimated_duration` INT NULL COMMENT 'in minutes',
    `created_by` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `closed_at` DATETIME NULL,
    FOREIGN KEY (`assigned_judge_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_case_no` (`case_no`),
    INDEX `idx_status` (`status`),
    INDEX `idx_type` (`case_type`),
    INDEX `idx_judge` (`assigned_judge_id`),
    INDEX `idx_filing_date` (`filing_date`),
    INDEX `idx_next_hearing` (`next_hearing_date`),
    FULLTEXT `idx_search` (`title`, `description`, `plaintiff`, `defendant`, `tags`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Case participants (lawyers, witnesses, etc.)
CREATE TABLE `case_participants` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `case_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `role` VARCHAR(100) NOT NULL COMMENT 'Prosecutor, Defense Attorney, Witness, etc.',
    `contact_email` VARCHAR(255) NULL,
    `contact_phone` VARCHAR(20) NULL,
    `address` TEXT NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    INDEX `idx_case_participant` (`case_id`, `role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents table - all case-related documents
CREATE TABLE `documents` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `case_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `document_type` VARCHAR(50) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `file_size` BIGINT NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_hash` VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash for integrity',
    `visibility` ENUM('public', 'case_staff', 'judge_only', 'admin_only') DEFAULT 'case_staff',
    `signed_by` VARCHAR(255) NULL,
    `uploaded_by` INT NOT NULL,
    `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `version` INT DEFAULT 1,
    `parent_document_id` INT NULL COMMENT 'For document versions',
    `is_active` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`parent_document_id`) REFERENCES `documents`(`id`) ON DELETE SET NULL,
    INDEX `idx_case_documents` (`case_id`),
    INDEX `idx_document_type` (`document_type`),
    INDEX `idx_visibility` (`visibility`),
    INDEX `idx_uploaded_by` (`uploaded_by`),
    FULLTEXT `idx_document_search` (`title`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hearings table - court hearings and scheduling
CREATE TABLE `hearings` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `case_id` INT NOT NULL,
    `hearing_date` DATETIME NOT NULL,
    `duration_minutes` INT DEFAULT 60,
    `court_room` VARCHAR(100) NOT NULL,
    `hearing_type` VARCHAR(50) NOT NULL,
    `status` ENUM('Scheduled', 'Rescheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    `judge_id` INT NOT NULL,
    `clerk_id` INT NULL,
    `notes` TEXT NULL,
    `outcome` TEXT NULL,
    `recording_path` VARCHAR(500) NULL,
    `public_gallery` TINYINT(1) DEFAULT 1,
    `virtual_meeting_link` VARCHAR(500) NULL,
    `created_by` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`judge_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`clerk_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_hearing_date` (`hearing_date`),
    INDEX `idx_court_room` (`court_room`),
    INDEX `idx_judge_hearings` (`judge_id`, `hearing_date`),
    INDEX `idx_case_hearings` (`case_id`, `hearing_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Judgments table - final decisions and rulings
CREATE TABLE `judgments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `case_id` INT NOT NULL,
    `judge_id` INT NOT NULL,
    `hearing_id` INT NULL,
    `judgment_date` DATE NOT NULL,
    `outcome` VARCHAR(100) NOT NULL,
    `judgment_text` LONGTEXT NOT NULL,
    `orders` TEXT NULL COMMENT 'Specific orders and actions',
    `fine_amount` DECIMAL(15,2) NULL,
    `sentence_details` TEXT NULL,
    `appeal_deadline` DATE NULL,
    `document_path` VARCHAR(500) NULL COMMENT 'Signed judgment document',
    `publish_public` TINYINT(1) DEFAULT 0,
    `public_summary` TEXT NULL COMMENT 'Redacted summary for public',
    `is_final` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`judge_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`hearing_id`) REFERENCES `hearings`(`id`) ON DELETE SET NULL,
    INDEX `idx_case_judgment` (`case_id`),
    INDEX `idx_judge_judgments` (`judge_id`),
    INDEX `idx_judgment_date` (`judgment_date`),
    INDEX `idx_outcome` (`outcome`),
    FULLTEXT `idx_judgment_search` (`judgment_text`, `orders`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table - system notifications
CREATE TABLE `notifications` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `recipient_id` INT NOT NULL,
    `sender_id` INT NULL,
    `case_id` INT NULL,
    `hearing_id` INT NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'hearing_reminder, case_update, document_upload, etc.',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `priority` ENUM('High', 'Normal', 'Low') DEFAULT 'Normal',
    `send_email` TINYINT(1) DEFAULT 1,
    `send_sms` TINYINT(1) DEFAULT 0,
    `email_sent` TINYINT(1) DEFAULT 0,
    `sms_sent` TINYINT(1) DEFAULT 0,
    `read_at` DATETIME NULL,
    `scheduled_for` DATETIME NULL COMMENT 'For scheduled notifications',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`recipient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`hearing_id`) REFERENCES `hearings`(`id`) ON DELETE CASCADE,
    INDEX `idx_recipient` (`recipient_id`),
    INDEX `idx_unread` (`recipient_id`, `read_at`),
    INDEX `idx_scheduled` (`scheduled_for`),
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit logs table - system activity tracking
CREATE TABLE `audit_logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(50) NULL,
    `record_id` INT NULL,
    `case_id` INT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `session_id` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_action` (`user_id`, `action`),
    INDEX `idx_case_audit` (`case_id`),
    INDEX `idx_action_date` (`action`, `created_at`),
    INDEX `idx_table_record` (`table_name`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Case notes table - internal notes and comments
CREATE TABLE `case_notes` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `case_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `note_type` ENUM('general', 'private', 'court_order', 'reminder') DEFAULT 'general',
    `title` VARCHAR(255) NULL,
    `content` TEXT NOT NULL,
    `visibility` ENUM('public', 'staff_only', 'judge_only') DEFAULT 'staff_only',
    `reminder_date` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_case_notes` (`case_id`, `created_at`),
    INDEX `idx_user_notes` (`user_id`),
    INDEX `idx_reminders` (`reminder_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Document access requests table
CREATE TABLE `document_requests` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `case_id` INT NOT NULL,
    `requestor_id` INT NOT NULL,
    `approver_id` INT NULL,
    `document_ids` JSON NOT NULL COMMENT 'Array of requested document IDs',
    `reason` TEXT NOT NULL,
    `due_by` DATETIME NULL,
    `status` ENUM('pending', 'approved', 'denied', 'delivered') DEFAULT 'pending',
    `approved_at` DATETIME NULL,
    `delivered_at` DATETIME NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`requestor_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`approver_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_requestor` (`requestor_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_case_requests` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System settings table
CREATE TABLE `system_settings` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `setting_key` VARCHAR(100) UNIQUE NOT NULL,
    `setting_value` TEXT NULL,
    `setting_type` ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    `description` TEXT NULL,
    `updated_by` INT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraints for users table
ALTER TABLE `users` ADD FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL;
ALTER TABLE `departments` ADD FOREIGN KEY (`head_judge_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Insert default departments
INSERT INTO `departments` (`name`, `description`) VALUES
('Criminal Court', 'Handles all criminal cases and proceedings'),
('Civil Court', 'Handles civil disputes and litigation'),
('Family Court', 'Handles family law matters including divorce, custody, etc.'),
('Appeals Court', 'Handles appeals from lower courts'),
('Administrative Court', 'Handles administrative and regulatory matters');

-- Insert default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'Digital Court Case Management System', 'string', 'Name of the court system'),
('timezone', 'UTC', 'string', 'Default system timezone'),
('max_upload_size', '20971520', 'integer', 'Maximum file upload size in bytes'),
('session_timeout', '3600', 'integer', 'Session timeout in seconds'),
('notification_lead_times', '[24, 2]', 'json', 'Hours before hearing to send notifications'),
('email_notifications', 'true', 'boolean', 'Enable email notifications'),
('sms_notifications', 'false', 'boolean', 'Enable SMS notifications'),
('case_number_counter', '1', 'integer', 'Counter for generating case numbers');

-- Create default admin user (password: admin123)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password_hash`, `role`, `is_active`, `email_verified`) VALUES
('System', 'Administrator', 'admin@court.example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1);

-- Create indexes for better performance
CREATE INDEX `idx_cases_search` ON `cases` (`status`, `case_type`, `assigned_judge_id`);
CREATE INDEX `idx_hearings_schedule` ON `hearings` (`hearing_date`, `court_room`, `status`);
CREATE INDEX `idx_documents_case_type` ON `documents` (`case_id`, `document_type`, `is_active`);
CREATE INDEX `idx_notifications_pending` ON `notifications` (`recipient_id`, `read_at`, `scheduled_for`);

-- Create views for common queries
CREATE VIEW `active_cases` AS
SELECT 
    c.*,
    CONCAT(u.first_name, ' ', u.last_name) as judge_name,
    d.name as department_name,
    (SELECT COUNT(*) FROM documents WHERE case_id = c.id AND is_active = 1) as document_count,
    (SELECT COUNT(*) FROM hearings WHERE case_id = c.id) as hearing_count
FROM cases c
LEFT JOIN users u ON c.assigned_judge_id = u.id
LEFT JOIN departments d ON c.department_id = d.id
WHERE c.status NOT IN ('Closed', 'Dismissed');

CREATE VIEW `upcoming_hearings` AS
SELECT 
    h.*,
    c.case_no,
    c.title as case_title,
    c.case_type,
    CONCAT(j.first_name, ' ', j.last_name) as judge_name,
    CONCAT(cl.first_name, ' ', cl.last_name) as clerk_name
FROM hearings h
JOIN cases c ON h.case_id = c.id
JOIN users j ON h.judge_id = j.id
LEFT JOIN users cl ON h.clerk_id = cl.id
WHERE h.hearing_date >= CURDATE() 
AND h.status IN ('Scheduled', 'Rescheduled')
ORDER BY h.hearing_date ASC;

-- Create triggers for audit logging
DELIMITER $$

CREATE TRIGGER `cases_audit_insert` AFTER INSERT ON `cases`
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, case_id, new_values, created_at)
    VALUES (NEW.created_by, 'INSERT', 'cases', NEW.id, NEW.id, JSON_OBJECT(
        'case_no', NEW.case_no,
        'title', NEW.title,
        'case_type', NEW.case_type,
        'status', NEW.status
    ), NOW());
END$$

CREATE TRIGGER `cases_audit_update` AFTER UPDATE ON `cases`
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, case_id, old_values, new_values, created_at)
    VALUES (@current_user_id, 'UPDATE', 'cases', NEW.id, NEW.id, 
        JSON_OBJECT('status', OLD.status, 'assigned_judge_id', OLD.assigned_judge_id),
        JSON_OBJECT('status', NEW.status, 'assigned_judge_id', NEW.assigned_judge_id),
        NOW());
END$$

CREATE TRIGGER `documents_audit_insert` AFTER INSERT ON `documents`
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, case_id, new_values, created_at)
    VALUES (NEW.uploaded_by, 'INSERT', 'documents', NEW.id, NEW.case_id, JSON_OBJECT(
        'title', NEW.title,
        'document_type', NEW.document_type,
        'visibility', NEW.visibility
    ), NOW());
END$$

DELIMITER ;