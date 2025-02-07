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
    $payroll_number = trim($_POST['payroll_number']);
    $name = trim($_POST['name']);
    $department = trim($_POST['department']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO workers (payroll_number, name, department) VALUES (?, ?, ?)");
        $stmt->execute([$payroll_number, $name, $department]);
        $success = "Worker added successfully";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry error
            $error = "Payroll number already exists";
        } else {
            $error = "An error occurred while adding the worker";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Worker - Medical Records Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-bar">
                <div class="welcome-message">
                    <h2>Add New Worker</h2>
                    <p>Enter worker details below</p>
                </div>
                <div class="action-buttons">
                    <a href="import.php" class="btn-import">
                        <i class="fas fa-file-import"></i>
                        Import Multiple Workers
                    </a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="form-container">
                    <?php if ($error): ?>
                        <div class="error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="add.php" class="styled-form">
                        <div class="form-group">
                            <label for="payroll_number">
                                <i class="fas fa-id-card"></i>
                                Payroll Number
                            </label>
                            <input type="text" id="payroll_number" name="payroll_number" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-user"></i>
                                Full Name
                            </label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="department">
                                <i class="fas fa-building"></i>
                                Department
                            </label>
                            <input type="text" id="department" name="department" required>
                        </div>
                        
                        <button type="submit" class="btn-add">
                            <i class="fas fa-plus-circle"></i>
                            Add Worker
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/menu.js"></script>
</body>
</html> 