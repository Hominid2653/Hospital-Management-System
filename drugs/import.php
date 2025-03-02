<?php
session_start();
require_once '../config/database.php';
require_once '../config/paths.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('login.php'));
    exit();
}

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        $file = $_FILES['csv_file'];
        
        // Basic validation
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed');
        }
        
        if ($file['type'] !== 'text/csv') {
            throw new Exception('Please upload a CSV file');
        }

        // Process CSV file
        $handle = fopen($file['tmp_name'], 'r');
        $header = fgetcsv($handle); // Skip header row
        
        $pdo->beginTransaction();
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $stmt = $pdo->prepare("
                INSERT INTO drugs (name, status) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status)
            ");
            $stmt->execute([$data[0], $data[1] ?? 'in_stock']);
        }
        
        fclose($handle);
        $pdo->commit();
        $success = 'Drugs imported successfully';
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Import failed: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Drugs - Sian Roses</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .main-content {
            padding: 2rem;
            background: none;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-header-left h1 {
            color: var(--primary);
            font-size: 1.75rem;
            margin: 0;
        }

        .section-header-left p {
            color: var(--text-secondary);
            margin: 0.5rem 0 0 0;
        }

        .import-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        .import-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .file-upload {
            border: 2px dashed var(--border);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload:hover {
            border-color: var(--primary);
            background: rgba(231, 84, 128, 0.05);
        }

        .file-upload i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .file-upload p {
            color: var(--text-secondary);
            margin: 0.5rem 0;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .template-link {
            text-align: center;
            margin-top: 1rem;
        }

        .template-link a {
            color: var(--primary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .template-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: rgba(46, 213, 115, 0.1);
            color: #2ed573;
            border: 1px solid rgba(46, 213, 115, 0.2);
        }

        .alert-error {
            background: rgba(255, 71, 87, 0.1);
            color: #ff4757;
            border: 1px solid rgba(255, 71, 87, 0.2);
        }

        .instructions {
            margin-top: 2rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .instructions h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .instructions ul {
            padding-left: 1.5rem;
            margin-top: 0.5rem;
        }

        .instructions li {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div class="section-header-left">
                    <h1>Import Drugs</h1>
                    <p>Import drug inventory from CSV file</p>
                </div>
            </div>

            <div class="import-container">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form class="import-form" method="POST" enctype="multipart/form-data">
                    <label class="file-upload" for="csv_file">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag and drop your CSV file here</p>
                        <p>or click to browse</p>
                        <input type="file" 
                               id="csv_file" 
                               name="csv_file" 
                               accept=".csv"
                               onchange="updateFileName(this)">
                        <p id="fileName" class="selected-file"></p>
                    </label>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-file-import"></i>
                        Import Drugs
                    </button>
                </form>

                <div class="template-link">
                    <a href="<?php echo url('drugs/template.csv'); ?>" download>
                        <i class="fas fa-download"></i>
                        Download CSV Template
                    </a>
                </div>

                <div class="instructions">
                    <h3>Instructions</h3>
                    <ul>
                        <li>Download the CSV template using the link above</li>
                        <li>Fill in the drug details following the template format</li>
                        <li>Save the file and upload it using the form above</li>
                        <li>The system will process the file and import the drugs</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo url('assets/js/menu.js'); ?>"></script>
    <script>
        function updateFileName(input) {
            const fileName = document.getElementById('fileName');
            if (input.files.length > 0) {
                fileName.textContent = input.files[0].name;
            } else {
                fileName.textContent = '';
            }
        }

        // Drag and drop functionality
        const dropZone = document.querySelector('.file-upload');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('highlight');
        }

        function unhighlight(e) {
            dropZone.classList.remove('highlight');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            const fileInput = document.getElementById('csv_file');
            
            fileInput.files = files;
            updateFileName(fileInput);
        }
    </script>
</body>
</html> 