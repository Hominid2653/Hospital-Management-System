-- Create database
CREATE DATABASE IF NOT EXISTS SianRosesMedical;
USE SianRosesMedical;

-- Workers table
CREATE TABLE workers (
    payroll_number VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Medical history table
CREATE TABLE medical_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_number VARCHAR(20),
    diagnosis TEXT NOT NULL,
    visit_date DATE NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_number) REFERENCES workers(payroll_number)
);

-- Prescriptions table
CREATE TABLE prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medical_history_id INT,
    drug_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medical_history_id) REFERENCES medical_history(id)
);

-- Create admin user for doctor
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Drugs table
CREATE TABLE drugs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('in_stock', 'low_stock', 'out_of_stock') DEFAULT 'in_stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add these indexes to improve query performance
ALTER TABLE medical_history
ADD INDEX idx_visit_date (visit_date),
ADD INDEX idx_payroll_disease (payroll_number, disease_classification_id);

ALTER TABLE prescriptions
ADD INDEX idx_drug_name (drug_name);

ALTER TABLE leave_recommendations
ADD INDEX idx_dates (start_date, end_date),
ADD INDEX idx_status (status);

-- Optimize table engines and character sets
ALTER TABLE workers ENGINE = InnoDB;
ALTER TABLE medical_history ENGINE = InnoDB;
ALTER TABLE prescriptions ENGINE = InnoDB;
ALTER TABLE leave_recommendations ENGINE = InnoDB;

-- Set proper character sets
ALTER TABLE workers CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE medical_history CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE prescriptions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE leave_recommendations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; 