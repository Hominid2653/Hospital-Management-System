USE SianRosesMedical;

-- First, clear any existing admin user to avoid duplicates
DELETE FROM users WHERE username = 'admin';

-- Insert the admin user
-- Username: admin
-- Password: admin123
INSERT INTO users (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); 