<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$base_url = '../';

// Get filter parameters
$period = $_GET['period'] ?? 'month';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

try {
    // Your database queries here
} catch (PDOException $e) {
    $error = "Error generating report: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Reports - Sian Roses</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Header Styles */
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

        .header-actions {
            display: flex;
            gap: 1rem;
        }

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

        /* Filter Section */
        .filters-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .filters-form {
            display: flex;
            gap: 1.5rem;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .styled-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            color: var(--text-primary);
            background-color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .styled-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(231, 84, 128, 0.1);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            min-height: 400px;
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div>
                    <h1>Medical Reports</h1>
                    <p>View and analyze medical statistics</p>
                </div>
                <div class="header-actions">
                    <a href="export_summary.php?period=<?php echo htmlspecialchars($period); ?>&year=<?php echo htmlspecialchars($year); ?>&month=<?php echo htmlspecialchars($month); ?>" 
                       class="btn-action">
                        <i class="fas fa-file-word"></i>
                        Export Summary
                    </a>
                    <a href="export_report.php?period=<?php echo htmlspecialchars($period); ?>&year=<?php echo htmlspecialchars($year); ?>&month=<?php echo htmlspecialchars($month); ?>" 
                       class="btn-action secondary">
                        <i class="fas fa-file-csv"></i>
                        Export Data
                    </a>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
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

            <!-- Your statistics and charts sections here -->

        </main>
    </div>

    <script src="../assets/js/menu.js"></script>
</body>
</html> 