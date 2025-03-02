<?php
session_start();
require_once 'config/database.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$base_url = ''; // sidebar links

// Get statsdb
try {
    $workerCount = $pdo->query("SELECT COUNT(*) FROM workers")->fetchColumn();
    $visitCount = $pdo->query("SELECT COUNT(*) FROM medical_history")->fetchColumn();
    $recentVisits = $pdo->query("
        SELECT m.*, w.name, w.department 
        FROM medical_history m 
        JOIN workers w ON m.payroll_number = w.payroll_number 
        ORDER BY m.visit_date DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching dashboard data";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sian Roses</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Content  -->
        <main class="main-content">
            <header class="top-bar">
                <div class="welcome-message">
                    <h2>Welcome back, Dr. <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                    <p>Here's what's happening today</p>
                </div>
                <div class="user-profile">
                    <i class="fas fa-user-md fa-2x"></i>
                    <span>Dr. <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- StatCards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon workers">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Workers</h3>
                            <p class="stat-number"><?php echo number_format($workerCount); ?></p>
                            <p class="stat-label">Registered Employees</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon visits">
                            <i class="fas fa-notes-medical"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Medical Visits</h3>
                            <p class="stat-number"><?php echo number_format($visitCount); ?></p>
                            <p class="stat-label">Total Consultations</p>
                        </div>
                    </div>
                </div>

                <!-- search -->


                <div class="search-section">
                    <div class="section-header">
                        <h2><i class="fas fa-search"></i> Quick Search</h2>
                        <div class="section-actions">
                            <a href="workers/export.php" class="btn-export">
                                <i class="fas fa-download"></i>
                                Export Records
                            </a>
                        </div>
                    </div>
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" placeholder="Search by payroll number or name...">
                        <button onclick="searchWorker()">Search</button>
                    </div>
                    <div id="searchResults" class="search-results"></div>
                </div>

                <!-- Recent Visits -->
                <div class="recent-visits">
                    <div class="section-header">
                        <h2><i class="fas fa-history"></i> Recent Medical Visits</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Worker Name</th>
                                    <th>Department</th>
                                    <th>Diagnosis</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentVisits)): ?>
                                    <tr>
                                        <td colspan="5" class="no-records">No recent visits found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentVisits as $visit): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($visit['name']); ?></td>
                                        <td><?php echo htmlspecialchars($visit['department']); ?></td>
                                        <td class="diagnosis-cell">
                                            <?php echo htmlspecialchars(substr($visit['diagnosis'], 0, 50)) . 
                                                (strlen($visit['diagnosis']) > 50 ? '...' : ''); ?>
                                        </td>
                                        <td>
                                            <a href="medical/view.php?id=<?php echo $visit['payroll_number']; ?>" 
                                               class="btn-view">
                                                <i class="fas fa-eye"></i> View
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

    <script src="assets/js/menu.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
    let searchTimeout;

    function searchWorker() {
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        const searchTerm = searchInput.value.trim();

        // Clear previous timeout
        clearTimeout(searchTimeout);

        // Clear results if search is empty
        if (searchTerm === '') {
            searchResults.innerHTML = '';
            return;
        }

        // Add loading indicator
        searchResults.innerHTML = '<div class="loading">Searching...</div>';

        // Set new timeout
        searchTimeout = setTimeout(() => {
            fetch(`workers/search_ajax.php?search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        searchResults.innerHTML = `<div class="error">${data.error}</div>`;
                        return;
                    }

                    if (data.workers.length === 0) {
                        searchResults.innerHTML = '<div class="no-results">No workers found</div>';
                        return;
                    }

                    const resultsHtml = data.workers.map(worker => `
                        <div class="search-result-item">
                            <div class="worker-info">
                                <span class="worker-name">${worker.name}</span>
                                <span class="worker-details">
                                    ${worker.payroll_number} | ${worker.department}
                                </span>
                            </div>
                            <a href="medical/view.php?id=${worker.payroll_number}" 
                               class="btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </div>
                    `).join('');

                    searchResults.innerHTML = resultsHtml;
                })
                .catch(error => {
                    searchResults.innerHTML = '<div class="error">Error performing search</div>';
                    console.error('Search error:', error);
                });
        }, 300); // delay
    }

    // Add event listener for input changes
    document.getElementById('searchInput').addEventListener('input', searchWorker);
    </script>

    <style>
    .search-results {
        margin-top: 1rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .search-result-item {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .worker-info {
        display: flex;
        flex-direction: column;
    }

    .worker-name {
        font-weight: 500;
        color: #2c3e50;
    }

    .worker-details {
        font-size: 0.9em;
        color: #666;
    }

    .loading {
        padding: 1rem;
        text-align: center;
        color: #666;
    }

    .no-results {
        padding: 1rem;
        text-align: center;
        color: #666;
    }

    .error {
        padding: 1rem;
        text-align: center;
        color: #e74c3c;
    }
    </style>
</body>
</html> 