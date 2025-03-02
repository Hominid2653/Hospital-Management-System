<?php
session_start();
require_once '../config/database.php';
require_once '../config/paths.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('login.php'));
    exit();
}

try {
    // Get drug usage statistics
    $stmt = $pdo->query("
        SELECT 
            d.name as drug_name,
            d.status,
            d.quantity as current_stock,
            d.reorder_level,
            COUNT(m.id) as times_prescribed,
            COUNT(DISTINCT m.payroll_number) as unique_patients
        FROM drugs d
        LEFT JOIN medical_history m ON m.treatment LIKE CONCAT('% ', d.name, ' %')
            OR m.treatment LIKE CONCAT(d.name, ' %')
            OR m.treatment LIKE CONCAT('% ', d.name)
            OR m.treatment = d.name
        GROUP BY d.id, d.name, d.status, d.quantity
        ORDER BY 
            CASE 
                WHEN d.quantity <= d.reorder_level THEN 0
                WHEN d.status = 'out_of_stock' THEN 1
                ELSE 2
            END,
            times_prescribed DESC
    ");

    // Debug the query
    if (!$stmt) {
        echo "Query failed: " . print_r($pdo->errorInfo(), true);
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
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="drug_usage_report.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($output, [
            'Drug Name',
            'Status',
            'Current Stock',
            'Times Prescribed',
            'Unique Patients'
        ]);
        
        // Add data
        foreach ($records as $record) {
            fputcsv($output, [
                $record['drug_name'],
                ucfirst(str_replace('_', ' ', $record['status'])),
                $record['current_stock'],
                $record['times_prescribed'],
                $record['unique_patients']
            ]);
        }
        
        fclose($output);
        exit;
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drug Usage Report - Sian Roses</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Use same styles as medical_history.php */
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

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
        }

        .status-badge.in_stock {
            background: rgba(46, 213, 115, 0.1);
            color: #2ed573;
        }

        .status-badge.low_stock {
            background: rgba(255, 171, 0, 0.1);
            color: #ffab00;
        }

        .status-badge.out_of_stock {
            background: rgba(255, 71, 87, 0.1);
            color: #ff4757;
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

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            padding: 1.5rem;
            border-radius: 10px;
            color: white;
        }

        .summary-card.warning {
            background: linear-gradient(135deg, #ffab00, #ffd54f);
        }

        .summary-card.danger {
            background: linear-gradient(135deg, #ff4757, #ff6b81);
        }

        .summary-card.info {
            background: linear-gradient(135deg, #2ed573, #7bed9f);
        }

        .summary-card h3 {
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .summary-card .count {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .summary-card p {
            margin: 0;
            opacity: 0.9;
        }

        .summary-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .summary-card li {
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-card li span {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .report-table tr.low-stock {
            background: rgba(255, 171, 0, 0.05);
        }

        .report-table tr.out-of-stock {
            background: rgba(255, 71, 87, 0.05);
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div>
                    <h1>Drug Usage Report</h1>
                    <p>Analysis of drug prescriptions and inventory status</p>
                </div>
                <a href="?export=true" class="btn-export">
                    <i class="fas fa-download"></i>
                    Export to CSV
                </a>
            </div>

            <div class="report-container">
                <?php if (empty($records)): ?>
                    <div class="no-records">
                        <i class="fas fa-info-circle"></i>
                        <p>No drug usage data available</p>
                    </div>
                <?php else: ?>
                    <div class="summary-cards">
                        <?php
                            $low_stock = array_filter($records, function($r) { 
                                return $r['quantity'] <= $r['reorder_level'] && $r['quantity'] > 0; 
                            });
                            $out_of_stock = array_filter($records, function($r) { 
                                return $r['status'] === 'out_of_stock'; 
                            });
                            $most_used = array_slice($records, 0, 5);
                        ?>
                        <div class="summary-card warning">
                            <h3>Low Stock Alert</h3>
                            <div class="count"><?php echo count($low_stock); ?></div>
                            <p>Drugs below reorder level</p>
                        </div>
                        <div class="summary-card danger">
                            <h3>Out of Stock</h3>
                            <div class="count"><?php echo count($out_of_stock); ?></div>
                            <p>Drugs need immediate restock</p>
                        </div>
                        <div class="summary-card info">
                            <h3>Most Prescribed</h3>
                            <ul>
                                <?php foreach(array_slice($most_used, 0, 3) as $drug): ?>
                                    <li><?php echo htmlspecialchars($drug['drug_name']); ?> 
                                        <span>(<?php echo $drug['times_prescribed']; ?> times)</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Drug Name</th>
                                <th>Status</th>
                                <th>Current Stock</th>
                                <th>Times Prescribed</th>
                                <th>Unique Patients</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr class="<?php 
                                echo $record['status'] === 'out_of_stock' ? 'out-of-stock' : 
                                    ($record['quantity'] <= $record['reorder_level'] ? 'low-stock' : ''); 
                            ?>">
                                <td><?php echo htmlspecialchars($record['drug_name']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $record['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($record['current_stock']); ?></td>
                                <td><?php echo $record['times_prescribed']; ?></td>
                                <td><?php echo $record['unique_patients']; ?></td>
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