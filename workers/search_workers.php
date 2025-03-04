<?php
session_start();
require_once '../config/database.php';
require_once '../config/paths.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('login.php'));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Workers - Sian Roses</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .main-content {
            padding: 2rem;
            background: transparent;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .search-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
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
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(231, 84, 128, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .results-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .worker-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid var(--border);
            text-decoration: none;
            display: block;
            color: inherit;
        }

        .worker-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .worker-name {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        .worker-details {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .worker-details p {
            margin: 0;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .worker-details i {
            width: 16px;
            color: var(--primary);
            opacity: 0.8;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .no-results i {
            font-size: 2.5rem;
            opacity: 0.5;
            color: var(--primary);
        }

        .department-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            background: rgba(231, 84, 128, 0.1);
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div>
                    <h1>Search Workers</h1>
                    <p>Find and view worker information</p>
                </div>
            </div>

            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Search by name, payroll number, or department..."
                        autocomplete="off"
                    >
                </div>
            </div>

            <div id="workersGrid" class="results-container"></div>
        </main>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const workersGrid = document.getElementById('workersGrid');

        function searchWorkers(query = '') {
            fetch(`<?php echo url('workers/search.php'); ?>?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Search failed');
                }

                if (data.workers.length === 0) {
                    workersGrid.innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-search"></i>
                            <p>No workers found</p>
                        </div>
                    `;
                    return;
                }

                workersGrid.innerHTML = data.workers.map(worker => `
                    <a href="<?php echo url('workers/view.php'); ?>?id=${worker.payroll_number}" class="worker-card">
                        <div class="department-badge">${worker.department}</div>
                        <div class="worker-name">${worker.name}</div>
                        <div class="worker-details">
                            <p><i class="fas fa-id-card"></i> ${worker.payroll_number}</p>
                            <p><i class="fas fa-user-tie"></i> ${worker.role}</p>
                            <p><i class="fas fa-phone"></i> ${worker.phone || 'Not provided'}</p>
                            <p><i class="fas fa-envelope"></i> ${worker.email || 'Not provided'}</p>
                        </div>
                    </a>
                `).join('');
            })
            .catch(error => {
                console.error('Search error:', error);
                workersGrid.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>An error occurred while searching</p>
                    </div>
                `;
            });
        }

        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Add event listener with debounce
        searchInput.addEventListener('input', debounce(e => {
            searchWorkers(e.target.value.trim());
        }, 300));

        // Initial load
        searchWorkers();
    </script>
</body>
</html> 