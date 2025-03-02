<?php
session_start();
require_once 'config/database.php';
require_once 'config/paths.php';

// Set base URL for root level
$base_url = './';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('login.php'));
    exit();
}

// Get statsdb
try {
    // Get total workers and department counts
    $totalWorkers = $pdo->query("SELECT COUNT(*) FROM workers")->fetchColumn();
    $managementCount = $pdo->query("
        SELECT COUNT(*) 
        FROM workers 
        WHERE department LIKE '%management%' OR department LIKE '%admin%'
    ")->fetchColumn();
    $workersCount = $totalWorkers - $managementCount;

    // Get medical cases statistics
    $totalCases = $pdo->query("SELECT COUNT(*) FROM medical_history")->fetchColumn();
    
    // Calculate average daily cases
    $avgDailyCasesQuery = $pdo->query("
        SELECT ROUND(COUNT(*) / COUNT(DISTINCT DATE(visit_date)), 1) as avg_daily 
        FROM medical_history
    ");
    $avgDailyCases = $avgDailyCasesQuery->fetchColumn() ?: 0;

    // Get medical supplies statistics
    $totalDrugs = $pdo->query("SELECT COUNT(*) FROM drugs")->fetchColumn();
    $lowStockCount = $pdo->query("
        SELECT COUNT(*) 
        FROM drugs 
        WHERE status = 'low_stock' OR status = 'out_of_stock'
    ")->fetchColumn();

    $recentVisits = $pdo->query("
        SELECT m.*, w.name, w.department 
        FROM medical_history m 
        JOIN workers w ON m.payroll_number = w.payroll_number 
        ORDER BY m.visit_date DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle any database errors
    error_log("Database Error: " . $e->getMessage());
    $totalWorkers = $managementCount = $workersCount = 0;
    $totalCases = $avgDailyCases = 0;
    $totalDrugs = $lowStockCount = 0;
}

// Add this function at the top of the file with other PHP code
function getTimeBasedGreeting() {
    $hour = date('H');
    if ($hour >= 5 && $hour < 12) {
        return "Good morning";
    } elseif ($hour >= 12 && $hour < 17) {
        return "Good afternoon";
    } elseif ($hour >= 17 && $hour < 22) {
        return "Good evening";
    } else {
        return "Good night";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sian Roses</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .main-content {
            padding: 2rem;
            background: none;
        }

        .section-header {
            margin-bottom: 2rem;
        }

        .section-header h1 {
            color: var(--primary);
            font-size: 1.75rem;
            margin: 0;
        }

        .section-header p {
            color: var(--text-secondary);
            margin: 0.5rem 0 0 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .stat-icon.primary { background: var(--primary); }
        .stat-icon.secondary { background: var(--secondary); }
        .stat-icon.success { background: #27ae60; }
        .stat-icon.warning { background: #f39c12; }

        .stat-title {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0.5rem 0;
        }

        .stat-trend {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .recent-activity {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .activity-header h2 {
            font-size: 1.25rem;
            color: var(--primary);
            margin: 0;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: rgba(231, 84, 128, 0.05);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(231, 84, 128, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-size: 1rem;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .activity-meta {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .live-clock {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <h1><?php echo getTimeBasedGreeting(); ?></h1>
                <p>Welcome to your dashboard</p>
                <div class="live-clock"></div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-title">Total Workers</div>
                    </div>
                    <div class="stat-value"><?php echo $totalWorkers; ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-chart-line"></i>
                        Active Employees
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon secondary">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-title">Management Staff</div>
                    </div>
                    <div class="stat-value"><?php echo $managementCount; ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="fas fa-notes-medical"></i>
                        </div>
                        <div class="stat-title">Medical Cases</div>
                    </div>
                    <div class="stat-value"><?php echo $totalCases; ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-calculator"></i>
                        Avg <?php echo $avgDailyCases; ?> cases/day
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon warning">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div class="stat-title">Medical Supplies</div>
                    </div>
                    <div class="stat-value"><?php echo $totalDrugs; ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $lowStockCount; ?> low stock
                    </div>
                </div>
            </div>

            <div class="recent-activity">
                <div class="activity-header">
                    <h2>Recent Medical Visits</h2>
                </div>
                <div class="activity-list">
                    <?php foreach ($recentVisits as $visit): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-user-nurse"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-title"><?php echo htmlspecialchars($visit['name']); ?></div>
                            <div class="activity-meta">
                                <?php echo htmlspecialchars($visit['department']); ?> • 
                                <?php echo date('M j, Y', strtotime($visit['visit_date'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo url('assets/js/menu.js'); ?>"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const clock = document.querySelector('.live-clock');
            clock.textContent = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true 
            });
        }

        // Update clock immediately and then every second
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html> 