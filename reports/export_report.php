<?php
session_start();
require_once '../config/database.php';

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get filter parameters
$period = $_GET['period'] ?? 'month';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

// Log the received parameters
error_log("Export Parameters - Period: $period, Year: $year, Month: $month");

try {
    // Very simple query to get just diseases and total cases
    $sql = "
        SELECT 
            dc.name as disease,
            COUNT(*) as total_cases
        FROM medical_history m
        JOIN disease_classifications dc ON m.disease_classification_id = dc.id
        GROUP BY dc.id, dc.name
        ORDER BY total_cases DESC
    ";

    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clear output buffer
    if (ob_get_level()) ob_end_clean();

    // Set download headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="disease_statistics.csv"');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Write CSV header
    fputcsv($output, ['Disease', 'Total Cases']);

    // Write data rows
    foreach ($results as $row) {
        fputcsv($output, [
            $row['disease'],
            $row['total_cases']
        ]);
    }

    fclose($output);
    exit();

} catch (Exception $e) {
    // Log the error
    error_log("Export Error: " . $e->getMessage());
    $_SESSION['error'] = "Export failed: " . $e->getMessage();
    header("Location: index.php");
    exit();
} 