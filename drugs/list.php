<?php
session_start();
require_once '../config/database.php';
require_once '../config/paths.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('login.php'));
    exit();
}

try {
    // First, check if status column exists and add it if it doesn't
    $columns = $pdo->query("SHOW COLUMNS FROM drugs")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('status', $columns)) {
        $pdo->exec("ALTER TABLE drugs ADD COLUMN status ENUM('in_stock', 'out_of_stock', 'low_stock') DEFAULT 'in_stock' AFTER name");
    }

    // Now fetch the drugs with all columns
    $stmt = $pdo->query("
        SELECT 
            id,
            name,
            status,
            created_at
        FROM drugs 
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
    <title>Medical Supplies - Sian Roses</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .main-content {
            padding: 2rem;
            background: none;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-header-left h1 {
            color: var(--primary);
            font-size: 1.75rem;
            margin: 0;
        }

        .section-header-left p {
            color: var(--text-secondary);
            margin: 0.5rem 0 0 0;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-action {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-action.secondary {
            background: var(--secondary);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .drugs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .drug-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .drug-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .drug-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .drug-name {
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 500;
            margin: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-badge.in_stock {
            background: rgba(46, 213, 115, 0.1);
            color: #2ed573;
        }

        .status-badge.low_stock {
            background: rgba(255, 171, 0, 0.1);
            color: #ffab00;
        }

        .status-badge.out_of_stock {
            background: rgba(255, 71, 87, 0.1);
            color: #ff4757;
        }

        .drug-details {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .drug-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-card-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-edit {
            background: var(--secondary);
        }

        .btn-delete {
            background: #ff4757;
        }

        .btn-card-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .search-box {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-box input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid var(--border);
            border-radius: 25px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(231, 84, 128, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1.2rem;
        }

        .no-drugs {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .no-drugs i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-drugs p {
            margin: 0;
            font-size: 1.1rem;
        }

        .stock-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .stock-info i {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div class="section-header-left">
                    <h1>Drug Inventory</h1>
                    <p>Manage drug stock and inventory</p>
                </div>
                <div class="header-actions">
                    <a href="<?php echo url('drugs/add.php'); ?>" class="btn-action">
                        <i class="fas fa-plus"></i>
                        Add New Drug
                    </a>
                    <a href="<?php echo url('drugs/import.php'); ?>" class="btn-action secondary">
                        <i class="fas fa-file-import"></i>
                        Import
                    </a>
                    <a href="<?php echo url('drugs/stock.php'); ?>" class="btn-action secondary">
                        <i class="fas fa-boxes"></i>
                        Manage Stock
                    </a>
                </div>
            </div>

            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" 
                       id="drugSearch" 
                       placeholder="Search drugs by name..."
                       oninput="searchDrugs(this.value)">
            </div>

            <?php if (empty($drugs)): ?>
                <div class="no-drugs">
                    <i class="fas fa-prescription-bottle-alt"></i>
                    <p>No drugs in inventory</p>
                </div>
            <?php else: ?>
                <div class="drugs-grid">
                    <?php foreach ($drugs as $drug): ?>
                        <div class="drug-card">
                            <div class="drug-header">
                                <h3 class="drug-name"><?php echo htmlspecialchars($drug['name']); ?></h3>
                                <span class="status-badge <?php echo $drug['status']; ?>">
                                    <?php 
                                    switch($drug['status']) {
                                        case 'in_stock':
                                            echo '<i class="fas fa-check-circle"></i> In Stock';
                                            break;
                                        case 'low_stock':
                                            echo '<i class="fas fa-exclamation-circle"></i> Low Stock';
                                            break;
                                        case 'out_of_stock':
                                            echo '<i class="fas fa-times-circle"></i> Out of Stock';
                                            break;
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="drug-details">
                                <div class="stock-info">
                                    <i class="fas fa-calendar"></i>
                                    Added <?php echo date('M j, Y', strtotime($drug['created_at'])); ?>
                                </div>
                            </div>
                            <div class="drug-actions">
                                <a href="<?php echo url('drugs/edit.php?id=' . $drug['id']); ?>" 
                                   class="btn-card-action btn-edit">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </a>
                                <a href="<?php echo url('drugs/delete.php?id=' . $drug['id']); ?>" 
                                   class="btn-card-action btn-delete"
                                   onclick="return confirm('Are you sure you want to delete this drug?')">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="<?php echo url('assets/js/menu.js'); ?>"></script>
    <script>
        function searchDrugs(query) {
            const cards = document.querySelectorAll('.drug-card');
            
            cards.forEach(card => {
                const name = card.querySelector('.drug-name').textContent.toLowerCase();
                const visible = name.includes(query.toLowerCase());
                card.style.display = visible ? '' : 'none';
            });

            // Show/hide no results message
            const noResults = document.querySelector('.no-drugs');
            const visibleCards = document.querySelectorAll('.drug-card[style=""]').length;
            
            if (noResults) {
                noResults.style.display = visibleCards === 0 ? 'block' : 'none';
            }
        }
    </script>
</body>
</html> 