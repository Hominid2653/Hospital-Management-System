// Maximum number of searches to store in history
const MAX_HISTORY_ITEMS = 5;

// Load search history when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadSearchHistory();
    
    // Add event listener for Enter key
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchWorker();
            }
        });
    }

    // Add menu search functionality
    const menuSearch = document.getElementById('menuSearch');
    if (menuSearch) {
        menuSearch.addEventListener('input', filterMenuItems);
    }
});

function searchWorker() {
    const searchInput = document.getElementById('searchInput').value;
    
    if (searchInput.trim() === '') {
        alert('Please enter a search term');
        return;
    }

    // Add to search history before performing search
    addToSearchHistory(searchInput.trim());

    fetch('search_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `search=${encodeURIComponent(searchInput)}`
    })
    .then(response => response.json())
    .then(data => {
        const resultsDiv = document.getElementById('searchResults');
        if (data.length === 0) {
            resultsDiv.innerHTML = `
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
        
        data.forEach(worker => {
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
        resultsDiv.innerHTML = html;
    })
    .catch(error => {
        console.error('Error:', error);
        const resultsDiv = document.getElementById('searchResults');
        resultsDiv.innerHTML = `
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                An error occurred while searching. Please try again.
            </div>`;
    });
}

function addToSearchHistory(searchTerm) {
    let history = JSON.parse(localStorage.getItem('sianRosesSearchHistory') || '[]');
    
    // Remove duplicate if exists
    history = history.filter(item => item.term !== searchTerm);
    
    // Add new search term with timestamp
    history.unshift({
        term: searchTerm,
        timestamp: new Date().toISOString()
    });
    
    // Keep only the most recent searches
    history = history.slice(0, MAX_HISTORY_ITEMS);
    
    localStorage.setItem('sianRosesSearchHistory', JSON.stringify(history));
    loadSearchHistory();
}

function loadSearchHistory() {
    const historyList = document.getElementById('searchHistoryList');
    if (!historyList) return;

    const history = JSON.parse(localStorage.getItem('sianRosesSearchHistory') || '[]');
    
    if (history.length === 0) {
        historyList.innerHTML = `
            <div class="no-history">
                <i class="fas fa-search"></i>
                <p>No recent searches</p>
            </div>`;
        return;
    }
    
    historyList.innerHTML = history.map(item => `
        <div class="history-item" onclick="useHistoryItem('${item.term}')">
            <div class="history-term">
                <i class="fas fa-search"></i>
                <span>${item.term}</span>
            </div>
            <div class="history-time">
                ${formatTimeAgo(new Date(item.timestamp))}
            </div>
        </div>
    `).join('');
}

function clearSearchHistory() {
    localStorage.removeItem('sianRosesSearchHistory');
    loadSearchHistory();
}

function useHistoryItem(term) {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = term;
        searchWorker();
    }
}

function formatTimeAgo(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
}

function filterMenuItems() {
    const searchTerm = document.getElementById('menuSearch').value.toLowerCase();
    const menuItems = document.querySelectorAll('#menuItems li');
    
    menuItems.forEach(item => {
        const text = item.getAttribute('data-menu-item').toLowerCase();
        const visible = text.includes(searchTerm);
        item.style.display = visible ? '' : 'none';
    });
} 