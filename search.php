<?php
// Prevent any output before headers
ob_start();

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'config/paths.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    ob_end_clean();
    echo json_encode(['error' => true, 'message' => 'Unauthorized']);
    exit;
}

$query = $_GET['q'] ?? '';
$results = [];

if (strlen($query) >= 2) {
    try {
        // Test database connection
        if (!isset($pdo)) {
            throw new Exception("Database connection not established");
        }

        // Use PDO directly for now instead of Database class
        $stmt = $pdo->prepare("
            SELECT payroll_number, name, department 
            FROM workers 
            WHERE name LIKE ? OR payroll_number LIKE ?
            LIMIT 5
        ");
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm]);
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($workers as $worker) {
            $results[] = [
                'type' => 'worker',
                'title' => htmlspecialchars($worker['name']),
                'subtitle' => "ID: {$worker['payroll_number']} • {$worker['department']}",
                'url' => url("workers/view.php?id={$worker['payroll_number']}")
            ];
        }
        
        // Search medical records
        $stmt = $pdo->prepare("
            SELECT m.id, m.diagnosis, m.visit_date, w.name 
            FROM medical_history m 
            JOIN workers w ON m.payroll_number = w.payroll_number
            WHERE m.diagnosis LIKE ? OR w.name LIKE ?
            ORDER BY m.visit_date DESC
            LIMIT 5
        ");
        $stmt->execute([$searchTerm, $searchTerm]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($records as $record) {
            $results[] = [
                'type' => 'medical',
                'title' => htmlspecialchars($record['name']),
                'subtitle' => substr(htmlspecialchars($record['diagnosis']), 0, 50) . '...',
                'url' => url("medical/view.php?id={$record['id']}")
            ];
        }
        
        // Search drugs
        $stmt = $pdo->prepare("
            SELECT name, status 
            FROM drugs 
            WHERE name LIKE ?
            LIMIT 5
        ");
        $stmt->execute([$searchTerm]);
        $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($drugs as $drug) {
            $results[] = [
                'type' => 'drug',
                'title' => htmlspecialchars($drug['name']),
                'subtitle' => "Status: " . ucfirst(str_replace('_', ' ', $drug['status'])),
                'url' => url("drugs/list.php")
            ];
        }
        
    } catch (Exception $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Search error: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Set proper headers
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

ob_end_clean();
echo json_encode(['results' => $results, 'query' => $query]); 