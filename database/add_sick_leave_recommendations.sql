CREATE TABLE sick_leave_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medical_record_id INT NOT NULL,
    worker_id VARCHAR(20) NOT NULL,
    days_recommended INT NOT NULL,
    payment_recommendation ENUM('full_pay', 'half_pay', 'no_pay') NOT NULL,
    reason TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medical_record_id) REFERENCES medical_history(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES workers(payroll_number)
); 