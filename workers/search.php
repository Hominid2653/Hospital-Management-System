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

        .search-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 2rem;
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

        .search-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-option {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            border: 1px solid var(--border);
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .filter-option i {
            font-size: 1rem;
        }

        .filter-option:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .filter-option.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .search-results {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .result-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .result-item:last-child {
            border-bottom: none;
        }

        .result-item:hover {
            background: rgba(231, 84, 128, 0.05);
            transform: translateX(5px);
        }

        .result-info {
            flex: 1;
        }

        .worker-name {
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .worker-details {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .result-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: var(--primary);
            color: white;
        }

        .btn-edit {
            background: var(--secondary);
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-results p {
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
                <h1>Search Workers</h1>
                <p>Search and find worker records quickly</p>
            </div>

            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="searchInput" 
                           placeholder="Search by payroll number, name, or department..." 
                           oninput="searchWorkers(this.value)">
                </div>

                <div class="search-filters">
                    <button class="filter-option active" onclick="setFilter('all')">
                        <i class="fas fa-users"></i>
                        All Workers
                    </button>
                    <button class="filter-option" onclick="setFilter('management')">
                        <i class="fas fa-user-tie"></i>
                        Management
                    </button>
                    <button class="filter-option" onclick="setFilter('farm')">
                        <i class="fas fa-tractor"></i>
                        Farm Workers
                    </button>
                </div>
            </div>

            <div id="searchResults" class="search-results">
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>Start typing to search for workers</p>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo url('assets/js/menu.js'); ?>"></script>
    <script>
        let currentFilter = 'all';

        function setFilter(filter) {
            currentFilter = filter;
            const buttons = document.querySelectorAll('.filter-option');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.closest('.filter-option').classList.add('active');
            
            const searchInput = document.getElementById('searchInput');
            searchWorkers(searchInput.value);
        }

        function searchWorkers(query) {
            if (!query.trim()) {
                document.getElementById('searchResults').innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>Start typing to search for workers</p>
                    </div>`;
                return;
            }

            fetch(`search_workers.php?q=${encodeURIComponent(query)}&filter=${currentFilter}`)
                .then(response => response.json())
                .then(workers => {
                    const searchResults = document.getElementById('searchResults');
                    
                    if (workers.length === 0) {
                        searchResults.innerHTML = `
                            <div class="no-results">
                                <i class="fas fa-exclamation-circle"></i>
                                <p>No workers found matching your search</p>
                            </div>`;
                        return;
                    }

                    const resultsHtml = workers.map(worker => `
                        <div class="result-item">
                            <div class="result-info">
                                <div class="worker-name">${worker.name}</div>
                                <div class="worker-details">
                                    ${worker.payroll_number} • ${worker.department}
                                </div>
                            </div>
                            <div class="result-actions">
                                <a href="view.php?id=${worker.payroll_number}" 
                                   class="btn-action btn-view">
                                    <i class="fas fa-eye"></i>
                                    View
                                </a>
                                <a href="edit.php?id=${worker.payroll_number}" 
                                   class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </a>
                            </div>
                        </div>
                    `).join('');

                    searchResults.innerHTML = resultsHtml;
                })
                .catch(error => {
                    console.error('Search error:', error);
                    document.getElementById('searchResults').innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>An error occurred while searching</p>
                        </div>`;
                });
        }
    </script>
</body>
</html> 