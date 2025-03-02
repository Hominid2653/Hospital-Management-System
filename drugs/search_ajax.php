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
        echo json_encode(['drugs' => []]);
        exit();
    }

    $query = "
        SELECT 
            id,
            name,
            status,
            created_at
        FROM drugs 
        WHERE name LIKE :search
        ORDER BY name ASC
    ";

    $stmt = $pdo->prepare($query);
    $searchTerm = "%{$search}%";
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();

    $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['drugs' => $drugs]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error occurred']);
} 