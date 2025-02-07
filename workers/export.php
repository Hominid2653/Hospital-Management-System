<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="workers_records_' . date('Y-m-d') . '.xls"');

// Create output stream
$output = fopen('php://output', 'w');

// Start HTML table for Excel
echo '
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<table border="1">
    <tr>
        <th>Payroll Number</th>
        <th>Name</th>
        <th>Department</th>
        <th>Last Visit Date</th>
        <th>Diagnosis</th>
    </tr>';

try {
    // Get all workers with their latest medical record
    $stmt = $pdo->query("
        SELECT 
            w.payroll_number,
            w.name,
            w.department,
            MAX(m.visit_date) as last_visit,
            m.diagnosis
        FROM workers w
        LEFT JOIN medical_history m ON w.payroll_number = m.payroll_number
        GROUP BY w.payroll_number
        ORDER BY w.name
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['payroll_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['department']) . "</td>";
        echo "<td>" . ($row['last_visit'] ? date('Y-m-d', strtotime($row['last_visit'])) : 'No visits') . "</td>";
        echo "<td>" . htmlspecialchars($row['diagnosis'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
} catch (PDOException $e) {
    die("Error exporting data");
}

echo '</table></body></html>';
exit(); 