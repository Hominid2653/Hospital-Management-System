-- First add the column if it doesn't exist
ALTER TABLE medical_history 
ADD COLUMN IF NOT EXISTS disease_classification_id INT;

-- Then add the foreign key constraint
ALTER TABLE medical_history
ADD CONSTRAINT medical_history_ibfk_2
FOREIGN KEY (disease_classification_id) 
REFERENCES disease_classifications(id); 