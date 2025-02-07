<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$worker_id = $_GET['id'] ?? '';

if (empty($worker_id)) {
    $_SESSION['error'] = "Worker ID is required";
    header("Location: list.php");
    exit();
}

try {
    // Get worker details
    $stmt = $pdo->prepare("
        SELECT name, payroll_number, department 
        FROM workers 
        WHERE payroll_number = ?
    ");
    $stmt->execute([$worker_id]);
    $worker = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get medical history with prescriptions
    $stmt = $pdo->prepare("
        SELECT 
            m.visit_date,
            m.temperature,
            m.blood_pressure,
            m.diagnosis,
            dc.name as disease_classification,
            GROUP_CONCAT(CONCAT(pd.drug_name, ' - ', pd.dosage) SEPARATOR '| ') as prescriptions,
            COALESCE(slr.days_recommended, 0) as sick_leave_days,
            slr.start_date as leave_start_date,
            slr.recommendation_reason as leave_reason,
            slr.payment_recommendation
        FROM medical_history m
        LEFT JOIN disease_classifications dc ON m.disease_classification_id = dc.id
        LEFT JOIN prescribed_drugs pd ON m.id = pd.medical_history_id
        LEFT JOIN sick_leave_recommendations slr ON m.id = slr.medical_history_id
        WHERE m.payroll_number = ?
        GROUP BY m.id
        ORDER BY m.visit_date DESC
    ");
    $stmt->execute([$worker_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clear any output buffering
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="medical_history_' . $worker['payroll_number'] . '_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create output handle
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Write worker info
    fputcsv($output, ['Worker Medical History Report']);
    fputcsv($output, []);
    fputcsv($output, ['Name:', $worker['name']]);
    fputcsv($output, ['Payroll Number:', $worker['payroll_number']]);
    fputcsv($output, ['Department:', $worker['department']]);
    fputcsv($output, []);
    
    // Write headers
    fputcsv($output, [
        'Visit Date',
        'Temperature',
        'Blood Pressure',
        'Disease Classification',
        'Diagnosis',
        'Prescriptions',
        'Sick Leave Days',
        'Leave Start Date',
        'Leave Payment',
        'Leave Reason'
    ]);

    // Write data
    foreach ($history as $record) {
        fputcsv($output, [
            date('Y-m-d', strtotime($record['visit_date'])),
            $record['temperature'],
            $record['blood_pressure'],
            $record['disease_classification'],
            $record['diagnosis'],
            $record['prescriptions'],
            $record['sick_leave_days'],
            $record['leave_start_date'],
            $record['payment_recommendation'],
            $record['leave_reason']
        ]);
    }

    fclose($output);
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Error exporting medical history: " . $e->getMessage();
    header("Location: view.php?id=" . $worker_id);
    exit();
} 