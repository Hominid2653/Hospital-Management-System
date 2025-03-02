<?php
session_start();
require_once '../config/database.php';
require_once '../config/paths.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('login.php'));
    exit();
}

// Fetch summary statistics
try {
    // Total workers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM workers");
    $total_workers = $stmt->fetch()['total'];

    // Total medical visits
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM medical_history");
    $total_visits = $stmt->fetch()['total'];

    // Total drugs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM drugs");
    $total_drugs = $stmt->fetch()['total'];

    // Get disease type distribution
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN diagnosis LIKE '%respiratory%' OR diagnosis LIKE '%breathing%' OR diagnosis LIKE '%lung%' 
                    THEN 'Respiratory'
                WHEN diagnosis LIKE '%muscle%' OR diagnosis LIKE '%joint%' OR diagnosis LIKE '%back%' OR diagnosis LIKE '%sprain%'
                    THEN 'Musculoskeletal'
                WHEN diagnosis LIKE '%skin%' OR diagnosis LIKE '%rash%' OR diagnosis LIKE '%dermatitis%'
                    THEN 'Skin Conditions'
                WHEN diagnosis LIKE '%headache%' OR diagnosis LIKE '%migraine%'
                    THEN 'Headaches'
                WHEN diagnosis LIKE '%fever%' OR diagnosis LIKE '%flu%' OR diagnosis LIKE '%cold%'
                    THEN 'Fever & Flu'
                ELSE 'Other'
            END as category,
            COUNT(*) as count
        FROM medical_history
        WHERE diagnosis IS NOT NULL
        GROUP BY category
        ORDER BY count DESC
    ");
    $disease_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent visits
    $stmt = $pdo->query("
        SELECT m.*, w.name as worker_name
        FROM medical_history m
        JOIN workers w ON m.payroll_number = w.payroll_number
        ORDER BY visit_date DESC
        LIMIT 5
    ");
    $recent_visits = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Sian Roses</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .stat-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
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

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .report-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .report-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .report-icon {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .report-title {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--text-primary);
            margin: 0;
        }

        .report-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-report {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-report.secondary {
            background: var(--secondary);
        }

        .btn-report:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .recent-visits {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }

        .recent-visits h2 {
            color: var(--primary);
            font-size: 1.25rem;
            margin: 0 0 1rem 0;
        }

        .visits-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .visit-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-radius: 8px;
            background: white;
            transition: all 0.3s ease;
        }

        .visit-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .visit-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .visit-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .visit-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 250px;
            position: relative;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .chart-header h3 {
            color: var(--primary);
            font-size: 1rem;
            margin: 0;
        }

        .period-select {
            padding: 0.25rem 0.5rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.8rem;
            color: var(--text-secondary);
            background: white;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div class="section-header-left">
                    <h1>Reports Dashboard</h1>
                    <p>View and generate medical reports</p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_workers; ?></div>
                    <div class="stat-label">Total Workers</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_visits; ?></div>
                    <div class="stat-label">Medical Visits</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-pills"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_drugs; ?></div>
                    <div class="stat-label">Drugs in Inventory</div>
                </div>
            </div>

            <div class="reports-grid">
                <div class="report-card">
                    <div class="report-header">
                        <i class="fas fa-file-medical report-icon"></i>
                        <h3 class="report-title">Medical History Report</h3>
                    </div>
                    <p>Generate comprehensive medical history reports for workers</p>
                    <div class="report-actions">
                        <a href="medical_history.php" class="btn-report">
                            <i class="fas fa-eye"></i>
                            View Report
                        </a>
                        <a href="medical_history.php?export=true" class="btn-report secondary">
                            <i class="fas fa-download"></i>
                            Export
                        </a>
                    </div>
                </div>

                <div class="report-card">
                    <div class="report-header">
                        <i class="fas fa-pills report-icon"></i>
                        <h3 class="report-title">Drug Usage Report</h3>
                    </div>
                    <p>Track and analyze drug consumption patterns</p>
                    <div class="report-actions">
                        <a href="drug_usage.php" class="btn-report">
                            <i class="fas fa-eye"></i>
                            View Report
                        </a>
                        <a href="drug_usage.php?export=true" class="btn-report secondary">
                            <i class="fas fa-download"></i>
                            Export
                        </a>
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Disease Categories</h3>
                    </div>
                    <canvas id="departmentChart"></canvas>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Monthly Visits</h3>
                    </div>
                    <canvas id="visitsChart"></canvas>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo url('assets/js/menu.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Department Distribution Chart
        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        const diseaseData = <?php echo json_encode(array_column($disease_stats, 'count')); ?>;
        const diseaseLabels = <?php echo json_encode(array_column($disease_stats, 'category')); ?>;

        new Chart(departmentCtx, {
            type: 'doughnut',
            data: {
                labels: diseaseLabels,
                datasets: [{
                    data: diseaseData,
                    backgroundColor: [
                        'rgba(231, 84, 128, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 8,
                            padding: 10,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Monthly Visits Chart
        const visitsCtx = document.getElementById('visitsChart').getContext('2d');
        new Chart(visitsCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Medical Visits',
                    data: [65, 59, 80, 81, 56, 55],
                    backgroundColor: 'rgba(231, 84, 128, 0.8)',
                    borderColor: 'rgba(231, 84, 128, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html> 