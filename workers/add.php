<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';
$base_url = '../'; // Add base_url for sidebar

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO workers (
                payroll_number, 
                name, 
                department,
                role,
                phone,
                email
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['payroll_number'],
            $_POST['name'],
            $_POST['department'],
            $_POST['role'],
            $_POST['phone'],
            $_POST['email'] ?: null
        ]);

        $_SESSION['success'] = "Worker registered successfully!";
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registe Employee - Medical Records Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Form Container */
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Alert Messages */
        .error, .success {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        .success {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        /* Form Styles */
        .styled-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr); /* Two columns layout */
            gap: 1.5rem;
            max-width: 900px;
        }

        /* Full width for submit button container */
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
        }

        .form-group {
            margin-bottom: 0; /* Remove margin as we're using grid gap */
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Style both input and select elements */
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            color: var(--text-primary);
            background-color: white;
            transition: all 0.3s ease;
        }

        /* Select specific styles */
        .styled-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        /* Placeholder styles */
        .form-group input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        /* Focus states */
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(231, 84, 128, 0.1);
        }

        /* Optional field style */
        .form-group input[placeholder="Optional"] {
            border-style: dashed;
        }

        /* Update submit button */
        .btn-add {
            background: var(--primary);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 25px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 84, 128, 0.2);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .styled-form {
                grid-template-columns: 1fr; /* Single column on mobile */
            }
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-action {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-action.secondary {
            background-color: var(--secondary);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: white;
        }

        /* Form Section Styles */
        .form-section {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-card {
            background: rgba(255, 255, 255, 0.85);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .form-card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .card-header i {
            font-size: 1.5rem;
            color: var(--primary);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(231, 84, 128, 0.1);
            border-radius: 10px;
        }

        .card-header h2 {
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        /* Form Group Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            color: var(--text-primary);
            background-color: white;
            transition: all 0.3s ease;
        }

        /* Form Actions */
        .form-actions {
            margin-top: 2rem;
            display: flex;
            justify-content: flex-end;
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div>
                    <h1>Register Employee</h1>
                    <p>Register a new Employee in the system</p>
                </div>
                <div class="header-actions">
                    <a href="import.php" class="btn-action">
                        <i class="fas fa-file-import"></i>
                        Import Workers
                    </a>
                    <a href="list.php" class="btn-action secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>

            <div class="content-wrapper">
                <div class="form-section">
                    <!-- Work Details Card -->
                    <div class="form-card">
                        <div class="card-header">
                            <i class="fas fa-briefcase"></i>
                            <h2>Work Details</h2>
                        </div>
                        <form method="POST" action="add.php" class="styled-form">
                            <div class="form-group">
                                <label for="payroll_number">Payroll Number</label>
                                <input type="text" 
                                       id="payroll_number" 
                                       name="payroll_number" 
                                       placeholder="Enter payroll number"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       placeholder="Enter full name"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="department">Department</label>
                                <select name="department" id="department" class="styled-select" required>
                                    <option value="">Select Department</option>
                                    <option value="General">General</option>
                                    <option value="Management">Management</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Role/Position</label>
                                <input type="text" 
                                       name="role" 
                                       id="role" 
                                       placeholder="e.g. Farm Supervisor, Medical Officer"
                                       required>
                            </div>
                        </div>

                        <!-- Contact Details Card -->
                        <div class="form-card">
                            <div class="card-header">
                                <i class="fas fa-address-card"></i>
                                <h2>Contact Information</h2>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       placeholder="Enter phone number"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       placeholder="Optional">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-add">
                                    <i class="fas fa-plus-circle"></i>
                                    Add Worker
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/menu.js"></script>
</body>
</html> 