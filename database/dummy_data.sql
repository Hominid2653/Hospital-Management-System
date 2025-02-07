USE SianRosesMedical;

-- Clear existing data
DELETE FROM prescriptions;
DELETE FROM medical_history;
DELETE FROM workers;

-- Insert workers
INSERT INTO workers (payroll_number, name, department) VALUES
('EMP001', 'John Smith', 'Production'),
('EMP002', 'Sarah Johnson', 'Administration'),
('EMP003', 'Michael Brown', 'Maintenance'),
('EMP004', 'Emily Davis', 'Production'),
('EMP005', 'James Wilson', 'Quality Control'),
('EMP006', 'Lisa Anderson', 'Administration'),
('EMP007', 'Robert Taylor', 'Maintenance'),
('EMP008', 'Emma White', 'Production'),
('EMP009', 'David Martinez', 'Quality Control'),
('EMP010', 'Jennifer Garcia', 'Administration');

-- Insert medical history records
INSERT INTO medical_history (payroll_number, diagnosis, visit_date, remarks) VALUES
-- John Smith's records
('EMP001', 'Common cold with fever and congestion', '2024-03-15', 'Patient advised to rest and increase fluid intake'),
('EMP001', 'Lower back pain', '2024-02-10', 'Ergonomic assessment of workstation recommended'),

-- Sarah Johnson's records
('EMP002', 'Migraine headache', '2024-03-10', 'Triggered by extended screen time'),
('EMP002', 'Seasonal allergies', '2024-01-20', 'Prescribed antihistamines'),

-- Michael Brown's records
('EMP003', 'Minor hand injury', '2024-03-01', 'Injury occurred during equipment maintenance'),
('EMP003', 'Annual health checkup', '2023-12-15', 'All vitals normal'),

-- Emily Davis's records
('EMP004', 'Respiratory infection', '2024-02-28', 'Follow-up scheduled in 1 week'),
('EMP004', 'Sprained ankle', '2024-01-05', 'Injury occurred outside work'),

-- James Wilson's records
('EMP005', 'High blood pressure', '2024-03-12', 'Monthly monitoring required'),
('EMP005', 'Stress-related symptoms', '2024-02-01', 'Referred to company counseling program'),

-- Lisa Anderson's records
('EMP006', 'Carpal tunnel syndrome', '2024-03-08', 'Ergonomic keyboard recommended'),

-- Robert Taylor's records
('EMP007', 'Back strain', '2024-02-20', 'Proper lifting techniques reviewed'),

-- Emma White's records
('EMP008', 'Workplace exposure assessment', '2024-03-05', 'No adverse effects noted'),

-- David Martinez's records
('EMP009', 'Annual health checkup', '2024-01-15', 'All parameters within normal range'),

-- Jennifer Garcia's records
('EMP010', 'Repetitive strain injury', '2024-02-15', 'Workplace ergonomics assessment scheduled');

-- Insert prescriptions
INSERT INTO prescriptions (medical_history_id, drug_name, dosage) VALUES
(1, 'Paracetamol', '500mg twice daily'),
(1, 'Decongestant', '10mg once daily'),
(2, 'Ibuprofen', '400mg as needed'),
(3, 'Sumatriptan', '50mg as needed'),
(4, 'Cetirizine', '10mg once daily'),
(5, 'Ibuprofen', '400mg three times daily'),
(5, 'Bandage wrap', 'Apply as directed'),
(7, 'Amoxicillin', '500mg three times daily'),
(7, 'Cough syrup', '10ml three times daily'),
(9, 'Amlodipine', '5mg once daily'),
(10, 'Diazepam', '5mg as needed'),
(11, 'Wrist brace', 'Wear during computer work'),
(11, 'Naproxen', '500mg twice daily'),
(12, 'Muscle relaxant', '10mg three times daily'),
(15, 'Anti-inflammatory gel', 'Apply three times daily');

-- Verify data insertion
SELECT 'Workers count: ' as 'Check', COUNT(*) FROM workers
UNION ALL
SELECT 'Medical records count: ', COUNT(*) FROM medical_history
UNION ALL
SELECT 'Prescriptions count: ', COUNT(*) FROM prescriptions; 