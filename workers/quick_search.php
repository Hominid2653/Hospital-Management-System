<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    if (empty($search)) {
        header('Content-Type: application/json');
        echo json_encode(['workers' => []]);
        exit();
    }

    $query = "
        SELECT 
            w.*,
            MAX(m.visit_date) as last_visit
        FROM workers w
        LEFT JOIN medical_history m ON w.payroll_number = m.payroll_number
        WHERE (
            w.payroll_number LIKE :search 
            OR w.name LIKE :search 
            OR w.department LIKE :search
        )
        GROUP BY w.payroll_number 
        ORDER BY w.name ASC
        LIMIT 5
    ";

    $stmt = $pdo->prepare($query);
    $searchTerm = "%{$search}%";
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();

    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['workers' => $workers]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error occurred']);
} 