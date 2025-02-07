function filterWorkers() {
    const searchInput = document.getElementById('workerSearch');
    const filter = searchInput.value.toLowerCase();
    const table = document.querySelector('.workers-list table');
    const rows = table.getElementsByTagName('tr');

    // Loop through all table rows, starting from index 1 to skip header
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const payroll = row.cells[0]?.textContent || '';
        const name = row.cells[1]?.textContent || '';
        const department = row.cells[2]?.textContent || '';
        
        // Check if any of the fields match the search term
        const matches = payroll.toLowerCase().includes(filter) ||
                       name.toLowerCase().includes(filter) ||
                       department.toLowerCase().includes(filter);
        
        row.style.display = matches ? '' : 'none';
    }
} 