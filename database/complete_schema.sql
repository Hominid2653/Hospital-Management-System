-- Drop database if exists and recreate
DROP DATABASE IF EXISTS SianRosesMedical;
CREATE DATABASE SianRosesMedical;
USE SianRosesMedical;

-- Drop tables if they exist (in correct order due to foreign keys)
DROP TABLE IF EXISTS leave_recommendations;
DROP TABLE IF EXISTS prescriptions;
DROP TABLE IF EXISTS medical_history;
DROP TABLE IF EXISTS disease_classifications;
DROP TABLE IF EXISTS drugs;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS workers;

-- Workers table
CREATE TABLE workers (
    payroll_number VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    role VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Disease classifications table
CREATE TABLE disease_classifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Medical history table
CREATE TABLE medical_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_number VARCHAR(20),
    diagnosis TEXT NOT NULL,
    visit_date DATE NOT NULL,
    remarks TEXT,
    disease_classification_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_number) REFERENCES workers(payroll_number),
    FOREIGN KEY (disease_classification_id) REFERENCES disease_classifications(id)
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

-- Users table
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

-- Leave recommendations table
CREATE TABLE leave_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medical_history_id INT NOT NULL,
    worker_id VARCHAR(20) NOT NULL,
    days_recommended INT NOT NULL,
    payment_recommendation ENUM('full_pay', 'half_pay', 'no_pay') NOT NULL,
    reason TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medical_history_id) REFERENCES medical_history(id),
    FOREIGN KEY (worker_id) REFERENCES workers(payroll_number)
);

-- Insert disease classifications
INSERT INTO disease_classifications (name, description) VALUES 
('Respiratory', 'Diseases affecting the respiratory system'),
('Cardiovascular', 'Diseases affecting the heart and blood vessels'),
('Musculoskeletal', 'Diseases affecting muscles, bones, and joints'),
('Gastrointestinal', 'Diseases affecting the digestive system'),
('Infectious', 'Diseases caused by pathogens'),
('Occupational', 'Work-related conditions and injuries'),
('Chronic', 'Long-term ongoing conditions'),
('Other', 'Other conditions not classified above');

-- Create test user (Elias Cheruiyot)
INSERT INTO users (username, email, password) VALUES 
('Elias Cheruiyot', 'eliascheruiyot9@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); 