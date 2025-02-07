// Maximum number of searches to store in history
const MAX_HISTORY_ITEMS = 5;

// Load search history when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadSearchHistory();
});

function searchWorker() {
    const searchInput = document.getElementById('searchInput').value;
    
    if (searchInput.trim() === '') {
        alert('Please enter a search term');
        return;
    }

    // Add to search history before performing search
    addToSearchHistory(searchInput.trim());

    fetch('workers/search_ajax.php', {
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
            resultsDiv.innerHTML = '<p>No results found</p>';
            return;
        }

        let html = '<table><tr><th>Payroll Number</th><th>Name</th><th>Department</th><th>Actions</th></tr>';
        data.forEach(worker => {
            html += `
                <tr>
                    <td>${worker.payroll_number}</td>
                    <td>${worker.name}</td>
                    <td>${worker.department}</td>
                    <td>
                        <a href="medical/view.php?id=${worker.payroll_number}">View Medical History</a>
                    </td>
                </tr>
            `;
        });
        html += '</table>';
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
    let history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
    
    // Remove duplicate if exists
    history = history.filter(item => item.term !== searchTerm);
    
    // Add new search term with timestamp
    history.unshift({
        term: searchTerm,
        timestamp: new Date().toISOString()
    });
    
    // Keep only the most recent searches
    history = history.slice(0, MAX_HISTORY_ITEMS);
    
    localStorage.setItem('searchHistory', JSON.stringify(history));
    loadSearchHistory();
}

function loadSearchHistory() {
    const history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
    const historyList = document.getElementById('searchHistoryList');
    
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
    localStorage.removeItem('searchHistory');
    loadSearchHistory();
}

function useHistoryItem(term) {
    document.getElementById('searchInput').value = term;
    searchWorker();
}

function formatTimeAgo(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
} 