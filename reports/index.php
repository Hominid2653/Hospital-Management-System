<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Add base URL for sidebar navigation
$base_url = '../';  // This is important for correct navigation paths

// Get filter parameters
$period = $_GET['period'] ?? 'month';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

// Initialize statistics array
$statistics = [];
$error = null;

try {
    // Build the date condition based on period
    $dateCondition = match($period) {
        'month' => "AND YEAR(m.visit_date) = ? AND MONTH(m.visit_date) = ?",
        'year' => "AND YEAR(m.visit_date) = ?",
        'all' => "",
    };

    // Get disease classification statistics
    $sql = "
        SELECT 
            dc.name as classification,
            COUNT(m.id) as consultation_count,
            COUNT(DISTINCT m.payroll_number) as patient_count,
            COUNT(DISTINCT DATE(m.visit_date)) as days_count,
            (
                SELECT COUNT(DISTINCT mh.payroll_number)
                FROM medical_history mh
                JOIN workers w ON mh.payroll_number = w.payroll_number
                WHERE mh.disease_classification_id = dc.id
                AND w.department LIKE '%Management%'
                " . ($dateCondition ? $dateCondition : "") . "
            ) as management_count,
            (
                SELECT COUNT(DISTINCT mh.payroll_number)
                FROM medical_history mh
                JOIN workers w ON mh.payroll_number = w.payroll_number
                WHERE mh.disease_classification_id = dc.id
                AND w.department NOT LIKE '%Management%'
                " . ($dateCondition ? $dateCondition : "") . "
            ) as worker_count
        FROM medical_history m
        JOIN disease_classifications dc ON m.disease_classification_id = dc.id
        WHERE 1=1 
        " . ($dateCondition ? $dateCondition : "") . "
        GROUP BY dc.id, dc.name
        ORDER BY consultation_count DESC
    ";

    $stmt = $pdo->prepare($sql);
    
    // Bind parameters based on period
    if ($period === 'month') {
        $stmt->execute([$year, $month, $year, $month, $year, $month]);
    } elseif ($period === 'year') {
        $stmt->execute([$year, $year, $year]);
    } else {
        $stmt->execute();
    }

    $statistics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no data found, add message
    if (empty($statistics)) {
        $error = "No data found for the selected period";
    }

} catch (PDOException $e) {
    $error = "Error generating report: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disease Classification Reports - Sian Roses</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-bar">
                <div class="welcome-message">
                    <h2>Medical Reports</h2>
                    <p>View and analyze medical statistics</p>
                </div>
                <div class="action-buttons">
                    <a href="export_report.php?period=<?php echo htmlspecialchars($period); ?>&year=<?php echo htmlspecialchars($year); ?>&month=<?php echo htmlspecialchars($month); ?>" 
                       class="btn-export" title="Export Report">
                        <i class="fas fa-file-csv"></i>
                        Export Report
                    </a>
                </div>
            </header>

            <div class="content-wrapper">
                <!-- Filters -->
                <div class="filters-section card">
                    <form method="GET" class="filters-form">
                        <div class="form-group">
                            <label for="period">Time Period</label>
                            <select name="period" id="period" class="styled-select" onchange="this.form.submit()">
                                <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>Yearly</option>
                                <option value="all" <?php echo $period === 'all' ? 'selected' : ''; ?>>All Time</option>
                            </select>
                        </div>

                        <?php if ($period === 'month'): ?>
                        <div class="form-group">
                            <label for="month">Month</label>
                            <select name="month" id="month" class="styled-select" onchange="this.form.submit()">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $month == $i ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="year">Year</label>
                            <select name="year" id="year" class="styled-select" onchange="this.form.submit()">
                                <?php for ($i = date('Y'); $i >= 2020; $i--): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $year == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Add error handling in the display section -->
                <?php if ($error): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php else: ?>
                    <!-- Statistics Cards -->
                    <div class="stats-grid">
                        <?php foreach ($statistics as $stat): ?>
                        <div class="stat-card">
                            <div class="stat-header">
                                <h3><?php echo htmlspecialchars($stat['classification']); ?></h3>
                            </div>
                            <div class="stat-body">
                                <div class="stat-item" title="Total number of medical visits for this condition">
                                    <i class="fas fa-stethoscope"></i>
                                    <div class="stat-details">
                                        <span class="stat-number"><?php echo $stat['consultation_count']; ?></span>
                                        <span class="stat-label">Total Visits</span>
                                    </div>
                                </div>
                                <?php /* Comment out unique patients section
                                <div class="stat-item" title="Number of different workers who reported this condition">
                                    <i class="fas fa-users"></i>
                                    <div class="stat-details">
                                        <span class="stat-number"><?php echo $stat['patient_count']; ?></span>
                                        <span class="stat-label">Different Workers Affected</span>
                                    </div>
                                </div>
                                */ ?>
                                <div class="stat-item">
                                    <div class="worker-split" title="Breakdown by department">
                                        <div>
                                            <span class="stat-number"><?php echo $stat['management_count']; ?></span>
                                            <span class="stat-label">Management Staff</span>
                                        </div>
                                        <div>
                                            <span class="stat-number"><?php echo $stat['worker_count']; ?></span>
                                            <span class="stat-label">Farm Workers</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Charts -->
                    <?php if (!empty($statistics)): ?>
                        <div class="charts-section">
                            <!-- Pie Chart First -->
                            <div class="card chart-container">
                                <canvas id="diseasePieChart" style="height: 300px;"></canvas>
                            </div>
                            <!-- Bar Chart Second -->
                            <div class="card chart-container">
                                <canvas id="diseaseChart" style="height: 300px;"></canvas>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    // Update both charts' options
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 20
                }
            },
            title: {
                display: true,
                padding: {
                    top: 10,
                    bottom: 20
                }
            }
        }
    };

    // Change from Pie to Doughnut chart
    const pieCtx = document.getElementById('diseasePieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',  // Changed from 'pie' to 'doughnut'
        data: {
            labels: <?php echo json_encode(array_column($statistics, 'classification')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($statistics, 'consultation_count')); ?>,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40',
                    '#FF6384',
                    '#36A2EB'
                ]
            }]
        },
        options: {
            ...chartOptions,
            plugins: {
                ...chartOptions.plugins,
                title: {
                    ...chartOptions.plugins.title,
                    text: 'Distribution of Disease Cases'
                }
            },
            cutout: '60%'  // Added cutout percentage for doughnut
        }
    });

    // Bar Chart (now second)
    const ctx = document.getElementById('diseaseChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($statistics, 'classification')); ?>,
            datasets: [{
                label: 'Total Visits',
                data: <?php echo json_encode(array_column($statistics, 'consultation_count')); ?>,
                backgroundColor: '#3498db',
                borderColor: '#2980b9',
                borderWidth: 1
            }
            <?php /* Comment out unique patients dataset
            , {
                label: 'Unique Patients',
                data: <?php echo json_encode(array_column($statistics, 'patient_count')); ?>,
                backgroundColor: '#2ecc71',
                borderColor: '#27ae60',
                borderWidth: 1
            }
            */ ?>
            ]
        },
        options: {
            ...chartOptions,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                ...chartOptions.plugins,
                title: {
                    ...chartOptions.plugins.title,
                    text: 'Disease Classification Statistics'
                }
            }
        }
    });
    </script>

    <style>
    .filters-section {
        margin-bottom: 2rem;
        padding: 1rem;
    }

    .filters-form {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 1rem;
    }

    .stat-header h3 {
        color: #2c3e50;
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
    }

    .stat-item {
        position: relative;
    }

    .stat-item:hover::after {
        content: attr(title);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding: 5px 10px;
        background: #333;
        color: white;
        border-radius: 4px;
        font-size: 0.8rem;
        white-space: nowrap;
        z-index: 1000;
    }

    .stat-number {
        font-size: 1.5rem;
        font-weight: 500;
        color: #2c3e50;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #666;
        display: block;
        margin-top: 4px;
    }

    .worker-split {
        display: flex;
        gap: 2rem;
    }

    .charts-section {
        margin-top: 2rem;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    @media (min-width: 1200px) {
        .charts-section {
            flex-direction: row;
            flex-wrap: wrap;
        }
        
        .chart-container {
            flex: 1;
            min-width: 45%;
        }
    }

    .chart-container {
        position: relative;
        min-height: 350px;
        height: auto;
        padding: 1rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        width: 100%;
    }

    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .alert-info {
        background-color: #e3f2fd;
        color: #1976d2;
        border: 1px solid #bbdefb;
    }

    .alert i {
        font-size: 1.2rem;
    }

    .mt-4 {
        margin-top: 1.5rem;
    }

    /* Add a subtle info icon next to labels */
    .stat-label-with-info {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .stat-label-with-info i {
        color: #666;
        font-size: 0.8rem;
    }

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
    </style>

    <!-- Update JavaScript paths with base_url -->
    <script src="<?php echo $base_url; ?>assets/js/menu.js"></script>
</body>
</html> 