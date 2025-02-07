<?php
session_start();
require_once '../config/database.php';
require_once 'calculate_leave_payment.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$medical_record_id = $_POST['medical_record_id'] ?? '';
$worker_id = $_POST['worker_id'] ?? '';
$days = $_POST['sick_leave_days'] ?? null;
$reason = $_POST['recommendation_reason'] ?? '';
$start_date = $_POST['start_date'] ?? date('Y-m-d');

// Validate required medical record and worker IDs
if (empty($medical_record_id) || empty($worker_id)) {
    echo json_encode(['success' => false, 'error' => 'Medical record and worker ID are required']);
    exit();
}

try {
    // Only process leave recommendation if days are specified
    if (!empty($days) && !empty($reason)) {
        // Calculate end date
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . ($days - 1) . ' days'));

        // Calculate payment recommendation
        $calculation = calculateLeavePayment($worker_id, $days, $start_date);

        if (isset($calculation['error'])) {
            echo json_encode(['success' => false, 'error' => $calculation['error']]);
            exit();
        }

        // Insert leave recommendation
        $stmt = $pdo->prepare("
            INSERT INTO sick_leave_recommendations 
            (medical_record_id, worker_id, days_recommended, payment_recommendation, reason, start_date, end_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $medical_record_id,
            $worker_id,
            $days,
            $calculation['payment_recommendation'],
            $reason . "\n\nSystem Note: " . $calculation['reason'],
            $start_date,
            $end_date
        ]);
    }

    // Always return success if we get here
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} 