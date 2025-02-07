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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($file_ext == 'csv') {
        try {
            $handle = fopen($file['tmp_name'], 'r');
            
            // Skip header row
            fgetcsv($handle);
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO workers (payroll_number, name, department) VALUES (?, ?, ?)");
            $successCount = 0;
            $errors = [];
            
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row[0])) continue; // Skip empty rows
                
                try {
                    $stmt->execute([
                        trim($row[0]), // payroll_number
                        trim($row[1]), // name
                        trim($row[2])  // department
                    ]);
                    $successCount++;
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // Duplicate entry
                        $errors[] = "Payroll number {$row[0]} already exists";
                    } else {
                        $errors[] = "Error adding worker {$row[0]}: " . $e->getMessage();
                    }
                }
            }
            
            fclose($handle);
            
            if (count($errors) == 0) {
                $pdo->commit();
                $success = "Successfully imported $successCount workers";
            } else {
                $pdo->rollBack();
                $error = "Import failed:<br>" . implode("<br>", $errors);
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error processing file: " . $e->getMessage();
        }
    } else {
        $error = "Please upload a CSV file";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Workers - Sian Roses</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-bar">
                <div class="welcome-message">
                    <h2>Import Workers</h2>
                    <p>Upload CSV file with worker records</p>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="import-container">
                    <?php if ($error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-file-import"></i> Import Workers</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" class="styled-form">
                                <div class="form-group">
                                    <label for="csv_file">
                                        <i class="fas fa-file-csv"></i>
                                        Select CSV File
                                    </label>
                                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                                </div>

                                <div class="template-download">
                                    <p><i class="fas fa-download"></i> Download template:</p>
                                    <a href="template.csv" class="btn-secondary">
                                        <i class="fas fa-file-csv"></i>
                                        CSV Template
                                    </a>
                                </div>

                                <button type="submit" class="btn-import">
                                    <i class="fas fa-upload"></i>
                                    Import Workers
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/menu.js"></script>
</body>
</html> 