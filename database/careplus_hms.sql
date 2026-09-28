-- CarePlus Healthcare Management System Database

CREATE DATABASE IF NOT EXISTS careplus_hms;
USE careplus_hms;

-- Users Table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'admin', 'manager') NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Patients Table
CREATE TABLE patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    address VARCHAR(255),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Doctors Table
CREATE TABLE doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    specialisation VARCHAR(100),
    availability VARCHAR(255),
    department VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Admin Staff Table
CREATE TABLE admin_staff (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    staff_role VARCHAR(100),
    department VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Appointments Table
CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(255),
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE
);

-- Medical Records Table
CREATE TABLE medical_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    diagnosis VARCHAR(255),
    treatment VARCHAR(500),
    consultation_notes TEXT,
    record_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
);

-- Notifications Table
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    appointment_id INT,
    message VARCHAR(500) NOT NULL,
    notification_type ENUM('appointment', 'reminder', 'cancellation', 'general') DEFAULT 'general',
    status ENUM('unread', 'read') DEFAULT 'unread',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
);

-- Audit Logs Table
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    description VARCHAR(500),
    action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Sample Data

-- Insert Admin User
INSERT INTO users (full_name, email, password_hash, role, phone) VALUES
('Admin User', 'admin@careplus.com', '$2a$12$TPpPbyFyHdwy7MjTG0xPjukEF3bc9IlYH7Q3ygxhKZ9YKhhmcX5SS', 'admin', '0123456789');

-- Insert Manager User
INSERT INTO users (full_name, email, password_hash, role, phone) VALUES
('Manager User', 'manager@careplus.com', '$2a$12$/Kw6MeOWfXg/GvP2NoWa5eIo2lf7jGDOBV/9oz2u/TNgFnW0cdzUa', 'manager', '0123456790');

-- Insert Doctor Users
INSERT INTO users (full_name, email, password_hash, role, phone) VALUES
('Dr. Sarah Smith', 'dr.smith@careplus.com', '$2a$12$DZtR.sY4RfYIJQbI.wHWLeMfYn/6.UhhoQIuQQ8VkTIcoQDXpb0w6', 'doctor', '0987654321'),
('Dr. James Jones', 'dr.jones@careplus.com', '$2a$12$DZtR.sY4RfYIJQbI.wHWLeMfYn/6.UhhoQIuQQ8VkTIcoQDXpb0w6', 'doctor', '0987654322');

-- Insert Doctor Details
INSERT INTO doctors (user_id, specialisation, availability, department) VALUES
(3, 'General Practice', 'Monday to Friday 9AM to 5PM', 'General Medicine'),
(4, 'Cardiology', 'Monday to Friday 10AM to 6PM', 'Cardiology');

-- Insert Patient Users
INSERT INTO users (full_name, email, password_hash, role, phone) VALUES
('John Michael', 'patient1@example.com', '$2y$10$uZBCJoGkLBRj2FT5EFo.9eWXYsqzuLDYvvPuLLf7p5LpL5.Xe3OzW', 'patient', '0555123456'),
('Sarah Williams', 'patient2@example.com', '$2y$10$uZBCJoGkLBRj2FT5EFo.9eWXYsqzuLDYvvPuLLf7p5LpL5.Xe3OzW', 'patient', '0555123457');

-- Insert Patient Details
INSERT INTO patients (user_id, date_of_birth, gender, address, emergency_contact_name, emergency_contact_phone) VALUES
(5, '1990-05-15', 'male', '123 Main Street, City', 'Emily Michael', '0555123458'),
(6, '1995-08-22', 'female', '456 Oak Avenue, City', 'David Williams', '0555123459');

-- Sample Appointments
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', 'Regular checkup', 'confirmed'),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '14:30:00', 'Follow-up consultation', 'pending'),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '11:00:00', 'Cardiac assessment', 'confirmed');

-- Sample Notifications
INSERT INTO notifications (user_id, appointment_id, message, notification_type, status) VALUES
(5, 1, 'Your appointment with Dr. Sarah Smith is confirmed for 2 days from now', 'appointment', 'unread'),
(5, 2, 'Reminder: You have an appointment scheduled in 5 days', 'reminder', 'unread'),
(6, 3, 'Your appointment with Dr. James Jones is confirmed for 3 days from now', 'appointment', 'read');
