
// Delete All Users
document.getElementById('delete-all-users-btn')?.addEventListener('click', async () => {
    const confirmed = confirm(
        'WARNING: This will delete ALL users except your own account.\n' +
        'This action cannot be undone.\n\n' +
        'Type "DELETE" to confirm:'
    );
    if (!confirmed) return;
    const typed = prompt('Type DELETE to confirm deletion of all users:');
    if (typed !== 'DELETE') {
        notify('Deletion cancelled.', 'info');
        return;
    }
    try {
        const res = await fetch('api.php?action=delete_all_users_admin', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            notify('All users deleted successfully');
            await loadUsersAdminData();
        } else {
            notify(data.message || 'Failed to delete users', 'error');
        }
    } catch (error) {
        console.error(error);
        notify('Failed to delete users', 'error');
    }
});

// Export Users CSV
document.getElementById('export-users-csv-btn')?.addEventListener('click', async () => {
    try {
        const res = await fetch('api.php?action=export_users_csv');
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `users_export_${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    } catch (error) {
        console.error(error);
        notify('Failed to export users', 'error');
    }
});

// Import Users CSV - show file input
document.getElementById('import-users-csv-btn')?.addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.csv';
    input.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('csv_file', file);
        try {
            notify('Importing users...', 'info');
            const res = await fetch('api.php?action=import_users_csv', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                const successCount = data.results.filter(r => r.success).length;
                const failCount = data.results.filter(r => !r.success).length;
                notify(`Import complete: ${successCount} successful, ${failCount} failed`);
                if (failCount > 0) {
                    console.warn('Import failures:', data.results.filter(r => !r.success));
                }
                await loadUsersAdminData();
            } else {
                notify(data.message || 'Failed to import users', 'error');
            }
        } catch (error) {
            console.error(error);
            notify('Failed to import users', 'error');
        }
    });
    input.click();
});
