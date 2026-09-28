-- ============================================================
-- CarePlus HMS — Feature Upgrade Migration
-- Run this AFTER careplus_hms.sql has already created the base DB.
-- Adds: Health Vitals, Medications, Symptom Checker, Allergies,
-- Lab Reports, Vaccinations, SOS Alerts, Ratings, QR ID, Video Rooms
-- ============================================================

USE careplus_hms;

-- ---- Extend patients table ----
ALTER TABLE patients
    ADD COLUMN blood_group VARCHAR(5) DEFAULT NULL AFTER gender,
    ADD COLUMN family_history TEXT DEFAULT NULL,
    ADD COLUMN qr_token VARCHAR(64) UNIQUE DEFAULT NULL;

-- ---- Extend appointments table (for Video Consultation) ----
ALTER TABLE appointments
    ADD COLUMN appointment_type ENUM('in_person','video') DEFAULT 'in_person' AFTER reason,
    ADD COLUMN video_room VARCHAR(100) DEFAULT NULL;

-- ---- Extend doctors table (for Admin/Doctor Analytics "revenue" estimate) ----
ALTER TABLE doctors
    ADD COLUMN consultation_fee DECIMAL(8,2) DEFAULT 50.00;

-- ---- WebRTC signaling (poor-man's signaling server via DB polling) ----
-- Real video/audio flows peer-to-peer over WebRTC once connected; this table
-- only carries the short-lived handshake (SDP offer/answer + ICE candidates).
CREATE TABLE video_signals (
    signal_id INT AUTO_INCREMENT PRIMARY KEY,
    room_code VARCHAR(100) NOT NULL,
    sender_role ENUM('patient','doctor') NOT NULL,
    signal_type ENUM('offer','answer','candidate','hangup') NOT NULL,
    payload TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room (room_code)
);

-- ---- Health Dashboard: patient-logged vitals ----
CREATE TABLE health_vitals (
    vital_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    systolic INT DEFAULT NULL,
    diastolic INT DEFAULT NULL,
    heart_rate INT DEFAULT NULL,
    blood_sugar DECIMAL(5,1) DEFAULT NULL,
    weight_kg DECIMAL(5,1) DEFAULT NULL,
    height_cm DECIMAL(5,1) DEFAULT NULL,
    steps INT DEFAULT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
);

-- ---- Smart Medicine Reminder ----
CREATE TABLE medications (
    medication_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    medicine_name VARCHAR(150) NOT NULL,
    dosage VARCHAR(100),
    frequency_per_day INT DEFAULT 1,
    reminder_times VARCHAR(255) COMMENT 'comma separated HH:MM values',
    start_date DATE,
    end_date DATE,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
);

CREATE TABLE medication_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    medication_id INT NOT NULL,
    log_date DATE NOT NULL,
    log_time TIME NOT NULL,
    status ENUM('taken','missed') DEFAULT 'taken',
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medication_id) REFERENCES medications(medication_id) ON DELETE CASCADE
);

-- ---- AI Symptom Checker ----
CREATE TABLE symptom_checks (
    check_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    symptoms_selected VARCHAR(500),
    results_json TEXT,
    recommended_specialisation VARCHAR(100),
    urgency_level ENUM('low','moderate','emergency') DEFAULT 'low',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
);

-- ---- EHR: Allergies ----
CREATE TABLE allergies (
    allergy_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    allergen VARCHAR(150) NOT NULL,
    reaction VARCHAR(255),
    severity ENUM('mild','moderate','severe') DEFAULT 'mild',
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
);

-- ---- EHR: Lab Reports ----
CREATE TABLE lab_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT,
    test_name VARCHAR(150) NOT NULL,
    result_summary VARCHAR(500),
    file_path VARCHAR(255),
    report_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL
);

-- ---- EHR: Vaccinations ----
CREATE TABLE vaccinations (
    vaccination_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    vaccine_name VARCHAR(150) NOT NULL,
    dose_number INT DEFAULT 1,
    date_administered DATE NOT NULL,
    administered_by VARCHAR(150),
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
);

-- ---- Emergency SOS ----
CREATE TABLE sos_alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    latitude DECIMAL(10,6) DEFAULT NULL,
    longitude DECIMAL(10,6) DEFAULT NULL,
    status ENUM('active','resolved') DEFAULT 'active',
    triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
);

-- ---- Appointment ratings (feeds Doctor Analytics "satisfaction") ----
CREATE TABLE appointment_ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL UNIQUE,
    rating INT NOT NULL COMMENT '1-5',
    comment VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
);

-- Backfill QR tokens + blood groups for existing sample patients
UPDATE patients SET qr_token = SHA2(CONCAT(patient_id,'-',UNIX_TIMESTAMP(),'-',RAND()), 256) WHERE qr_token IS NULL;
UPDATE patients SET blood_group = 'O+' WHERE patient_id = 1;
UPDATE patients SET blood_group = 'A+' WHERE patient_id = 2;

-- Sample allergy for demo
INSERT INTO allergies (patient_id, allergen, reaction, severity) VALUES
(1, 'Penicillin', 'Rash and swelling', 'moderate');
