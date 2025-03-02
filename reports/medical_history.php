<?php
session_start();
require_once '../config/database.php';
require_once '../config/paths.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('login.php'));
    exit();
}

try {
    // Get medical history with worker details
    $stmt = $pdo->query("
        SELECT 
            m.*,
            w.name as worker_name,
            w.department,
            w.role,
            dc.name as disease_category,
            COALESCE(m.diagnosis, 'Not specified') as diagnosis,
            COALESCE(m.remarks, 'Not specified') as treatment
        FROM medical_history m
        LEFT JOIN workers w ON m.payroll_number = w.payroll_number
        LEFT JOIN disease_classifications dc ON m.disease_classification_id = dc.id
        ORDER BY m.visit_date DESC
    ");
    if (!$stmt) {
        throw new PDOException("Failed to execute query");
    }
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($records === false) {
        throw new PDOException("Failed to fetch results");
    }
    
    // Initialize empty array if no records found
    if (empty($records)) {
        $records = [];
    }

    // Handle Excel Export
    if (isset($_GET['export'])) {
        // Get category summary
        $category_summary = [];
        foreach ($records as $record) {
            $category = $record['disease_category'] ?: 'Uncategorized';
            if (!isset($category_summary[$category])) {
                $category_summary[$category] = 0;
            }
            $category_summary[$category]++;
        }
        arsort($category_summary); // Sort by count in descending order

        // Set headers for XLS download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="medical_history_report.xls"');
        header('Cache-Control: max-age=0');
        
        // Start Excel document with styles
        echo '
        <style>
            table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            th, td { border: 1px solid #000; padding: 5px; }
            th { background-color: #f0f0f0; }
            .summary-title { font-size: 14px; font-weight: bold; margin-top: 20px; }
        </style>';
        
        // Disease Categories Summary Table
        echo '<div class="summary-title">Disease Categories Summary</div>';
        echo '<table border="1">
            <tr>
                <th>Disease Category</th>
                <th>Number of Cases</th>
                <th>Percentage</th>
            </tr>';
        
        $total_cases = array_sum($category_summary);
        foreach ($category_summary as $category => $count) {
            $percentage = round(($count / $total_cases) * 100, 1);
            echo '<tr>
                <td>' . htmlspecialchars($category) . '</td>
                <td>' . $count . '</td>
                <td>' . $percentage . '%</td>
            </tr>';
        }
        echo '</table>';
        
        // Add spacing between tables
        echo '<br><br>';
        
        // Detailed Records Title
        echo '<div class="summary-title">Detailed Medical Records</div>';
        
        // Main records table
        echo '<table border="1">';
        
        // Add headers
        echo '<tr>
            <th>Date</th>
            <th>Worker Name</th>
            <th>Payroll Number</th>
            <th>Department</th>
            <th>Role</th>
            <th>Disease Category</th>
            <th>Diagnosis</th>
            <th>Treatment</th>
        </tr>';
         
        // Add data
        foreach ($records as $record) {
            echo '<tr>
                <td>' . date('Y-m-d', strtotime($record['visit_date'])) . '</td>
                <td>' . htmlspecialchars($record['worker_name']) . '</td>
                <td>' . htmlspecialchars($record['payroll_number']) . '</td>
                <td>' . htmlspecialchars($record['department']) . '</td>
                <td>' . htmlspecialchars($record['role']) . '</td>
                <td>' . htmlspecialchars($record['disease_category'] ?: 'Uncategorized') . '</td>
                <td>' . htmlspecialchars($record['diagnosis'] ?: 'Not specified') . '</td>
                <td>' . htmlspecialchars($record['treatment'] ?: 'Not specified') . '</td>
            </tr>';
        }
        
        echo '</table>';
        exit;
    }

} catch (PDOException $e) {
    // More detailed error message
    $error = "Database error: " . $e->getMessage() . 
             "\nSQL State: " . $e->getCode() . 
             "\nTrace: " . $e->getTraceAsString();
    // Debug: Print the error
    echo "<!-- Debug: " . htmlspecialchars($error) . " -->";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History Report - Sian Roses</title>
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

        .report-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .report-table th,
        .report-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .report-table th {
            background: rgba(231, 84, 128, 0.05);
            color: var(--primary);
            font-weight: 500;
        }

        .report-table tr:hover {
            background: rgba(231, 84, 128, 0.02);
        }

        .report-table td:empty::before {
            content: 'Not specified';
            color: #999;
            font-style: italic;
        }

        .btn-export {
            background: var(--secondary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .no-records {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .no-records i {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-records p {
            margin: 0;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div>
                    <h1>Medical History Report</h1>
                    <p>Comprehensive medical visit records</p>
                </div>
                <a href="?export=true" class="btn-export">
                    <i class="fas fa-download"></i>
                    Export to Excel
                </a>
            </div>

            <div class="report-container">
                <?php if (empty($records)): ?>
                    <div class="no-records">
                        <i class="fas fa-info-circle"></i>
                        <p>No medical history records available</p>
                    </div>
                <?php else: ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Worker Name</th>
                            <th>Payroll No.</th>
                            <th>Department</th>
                            <th>Category</th>
                            <th>Diagnosis</th>
                            <th>Treatment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($record['visit_date'])); ?></td>
                            <td><?php echo htmlspecialchars($record['worker_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['payroll_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['department']); ?></td>
                            <td><?php echo htmlspecialchars($record['disease_category'] ?: 'Uncategorized'); ?></td>
                            <td><?php echo htmlspecialchars($record['diagnosis'] ?: 'Not specified'); ?></td>
                            <td><?php echo htmlspecialchars($record['treatment'] ?: 'Not specified'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="<?php echo url('assets/js/menu.js'); ?>"></script>
</body>
</html> 