<?php
session_start();
require_once '../config/database.php';
require_once '../config/paths.php';

// If accessed directly without AJAX request, redirect to search page
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    header("Location: " . url('workers/search_workers.php'));
    exit();
}

// Prevent any HTML output before JSON response
ob_clean();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit();
}

try {
    // Get search term
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';

    // Base query
    $query = "SELECT * FROM workers WHERE 1=1";
    $params = [];

    // Add search condition if search term exists
    if (!empty($search)) {
        $query .= " AND (
            name LIKE ? OR 
            payroll_number LIKE ? OR 
            department LIKE ? OR 
            role LIKE ?
        )";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }

    // Add type filter
    if ($type === 'management') {
        $query .= " AND department = 'Management'";
    } elseif ($type === 'farm') {
        $query .= " AND department != 'Management'";
    }

    // Order by name
    $query .= " ORDER BY name ASC";

    // Prepare and execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    // Fetch results
    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return success response
    echo json_encode([
        'success' => true,
        'workers' => $workers
    ]);

} catch (PDOException $e) {
    // Log the error (but don't expose details to client)
    error_log("Search error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred'
    ]);
}

// Ensure no other output
exit(); 