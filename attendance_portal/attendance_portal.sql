-- Advanced Attendance Portal Database Schema
-- Create Database
CREATE DATABASE IF NOT EXISTS attendance_portal;
USE attendance_portal;

-- Organizations Table
CREATE TABLE organizations (
    org_id INT PRIMARY KEY AUTO_INCREMENT,
    org_name VARCHAR(255) NOT NULL,
    org_code VARCHAR(20) UNIQUE NOT NULL,
    org_type ENUM('college', 'office') NOT NULL,
    org_location TEXT,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    admin_email VARCHAR(255) UNIQUE NOT NULL,
    admin_password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_org_code (org_code),
    INDEX idx_admin_email (admin_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users Table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    org_id INT NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    gender ENUM('male', 'female', 'other') NOT NULL,
    role ENUM('student', 'teacher', 'employee') NOT NULL,
    class_name VARCHAR(100),
    roll_number VARCHAR(50),
    department VARCHAR(100),
    face_encoding TEXT,
    profile_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES organizations(org_id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_org_id (org_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attendance Records Table
CREATE TABLE attendance (
    attendance_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    org_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in_time TIME NOT NULL,
    status ENUM('present', 'late', 'absent') DEFAULT 'present',
    recognition_confidence DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (org_id) REFERENCES organizations(org_id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_attendance (user_id, attendance_date),
    INDEX idx_user_date (user_id, attendance_date),
    INDEX idx_org_date (org_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attendance Analytics View
CREATE VIEW attendance_analytics AS
SELECT 
    u.org_id,
    u.user_id,
    u.full_name,
    u.email,
    u.gender,
    u.role,
    COUNT(a.attendance_id) as total_days,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_days,
    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
    ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.attendance_id)) * 100, 2) as attendance_percentage
FROM users u
LEFT JOIN attendance a ON u.user_id = a.user_id
GROUP BY u.user_id;

-- Monthly Attendance View
CREATE VIEW monthly_attendance AS
SELECT 
    u.org_id,
    u.user_id,
    u.full_name,
    u.email,
    DATE_FORMAT(a.attendance_date, '%Y-%m') as month,
    COUNT(a.attendance_id) as total_days,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
    ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.attendance_id)) * 100, 2) as attendance_percentage
FROM users u
LEFT JOIN attendance a ON u.user_id = a.user_id
WHERE a.attendance_date IS NOT NULL
GROUP BY u.user_id, DATE_FORMAT(a.attendance_date, '%Y-%m');

-- Gender Statistics View
CREATE VIEW gender_statistics AS
SELECT 
    org_id,
    gender,
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users WHERE org_id = u.org_id)), 2) as percentage
FROM users u
GROUP BY org_id, gender;

-- Insert Sample Admin Organization
INSERT INTO organizations (org_name, org_code, org_type, org_location, start_time, end_time, admin_email, admin_password) 
VALUES 
('Demo College', 'DEMO2025', 'college', '123 Education Street, City', '09:00:00', '17:00:00', 'admin@demo.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- Password: password123