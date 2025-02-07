<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$base_url = '../'; //  base URL for sidebar links
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Workers - Sian Roses</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <div class="welcome-message">
                    <h2>Search Workers</h2>
                    <p>Find and manage worker records</p>
                </div>
                <div class="action-buttons">
                    <a href="export.php" class="btn-export">
                        <i class="fas fa-download"></i>
                        Export Records
                    </a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="search-container">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" placeholder="Enter payroll number or name...">
                        <button onclick="searchWorker()" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <!-- Add Search History Section -->
                    <div class="search-history">
                        <div class="history-header">
                            <h3><i class="fas fa-history"></i> Recent Searches</h3>
                            <button onclick="clearSearchHistory()" class="btn-clear">
                                <i class="fas fa-trash"></i> Clear History
                            </button>
                        </div>
                        <div id="searchHistoryList" class="history-list">
                            <!-- Search history items -->
                        </div>
                    </div>
                </div>
                <div id="searchResults" class="search-results"></div>
            </div>
        </main>
    </div>

    <script src="../assets/js/menu.js"></script>
    <script src="../assets/js/search.js"></script>
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
            fetch(`search_ajax.php?search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        searchResults.innerHTML = `<div class="error">${data.error}</div>`;
                        return;
                    }

                    if (data.workers.length === 0) {
                        searchResults.innerHTML = `
                            <div class="no-results">
                                <i class="fas fa-search fa-3x"></i>
                                <p>No workers found matching your search</p>
                            </div>`;
                        return;
                    }

                    let html = `
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Payroll Number</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    
                    data.workers.forEach(worker => {
                        html += `
                            <tr>
                                <td>${worker.payroll_number}</td>
                                <td>${worker.name}</td>
                                <td>${worker.department}</td>
                                <td>
                                    <a href="../medical/view.php?id=${worker.payroll_number}" class="btn-view">
                                        <i class="fas fa-eye"></i> View Medical History
                                    </a>
                                </td>
                            </tr>`;
                    });
                    
                    html += `
                                </tbody>
                            </table>
                        </div>`;
                    searchResults.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    searchResults.innerHTML = `
                        <div class="error">
                            <i class="fas fa-exclamation-circle"></i>
                            An error occurred while searching. Please try again.
                        </div>`;
                });
        }, 300); // 300ms delay to prevent too many requests
    }

    // Add event listener for input changes
    document.getElementById('searchInput').addEventListener('input', searchWorker);

    // Search history functions
    function addToSearchHistory(term) {
        let history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
        // Remove the term if it already exists
        history = history.filter(item => item !== term);
        // Add the new term at the beginning
        history.unshift(term);
        // Keep only the last 5 searches
        history = history.slice(0, 5);
        localStorage.setItem('searchHistory', JSON.stringify(history));
        displaySearchHistory();
    }

    function displaySearchHistory() {
        const history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
        const historyList = document.getElementById('searchHistoryList');
        
        if (history.length === 0) {
            historyList.innerHTML = '<div class="no-history">No recent searches</div>';
            return;
        }

        historyList.innerHTML = history.map(term => `
            <div class="history-item" onclick="useHistoryItem('${term}')">
                <i class="fas fa-history"></i>
                <span>${term}</span>
            </div>
        `).join('');
    }

    function useHistoryItem(term) {
        const searchInput = document.getElementById('searchInput');
        searchInput.value = term;
        searchWorker();
    }

    function clearSearchHistory() {
        localStorage.removeItem('searchHistory');
        displaySearchHistory();
    }

    // Display search history on page load
    displaySearchHistory();
    </script>

    <style>
    .search-container {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .search-history {
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }

    .history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .history-header h3 {
        color: #2c3e50;
        font-size: 1.1rem;
        margin: 0;
    }

    .history-item {
        padding: 0.5rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #666;
    }

    .history-item:hover {
        background: #f8f9fa;
        border-radius: 4px;
    }

    .no-history {
        text-align: center;
        color: #666;
        padding: 1rem;
    }

    .btn-clear {
        background: none;
        border: none;
        color: #e74c3c;
        cursor: pointer;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-clear:hover {
        color: #c0392b;
    }

    .loading, .error, .no-results {
        text-align: center;
        padding: 2rem;
        color: #666;
    }

    .error {
        color: #e74c3c;
    }

    .no-results i, .error i {
        margin-bottom: 1rem;
        color: #95a5a6;
    }
    </style>
</body>
</html> 