function updateStatus(drugId, status) {
    if (!confirm('Are you sure you want to update this drug\'s status?')) return;

    fetch('update_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${drugId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error updating status: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error updating status');
        console.error('Error:', error);
    });
}

function deleteDrug(drugId) {
    if (!confirm('Are you sure you want to delete this drug?')) return;

    fetch('delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${drugId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error deleting drug: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error deleting drug');
        console.error('Error:', error);
    });
} 