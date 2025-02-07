<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$period = $_GET['period'] ?? 'month';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

try {
    // Build the date condition based on period
    $dateCondition = match($period) {
        'month' => "AND YEAR(m.visit_date) = ? AND MONTH(m.visit_date) = ?",
        'year' => "AND YEAR(m.visit_date) = ?",
        'all' => "",
    };

    $sql = "
        SELECT 
            dc.name as classification,
            COUNT(*) as case_count,
            COUNT(DISTINCT m.payroll_number) as worker_count
        FROM medical_history m
        JOIN disease_classifications dc ON m.disease_classification_id = dc.id
        WHERE 1=1 $dateCondition
        GROUP BY dc.id, dc.name
        ORDER BY case_count DESC
    ";

    $stmt = $pdo->prepare($sql);
    
    // Bind parameters based on period
    match($period) {
        'month' => $stmt->execute([$year, $month]),
        'year' => $stmt->execute([$year]),
        'all' => $stmt->execute(),
    };

    $statistics = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error generating report";
}
?>

<!-- Add HTML for displaying the report with charts using Chart.js --> 