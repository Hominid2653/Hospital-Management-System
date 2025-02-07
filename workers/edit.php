<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';
$base_url = '../';
$worker = null;

// Get worker details
if (isset($_GET['id'])) {
    $payroll_number = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM workers WHERE payroll_number = ?");
        $stmt->execute([$payroll_number]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$worker) {
            header("Location: list.php");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error fetching worker details";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $department = trim($_POST['department']);
    $payroll_number = $_POST['payroll_number'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE workers 
            SET name = ?, department = ? 
            WHERE payroll_number = ?
        ");
        $stmt->execute([$name, $department, $payroll_number]);
        $success = "Worker details updated successfully";
        
        // Refresh worker data
        $stmt = $pdo->prepare("SELECT * FROM workers WHERE payroll_number = ?");
        $stmt->execute([$payroll_number]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error updating worker details";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Worker - Sian Roses</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-bar">
                <div class="welcome-message">
                    <h2>Edit Worker Details</h2>
                    <p>Update worker information</p>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="form-container">
                    <?php if ($error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="styled-form">
                        <input type="hidden" name="payroll_number" 
                               value="<?php echo htmlspecialchars($worker['payroll_number']); ?>">
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-id-card"></i>
                                Payroll Number
                            </label>
                            <input type="text" value="<?php echo htmlspecialchars($worker['payroll_number']); ?>" 
                                   disabled>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-user"></i>
                                Full Name
                            </label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($worker['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="department">
                                <i class="fas fa-building"></i>
                                Department
                            </label>
                            <input type="text" id="department" name="department" 
                                   value="<?php echo htmlspecialchars($worker['department']); ?>" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-add">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                            <a href="list.php" class="btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Back to List
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/menu.js"></script>
</body>
</html> 