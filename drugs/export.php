<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

try {
    // Get all drugs with their status, ordered by status priority
    $stmt = $pdo->query("
        SELECT name, 
        CASE 
            WHEN status = 'in_stock' THEN 'In Stock'
            WHEN status = 'low_stock' THEN 'Low Stock'
            WHEN status = 'out_of_stock' THEN 'Out of Stock'
            ELSE 'Unknown'
        END as status,
        CASE 
            WHEN status = 'out_of_stock' THEN 1
            WHEN status = 'low_stock' THEN 2
            WHEN status = 'in_stock' THEN 3
            ELSE 4
        END as status_priority
        FROM drugs 
        ORDER BY status_priority ASC, name ASC
    ");
    $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clear any output buffering
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="drugs_list_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create output handle
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Write headers
    fputcsv($output, ['Drug Name', 'Stock Status']);

    // Write data
    foreach ($drugs as $drug) {
        fputcsv($output, [
            $drug['name'],
            $drug['status']
        ]);
    }

    fclose($output);
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Error exporting drugs: " . $e->getMessage();
    header("Location: list.php");
    exit();
} 