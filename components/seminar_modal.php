<div class="modal-overlay admin-modal" id="seminar-modal">
    <div class="modal-content admin-modal-card admin-modal-md">
        <div class="modal-header">
            <h3>Add New Institutional Seminar</h3>
            <button class="btn-close" onclick="this.closest('.modal-overlay').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="seminar-form" onsubmit="handleSeminarSubmit(event)">
                <div class="form-group">
                    <label>Event Title</label>
                    <input type="text" id="sem-title" class="form-control" placeholder="e.g. Modern Web Dev" required>
                </div>
                <div class="form-group">
                    <label>Speaker Name</label>
                    <input type="text" id="sem-speaker" class="form-control" placeholder="e.g. Dr. John Doe" required>
                </div>
                <div class="form-group">
                    <label>Date & Time</label>
                    <input type="datetime-local" id="sem-date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Venue</label>
                    <input type="text" id="sem-venue" class="form-control" value="Library Audio-Visual Room">
                </div>
                <div class="form-group">
                    <label>Brief Description</label>
                    <textarea id="sem-desc" class="form-control" rows="3"></textarea>
                </div>
                <div class="admin-modal-footer">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Publish Seminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function handleSeminarSubmit(e) {
    e.preventDefault();
    const payload = {
        title: document.getElementById('sem-title').value,
        speaker: document.getElementById('sem-speaker').value,
        date_time: document.getElementById('sem-date').value,
        venue: document.getElementById('sem-venue').value,
        description: document.getElementById('sem-desc').value
    };
    
    try {
        const res = await fetch('api.php?action=add_seminar', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('seminar-modal').classList.remove('active');
            if (typeof loadSeminars === 'function') loadSeminars();
            e.target.reset();
        }
    } catch (err) { console.error(err); }
}
</script>
