CREATE TABLE disease_classifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add disease_classification_id to medical_history table
ALTER TABLE medical_history 
ADD COLUMN disease_classification_id INT,
ADD FOREIGN KEY (disease_classification_id) REFERENCES disease_classifications(id);

-- Insert some common disease classifications
INSERT INTO disease_classifications (name, description) VALUES 
('Respiratory', 'Diseases affecting the respiratory system'),
('Cardiovascular', 'Diseases affecting the heart and blood vessels'),
('Musculoskeletal', 'Diseases affecting muscles, bones, and joints'),
('Gastrointestinal', 'Diseases affecting the digestive system'),
('Infectious', 'Diseases caused by pathogens'),
('Occupational', 'Work-related conditions and injuries'),
('Chronic', 'Long-term ongoing conditions'),
('Other', 'Other conditions not classified above'); 