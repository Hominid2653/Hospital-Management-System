<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$base_url = '../';
$error = null;
$success = null;

// Get worker details
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM workers WHERE payroll_number = ?");
        $stmt->execute([$_GET['id']]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$worker) {
            header("Location: list.php");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error fetching worker details: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE workers 
            SET name = ?, 
                department = ?,
                role = ?,
                phone = ?,
                email = ?
            WHERE payroll_number = ?
        ");
        
        $stmt->execute([
            $_POST['name'],
            $_POST['department'],
            $_POST['role'],
            $_POST['phone'],
            $_POST['email'],
            $_GET['id']
        ]);

        $success = "Employee details updated successfully";
        
        // Refresh worker data
        $stmt = $pdo->prepare("SELECT * FROM workers WHERE payroll_number = ?");
        $stmt->execute([$_GET['id']]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error updating worker: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - Sian Roses</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group label i {
            color: var(--primary);
            width: 16px;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(231, 84, 128, 0.1);
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .alert-danger {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-header h1 {
            color: var(--primary);
            margin: 0;
            font-size: 1.75rem;
        }

        .section-header p {
            color: var(--text-secondary);
            margin: 0.25rem 0 0 0;
        }

        .btn-action {
            background-color: var(--secondary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: white;
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div>
                    <h1>Edit Employee Details</h1>
                    <p>Update employee information</p>
                </div>
                <a href="list.php" class="btn-action">
                    <i class="fas fa-arrow-left"></i>
                    Back to List
                </a>
            </div>

            <div class="form-container">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="payroll_number">
                                <i class="fas fa-id-card"></i>
                                Payroll Number
                            </label>
                            <input type="text" 
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($worker['payroll_number']); ?>" 
                                   readonly>
                        </div>

                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-user"></i>
                                Full Name
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($worker['name']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="department">
                                <i class="fas fa-building"></i>
                                Department
                            </label>
                            <input type="text" 
                                   id="department" 
                                   name="department" 
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($worker['department']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="role">
                                <i class="fas fa-briefcase"></i>
                                Role
                            </label>
                            <input type="text" 
                                   id="role" 
                                   name="role" 
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($worker['role']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i>
                                Phone Number
                            </label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($worker['phone']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                Email Address
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($worker['email']); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script src="../assets/js/menu.js"></script>
</body>
</html> 