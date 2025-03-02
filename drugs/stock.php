<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$base_url = '../';

try {
    // Get drugs grouped by status
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count, GROUP_CONCAT(name) as drugs
        FROM drugs
        GROUP BY status
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all drugs
    $stmt = $pdo->query("SELECT * FROM drugs ORDER BY status, name");
    $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching drugs: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drug Stock Overview - Sian Roses</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style>
    .main-content {
        padding: 2rem;
        background: transparent;
    }
</style>

<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div>
                    <h1>Drug Stock Management</h1>
                    <p>Update and manage drug stock levels</p>
                </div>
                <a href="list.php" class="btn-action secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to List
                </a>
            </div>

            <div class="content-wrapper">
                <!-- Stats Cards -->
                <div class="stats-grid" id="statsGrid">
                    <div class="stat-card in-stock" onclick="showDrugDetails('in_stock')">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>In Stock</h3>
                            <p class="stat-count">
                                <?php 
                                $in_stock = array_filter($stats, function($s) { 
                                    return $s['status'] === 'in_stock'; 
                                });
                                echo !empty($in_stock) ? current($in_stock)['count'] : 0;
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="stat-card low-stock" onclick="showDrugDetails('low_stock')">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Low Stock</h3>
                            <p class="stat-count">
                                <?php 
                                $low_stock = array_filter($stats, function($s) { 
                                    return $s['status'] === 'low_stock'; 
                                });
                                echo !empty($low_stock) ? current($low_stock)['count'] : 0;
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="stat-card out-of-stock" onclick="showDrugDetails('out_of_stock')">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Out of Stock</h3>
                            <p class="stat-count">
                                <?php 
                                $out_stock = array_filter($stats, function($s) { 
                                    return $s['status'] === 'out_of_stock'; 
                                });
                                echo !empty($out_stock) ? current($out_stock)['count'] : 0;
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stock Lists -->
                <div class="stock-lists">
                    <!-- In Stock -->
                    <div class="stock-section card in-stock-section">
                        <div class="card-header">
                            <h3><i class="fas fa-check-circle"></i> In Stock Drugs</h3>
                        </div>
                        <div class="card-body">
                            <ul class="drug-list">
                                <?php foreach ($drugs as $drug): ?>
                                    <?php if ($drug['status'] === 'in_stock'): ?>
                                        <li>
                                            <span class="drug-name"><?php echo htmlspecialchars($drug['name']); ?></span>
                                            <div class="actions">
                                                <button onclick="updateStatus(<?php echo $drug['id']; ?>, 'low_stock')" 
                                                        class="btn-icon btn-warning" title="Mark as Low Stock">
                                                    <i class="fas fa-exclamation-circle"></i>
                                                </button>
                                                <button onclick="updateStatus(<?php echo $drug['id']; ?>, 'out_of_stock')" 
                                                        class="btn-icon btn-danger" title="Mark as Out of Stock">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Low Stock -->
                    <div class="stock-section card low-stock-section">
                        <div class="card-header">
                            <h3><i class="fas fa-exclamation-circle"></i> Low Stock Drugs</h3>
                        </div>
                        <div class="card-body">
                            <ul class="drug-list">
                                <?php foreach ($drugs as $drug): ?>
                                    <?php if ($drug['status'] === 'low_stock'): ?>
                                        <li class="low-stock">
                                            <span class="drug-name"><?php echo htmlspecialchars($drug['name']); ?></span>
                                            <div class="actions">
                                                <button onclick="updateStatus(<?php echo $drug['id']; ?>, 'in_stock')" 
                                                        class="btn-icon btn-success" title="Mark as In Stock">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                                <button onclick="updateStatus(<?php echo $drug['id']; ?>, 'out_of_stock')" 
                                                        class="btn-icon btn-danger" title="Mark as Out of Stock">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Out of Stock -->
                    <div class="stock-section card out-of-stock-section">
                        <div class="card-header">
                            <h3><i class="fas fa-times-circle"></i> Out of Stock Drugs</h3>
                        </div>
                        <div class="card-body">
                            <ul class="drug-list">
                                <?php foreach ($drugs as $drug): ?>
                                    <?php if ($drug['status'] === 'out_of_stock'): ?>
                                        <li class="out-of-stock">
                                            <span class="drug-name"><?php echo htmlspecialchars($drug['name']); ?></span>
                                            <div class="actions">
                                                <button onclick="updateStatus(<?php echo $drug['id']; ?>, 'in_stock')" 
                                                        class="btn-icon btn-success" title="Mark as In Stock">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/menu.js"></script>
    <script src="js/drug-actions.js"></script>
    <div id="drugModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-pills"></i> <span id="modalTitle"></span></h3>
                <button onclick="closeModal()" class="close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="drugList"></div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all stat cards
        const statCards = document.querySelectorAll('.stat-card');
        
        // Add click event listeners to each card
        statCards.forEach(card => {
            card.addEventListener('click', function() {
                const status = this.classList.contains('in-stock') ? 'in_stock' : 
                              this.classList.contains('low-stock') ? 'low_stock' : 'out_of_stock';
                showDrugDetails(status);
            });
        });
    });

    function showDrugDetails(status) {
        const modal = document.getElementById('drugModal');
        const modalTitle = document.getElementById('modalTitle');
        const drugList = document.getElementById('drugList');
        
        if (!modal || !modalTitle || !drugList) {
            console.error('Required modal elements not found');
            return;
        }

        // Set modal title based on status
        switch(status) {
            case 'in_stock':
                modalTitle.innerHTML = 'In Stock Drugs';
                break;
            case 'low_stock':
                modalTitle.innerHTML = 'Low Stock Drugs';
                break;
            case 'out_of_stock':
                modalTitle.innerHTML = 'Out of Stock Drugs';
                break;
        }
        
        // Get drugs with matching status
        const drugs = <?php echo json_encode($drugs); ?>;
        if (!drugs) {
            console.error('No drugs data available');
            return;
        }

        const filteredDrugs = drugs.filter(drug => drug.status === status);
        
        // Generate drug list HTML
        let html = '<ul class="modal-drug-list">';
        if (filteredDrugs.length === 0) {
            html += '<li class="no-drugs">No drugs found</li>';
        } else {
            filteredDrugs.forEach(drug => {
                html += `
                    <li class="drug-item ${status}">
                        <span class="drug-name">${drug.name}</span>
                        <div class="actions">
                            ${getActionButtons(drug.id, status)}
                        </div>
                    </li>
                `;
            });
        }
        html += '</ul>';
        
        drugList.innerHTML = html;
        modal.classList.add('show');
    }

    function getActionButtons(drugId, status) {
        switch(status) {
            case 'in_stock':
                return `
                    <button onclick="updateStatus(${drugId}, 'low_stock')" class="btn-icon btn-warning" title="Mark as Low Stock">
                        <i class="fas fa-exclamation-circle"></i>
                    </button>
                    <button onclick="updateStatus(${drugId}, 'out_of_stock')" class="btn-icon btn-danger" title="Mark as Out of Stock">
                        <i class="fas fa-times-circle"></i>
                    </button>
                `;
            case 'low_stock':
                return `
                    <button onclick="updateStatus(${drugId}, 'in_stock')" class="btn-icon btn-success" title="Mark as In Stock">
                        <i class="fas fa-check-circle"></i>
                    </button>
                    <button onclick="updateStatus(${drugId}, 'out_of_stock')" class="btn-icon btn-danger" title="Mark as Out of Stock">
                        <i class="fas fa-times-circle"></i>
                    </button>
                `;
            case 'out_of_stock':
                return `
                    <button onclick="updateStatus(${drugId}, 'in_stock')" class="btn-icon btn-success" title="Mark as In Stock">
                        <i class="fas fa-check-circle"></i>
                    </button>
                `;
        }
    }

    function closeModal() {
        const modal = document.getElementById('drugModal');
        if (modal) {
            modal.classList.remove('show');
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('drugModal');
        if (event.target === modal && modal.classList.contains('show')) {
            closeModal();
        }
    }

    // Prevent modal from closing when clicking inside
    document.querySelector('.modal-content').addEventListener('click', function(event) {
        event.stopPropagation();
    });
    </script>

    <style>
    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-left: auto;
    }

    .btn-export {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background-color: #27ae60;
        color: white;
        border: none;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.9rem;
        transition: background-color 0.3s;
        cursor: pointer;
    }

    .btn-export:hover {
        background-color: #219a52;
    }

    .btn-export i {
        font-size: 1rem;
    }

    /* Add these styles to your existing <style> section */
    .btn-action {
        background-color: var(--primary);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-action.secondary {
        background-color: var(--secondary);
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        color: white;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .section-header h1 {
        color: var(--primary);
        margin: 0;
        font-size: 1.75rem;
    }

    .section-header p {
        color: var(--text-secondary);
        margin: 0.25rem 0 0 0;
    }
    </style>
    <script src="../assets/js/menu.js"></script>
</body>
</html> 