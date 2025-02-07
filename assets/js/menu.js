document.addEventListener('DOMContentLoaded', function() {
    // Add menu search functionality
    const menuSearch = document.getElementById('menuSearch');
    if (menuSearch) {
        menuSearch.addEventListener('input', filterMenuItems);
    }
});

function filterMenuItems() {
    const searchTerm = document.getElementById('menuSearch').value.toLowerCase();
    const menuItems = document.querySelectorAll('#menuItems li');
    
    menuItems.forEach(item => {
        const text = item.getAttribute('data-menu-item').toLowerCase();
        const visible = text.includes(searchTerm);
        item.style.display = visible ? '' : 'none';
    });
} 