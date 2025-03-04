-- Add indexes for frequently queried columns
ALTER TABLE medical_history ADD INDEX idx_visit_date (visit_date);
ALTER TABLE medical_history ADD INDEX idx_payroll_worker (payroll_number);
ALTER TABLE workers ADD INDEX idx_department (department);
ALTER TABLE drugs ADD INDEX idx_status (status);

-- Add composite indexes for common joins
ALTER TABLE medical_history ADD INDEX idx_worker_date (payroll_number, visit_date); 