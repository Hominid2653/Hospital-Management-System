<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get search term
$search = $_GET['search'] ?? '';

if (empty($search)) {
    header('Content-Type: application/json');
    echo json_encode(['workers' => []]);
    exit();
}

try {
    // Search in both payroll_number and name
    $stmt = $pdo->prepare("
        SELECT payroll_number, name, department 
        FROM workers 
        WHERE payroll_number LIKE ? 
        OR name LIKE ? 
        LIMIT 5
    ");
    
    $searchTerm = "%{$search}%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['workers' => $workers]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error performing search']);
}
?> 