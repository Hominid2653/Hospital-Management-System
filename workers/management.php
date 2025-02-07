<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$base_url = '../';

try {
    // management 
    $stmt = $pdo->query("
        SELECT 
            w.*,
            MAX(m.visit_date) as last_visit
        FROM workers w
        LEFT JOIN medical_history m ON w.payroll_number = m.payroll_number
        WHERE w.department LIKE '%management%' OR w.department LIKE '%admin%'
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
    <title>Management Staff - Sian Roses</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-bar">
                <div class="welcome-message">
                    <h2>Management Staff</h2>
                    <p>View and manage administrative staff records</p>
                </div>
                <div class="action-buttons">
                    <a href="add.php" class="btn-add">
                        <i class="fas fa-plus"></i>
                        Add New Staff
                    </a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="workers-list card">
                    <div class="list-header">
                        <div class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="workerSearch" 
                                   placeholder="Search management staff..."
                                   onkeyup="filterWorkers()">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Payroll Number</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Last Visit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($workers)): ?>
                                    <tr>
                                        <td colspan="5" class="no-records">
                                            <i class="fas fa-user-tie"></i>
                                            <p>No management staff registered</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($workers as $worker): ?>
                                        <tr>
                                            <td class="clickable" onclick="window.location.href='../medical/view.php?id=<?php echo $worker['payroll_number']; ?>'">
                                                <?php echo htmlspecialchars($worker['payroll_number']); ?>
                                            </td>
                                            <td class="clickable" onclick="window.location.href='../medical/view.php?id=<?php echo $worker['payroll_number']; ?>'">
                                                <?php echo htmlspecialchars($worker['name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($worker['department']); ?></td>
                                            <td>
                                                <?php 
                                                    echo $worker['last_visit'] 
                                                        ? date('M d, Y', strtotime($worker['last_visit']))
                                                        : 'No visits yet';
                                                ?>
                                            </td>
                                            <td class="actions">
                                                <a href="../medical/view.php?id=<?php echo $worker['payroll_number']; ?>" 
                                                   class="btn-view" title="View Medical History">
                                                    <i class="fas fa-notes-medical"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $worker['payroll_number']; ?>" 
                                                   class="btn-edit" title="Edit Worker">
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
    <script src="js/list.js"></script>
</body>
</html> 