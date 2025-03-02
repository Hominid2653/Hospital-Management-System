<?php
session_start();
require_once '../config/database.php';
require_once '../config/paths.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('login.php'));
    exit();
}

try {
    // Get farm workers with their latest medical visit
    $stmt = $pdo->query("
        SELECT 
            w.*,
            MAX(m.visit_date) as last_visit
        FROM workers w
        LEFT JOIN medical_history m ON w.payroll_number = m.payroll_number
        WHERE w.department NOT LIKE '%management%' AND w.department NOT LIKE '%admin%'
        GROUP BY w.payroll_number
        ORDER BY w.payroll_number DESC
    ");
    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching workers: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Workers - Sian Roses</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 500;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .workers-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

        .workers-table {
            width: 100%;
            border-collapse: collapse;
        }

        .workers-table th {
            text-align: left;
            padding: 1rem;
            color: var(--text-secondary);
            font-weight: 500;
            border-bottom: 2px solid var(--border);
        }

        .workers-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
        }

        .workers-table tr:last-child td {
            border-bottom: none;
        }

        .workers-table tr:hover {
            background: rgba(231, 84, 128, 0.05);
        }

        .worker-name {
            color: var(--primary);
            font-weight: 500;
        }

        .department-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            background: rgba(var(--secondary-rgb), 0.1);
            color: var(--secondary);
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-table-action {
            padding: 0.5rem;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-view {
            background: var(--primary);
        }

        .btn-edit {
            background: var(--secondary);
        }

        .btn-table-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .no-records {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .no-records i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-records p {
            margin: 0;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div class="section-header-left">
                    <h1>General Workers</h1>
                    <p>View and manage farm staff records</p>
                </div>
                <div class="header-actions">
                    <a href="<?php echo url('workers/add.php?type=farm'); ?>" class="btn-action">
                        <i class="fas fa-plus"></i>
                        Add New Worker
                    </a>
                    <a href="<?php echo url('workers/import.php'); ?>" class="btn-action secondary">
                        <i class="fas fa-file-import"></i>
                        Import
                    </a>
                    <a href="<?php echo url('workers/export.php'); ?>" class="btn-action secondary">
                        <i class="fas fa-download"></i>
                        Export
                    </a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($workers); ?></div>
                    <div class="stat-label">Total General Workers</div>
                </div>
            </div>

            <div class="workers-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="workerSearch" 
                           placeholder="Search workers..." 
                           oninput="filterWorkers()">
                </div>

                <?php if (empty($workers)): ?>
                    <div class="no-records">
                        <i class="fas fa-tractor"></i>
                        <p>No general workers found</p>
                    </div>
                <?php else: ?>
                    <table class="workers-table">
                        <thead>
                            <tr>
                                <th>Payroll Number</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Last Visit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workers as $worker): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($worker['payroll_number']); ?></td>
                                <td class="worker-name"><?php echo htmlspecialchars($worker['name']); ?></td>
                                <td>
                                    <span class="department-badge">
                                        <?php echo htmlspecialchars($worker['department']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($worker['role']); ?></td>
                                <td>
                                    <?php 
                                    echo $worker['last_visit'] 
                                        ? date('M j, Y', strtotime($worker['last_visit']))
                                        : 'No visits';
                                    ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="<?php echo url('workers/view.php?id=' . $worker['payroll_number']); ?>" 
                                           class="btn-table-action btn-view">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo url('workers/edit.php?id=' . $worker['payroll_number']); ?>" 
                                           class="btn-table-action btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="<?php echo url('assets/js/menu.js'); ?>"></script>
    <script>
        function filterWorkers() {
            const input = document.getElementById('workerSearch');
            const filter = input.value.toLowerCase();
            const tbody = document.querySelector('tbody');
            const rows = tbody.getElementsByTagName('tr');

            for (let row of rows) {
                const cells = row.getElementsByTagName('td');
                let found = false;

                for (let cell of cells) {
                    if (cell.textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }

                row.style.display = found ? '' : 'none';
            }
        }
    </script>
</body>
</html> 