// Store the current page URL in session storage when page loads
if (!sessionStorage.getItem('currentPage')) {
    sessionStorage.setItem('currentPage', window.location.pathname);
}

document.addEventListener('DOMContentLoaded', function() {
    // Mark active link based on current URL
    const currentPath = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
    
    sidebarLinks.forEach(link => {
        if (currentPath === link.pathname) {
            link.classList.add('active');
        }
    });
});

// Add these styles to handle loading and errors
const styles = `
    .loading-indicator {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 200px;
        color: var(--text-secondary);
    }

    .loading-indicator i {
        font-size: 2rem;
        margin-bottom: 1rem;
    }

    .error-message {
        text-align: center;
        padding: 2rem;
        color: var(--error);
        background: rgba(244, 67, 54, 0.1);
        border-radius: 8px;
        margin: 2rem;
    }

    .error-message i {
        font-size: 2rem;
        margin-bottom: 1rem;
    }
`;

// Add styles to document
const styleSheet = document.createElement("style");
styleSheet.textContent = styles;
document.head.appendChild(styleSheet);

// Menu search functionality
function filterMenuItems() {
    const searchTerm = document.getElementById('menuSearch').value.toLowerCase();
    const menuItems = document.querySelectorAll('#menuItems li');
    
    menuItems.forEach(item => {
        const text = item.getAttribute('data-menu-item').toLowerCase();
        const visible = text.includes(searchTerm);
        item.style.display = visible ? '' : 'none';
    });
} 