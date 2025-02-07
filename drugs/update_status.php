<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    $valid_statuses = ['in_stock', 'low_stock', 'out_of_stock'];
    
    if (empty($id) || !in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE drugs SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} 