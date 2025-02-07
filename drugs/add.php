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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $error = "Drug name is required";
    } else {
        try {
            // Check if drug already exists
            $stmt = $pdo->prepare("SELECT id FROM drugs WHERE name = ?");
            $stmt->execute([$name]);
            
            if ($stmt->fetch()) {
                $error = "Drug already exists";
            } else {
                // Add new drug
                $stmt = $pdo->prepare("INSERT INTO drugs (name) VALUES (?)");
                $stmt->execute([$name]);
                
                $success = "Drug added successfully";
                // Clear form
                $name = '';
            }
        } catch (PDOException $e) {
            $error = "Error adding drug";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Drug - Sian Roses</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-bar">
                <div class="welcome-message">
                    <h2>Add New Drug</h2>
                    <p>Add a new drug to the inventory</p>
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
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-pills"></i>
                                Drug Name
                            </label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                                   required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-add">
                                <i class="fas fa-plus-circle"></i>
                                Add Drug
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