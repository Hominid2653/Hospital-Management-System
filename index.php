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
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1100;
        }

        .section-header h1 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .section-header p {
            color: var(--text-secondary);
            margin: 0;
        }

        .stats-grid {
            position: relative;
            z-index: 1000;
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
            margin-top: 1rem;
        }

        .visit-info h4 {
            margin: 0 0 0.25rem 0;
            font-size: 1rem;
            line-height: 1.2;
        }

        .worker-link {
            color: var(--text-primary);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .worker-link:hover {
            color: var(--primary);
        }

        .diagnosis {
            margin: 0;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .universal-search {
            position: relative;
            z-index: 1100;
            margin: 1rem 0;
        }
        
        .search-container {
            position: relative;
            z-index: 1100;
        }
        
        #universalSearch {
            width: 100%;
            padding: 0.875rem 3rem 0.875rem 1.5rem;
            border: none;
            border-radius: 12px;
            background: var(--bg-light);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        
        #universalSearch:focus {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            right: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1.2rem;
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-top: 0.5rem;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1100;
        }
        
        .search-results.active {
            display: block;
        }
        
        .result-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .result-item:last-child {
            border-bottom: none;
        }
        
        .result-item:hover {
            background: #f8f9fa;
        }
        
        .result-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .result-content {
            flex: 1;
        }
        
        .result-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .result-subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .result-type {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            background: #e9ecef;
            color: var(--text-secondary);
        }
        
        .result-item .result-content {
            padding: 0.5rem 0;
        }
        
        /* Loading state */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .searching .result-content {
            animation: pulse 1s infinite;
        }
        
        .error-message {
            color: #dc3545;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .error-message i {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <h1><?php echo getTimeBasedGreeting(); ?></h1>
                <div class="universal-search">
                    <div class="search-container">
                        <input type="text" 
                               id="universalSearch" 
                               placeholder="Search workers, medical records, or drugs..."
                               autocomplete="off">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <div id="searchResults" class="search-results"></div>
                </div>
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
                            <div class="activity-title">
                                <a href="<?php echo url('workers/view.php?id=' . urlencode($visit['payroll_number'])); ?>" 
                                   class="worker-link">
                                    <?php echo htmlspecialchars($visit['name']); ?>
                                </a>
                            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        const searchInput = document.getElementById('universalSearch');
        const searchResults = document.getElementById('searchResults');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.classList.remove('active');
                return;
            }
            
            searchTimeout = setTimeout(() => performSearch(query), 300);
        });

        async function performSearch(query) {
            searchResults.innerHTML = '<div class="result-item"><div class="result-content">Searching...</div></div>';
            searchResults.classList.add('active');

            try {
                const response = await fetch(`search.php?q=${encodeURIComponent(query)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                let data;
                try {
                    data = await response.json();
                } catch (e) {
                    throw new Error('Invalid response from server');
                }

                if (!response.ok) {
                    throw new Error(data.message || `HTTP error! status: ${response.status}`);
                }

                if (data.error) {
                    throw new Error(data.message);
                }
                
                if (data.results.length > 0) {
                    displayResults(data.results);
                } else {
                    searchResults.innerHTML = '<div class="result-item"><div class="result-content">No results found</div></div>';
                }
            } catch (error) {
                console.error('Search error:', error);
                searchResults.innerHTML = `
                    <div class="result-item">
                        <div class="result-content">
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                ${error.message || 'Error performing search'}
                            </div>
                        </div>
                    </div>`;
            }
        }

        function displayResults(results) {
            const html = results.map(result => `
                <a href="${result.url}" class="result-item">
                    <div class="result-icon">
                        <i class="fas ${getIconForType(result.type)}"></i>
                    </div>
                    <div class="result-content">
                        <div class="result-title">${escapeHtml(result.title)}</div>
                        <div class="result-subtitle">${escapeHtml(result.subtitle)}</div>
                    </div>
                    <span class="result-type">${result.type}</span>
                </a>
            `).join('');
            searchResults.innerHTML = html;
        }

        function getIconForType(type) {
            switch (type) {
                case 'worker': return 'fa-user';
                case 'medical': return 'fa-notes-medical';
                case 'drug': return 'fa-pills';
                default: return 'fa-search';
            }
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('active');
            }
        });
    </script>
</body>
</html> 