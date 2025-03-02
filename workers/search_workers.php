<?php
require_once '../config/database.php';

if (!isset($_GET['query'])) {
    exit('No search query provided');
}

$query = trim($_GET['query']);

try {
    $sql = "SELECT * FROM workers 
            WHERE payroll_number LIKE :query 
            OR name LIKE :query 
            OR department LIKE :query 
            OR role LIKE :query
            LIMIT 20";
            
    $stmt = $pdo->prepare($sql);
    $searchTerm = "%{$query}%";
    $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    
    $workers = $stmt->fetchAll();
    
    if (count($workers) === 0) {
        echo '<div class="no-results">
                <i class="fas fa-search"></i>
                <p>No workers found matching your search.</p>
              </div>';
        exit;
    }
    
    // Return the results as HTML
    foreach ($workers as $worker) {
        echo '<div class="worker-card">
                <div class="worker-info">
                    <h3>' . htmlspecialchars($worker['name']) . '</h3>
                    <p class="payroll">Payroll: ' . htmlspecialchars($worker['payroll_number']) . '</p>
                    <p class="department">' . htmlspecialchars($worker['department']) . '</p>
                    <p class="role">' . htmlspecialchars($worker['role']) . '</p>
                </div>
                <div class="worker-actions">
                    <a href="view.php?id=' . $worker['id'] . '" class="btn-view">
                        <i class="fas fa-eye"></i>
                        View
                    </a>
                    <a href="edit.php?id=' . $worker['id'] . '" class="btn-edit">
                        <i class="fas fa-edit"></i>
                        Edit
                    </a>
                </div>
            </div>';
    }
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo '<div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            An error occurred while searching. Please try again.
          </div>';
}
?> 