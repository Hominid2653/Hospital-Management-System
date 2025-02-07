<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$base_url = '../';

try {
    $stmt = $pdo->query("
        SELECT * FROM drugs 
        ORDER BY name ASC
    ");
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
    <title>Drugs List - Sian Roses</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-bar">
                <div class="welcome-message">
                    <h2>Drugs List</h2>
                    <p>Manage medical drugs inventory</p>
                </div>
                <div class="action-buttons">
                    <a href="add.php" class="btn-add">
                        <i class="fas fa-plus"></i>
                        Add New Drug
                    </a>
                    <a href="import.php" class="btn-import">
                        <i class="fas fa-file-import"></i>
                        Import Drugs
                    </a>
                    <a href="export.php" class="btn-export" title="Export to CSV">
                        <i class="fas fa-file-csv"></i>
                        Export List
                    </a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="workers-list card">
                    <div class="list-header">
                        <div class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="drugSearch" 
                                   placeholder="Search drugs..."
                                   onkeyup="filterDrugs()">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Drug Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($drugs)): ?>
                                    <tr>
                                        <td colspan="2" class="no-records">
                                            <i class="fas fa-pills"></i>
                                            <p>No drugs registered</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($drugs as $drug): ?>
                                        <tr>
                                            <td class="drug-name <?php echo $drug['status']; ?>">
                                                <?php echo htmlspecialchars($drug['name']); ?>
                                                <?php if ($drug['status'] === 'low_stock'): ?>
                                                    <span class="status-badge low">Low Stock</span>
                                                <?php elseif ($drug['status'] === 'out_of_stock'): ?>
                                                    <span class="status-badge out">Out of Stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="actions">
                                                <?php if ($drug['status'] === 'in_stock'): ?>
                                                    <button onclick="updateStatus(<?php echo $drug['id']; ?>, 'low_stock')" 
                                                            class="btn-icon btn-warning" title="Mark as Low Stock">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                    </button>
                                                    <button onclick="updateStatus(<?php echo $drug['id']; ?>, 'out_of_stock')" 
                                                            class="btn-icon btn-danger" title="Mark as Out of Stock">
                                                        <i class="fas fa-times-circle"></i>
                                                    </button>
                                                <?php elseif ($drug['status'] === 'low_stock'): ?>
                                                    <button onclick="updateStatus(<?php echo $drug['id']; ?>, 'in_stock')" 
                                                            class="btn-icon btn-success" title="Mark as In Stock">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                    <button onclick="updateStatus(<?php echo $drug['id']; ?>, 'out_of_stock')" 
                                                            class="btn-icon btn-danger" title="Mark as Out of Stock">
                                                        <i class="fas fa-times-circle"></i>
                                                    </button>
                                                <?php elseif ($drug['status'] === 'out_of_stock'): ?>
                                                    <button onclick="updateStatus(<?php echo $drug['id']; ?>, 'in_stock')" 
                                                            class="btn-icon btn-success" title="Mark as In Stock">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <a href="edit.php?id=<?php echo $drug['id']; ?>" 
                                                   class="btn-edit" title="Edit Drug">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/menu.js"></script>
    <script src="js/drug-actions.js"></script>
    <script>
    function filterDrugs() {
        const searchInput = document.getElementById('drugSearch');
        const filter = searchInput.value.toLowerCase();
        const table = document.querySelector('.workers-list table');
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const drugName = row.cells[0]?.textContent || '';
            
            const matches = drugName.toLowerCase().includes(filter);
            
            row.style.display = matches ? '' : 'none';
        }
    }
    </script>
</body>
</html> 