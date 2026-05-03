<div class="modal-overlay admin-modal" id="admin-seminar-modal">
    <div class="modal-content admin-modal-card admin-modal-md">
        <div class="modal-header">
            <h3 id="seminar-modal-title">Add New Institutional Seminar</h3>
            <button class="btn-close" onclick="this.closest('.modal-overlay').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="admin-seminar-form">
                <input type="hidden" id="seminar-id" value="">
                <div class="form-group">
                    <label>Event Title</label>
                    <input type="text" id="seminar-title" class="form-control" placeholder="e.g. Modern Web Dev" required>
                </div>

                <!-- Speaker: Facilitator or Guest -->
                <div class="form-group">
                    <label>Speaker / Facilitator</label>
                    <select id="seminar-facilitator-select" class="form-control">
                        <option value="">— Guest Speaker (enter name below) —</option>
                        <!-- Populated via JS -->
                    </select>
                </div>
                <div class="form-group" id="seminar-guest-speaker-group">
                    <label>Guest Speaker Name <span style="color:#64748b;font-weight:400;">(if not in system)</span></label>
                    <input type="text" id="seminar-speaker" class="form-control" placeholder="e.g. Dr. John Doe">
                </div>

                <div class="form-group">
                    <label>Date &amp; Time</label>
                    <input type="datetime-local" id="seminar-datetime" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Venue</label>
                    <input type="text" id="seminar-venue" class="form-control" value="Library Audio-Visual Room">
                </div>
                <div class="form-group">
                    <label>Brief Description</label>
                    <textarea id="seminar-desc" class="form-control" rows="3"></textarea>
                </div>
                <div class="admin-modal-footer">
                    <button type="submit" class="btn btn-primary" id="seminar-submit-btn" style="flex: 1;">Publish Seminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle guest speaker input based on facilitator selection
document.getElementById('seminar-facilitator-select')?.addEventListener('change', function() {
    const guestGroup = document.getElementById('seminar-guest-speaker-group');
    if (this.value) {
        guestGroup.style.display = 'none';
        document.getElementById('seminar-speaker').value = '';
    } else {
        guestGroup.style.display = 'block';
    }
});

// Load facilitators into seminar modal dropdown
async function loadSeminarFacilitators(selectedFacId = null) {
    const sel = document.getElementById('seminar-facilitator-select');
    if (!sel) return;
    sel.innerHTML = '<option value="">— Guest Speaker (enter name below) —</option>';
    try {
        const res = await fetch('api.php?action=get_facilitators');
        const data = await res.json();
        if (data.success) {
            data.facilitators.forEach(f => {
                const opt = document.createElement('option');
                opt.value = f.id;
                opt.textContent = f.name;
                if (String(f.id) === String(selectedFacId)) opt.selected = true;
                sel.appendChild(opt);
            });
        }
    } catch(e) {}
    // Toggle visibility based on selection
    const guestGroup = document.getElementById('seminar-guest-speaker-group');
    guestGroup.style.display = sel.value ? 'none' : 'block';
}

document.getElementById('admin-seminar-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const seminarId = document.getElementById('seminar-id').value;
    const facilitatorId = document.getElementById('seminar-facilitator-select').value || null;
    const guestSpeaker = document.getElementById('seminar-speaker').value.trim();

    const payload = {
        title: document.getElementById('seminar-title').value,
        speaker: facilitatorId ? '' : guestSpeaker,
        date_time: document.getElementById('seminar-datetime').value,
        venue: document.getElementById('seminar-venue').value,
        description: document.getElementById('seminar-desc').value,
        facilitator_id: facilitatorId || null
    };
    
    const action = seminarId ? 'update_seminar' : 'add_seminar';
    if (seminarId) payload.id = seminarId;

    try {
        const res = await fetch(`api.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('admin-seminar-modal').classList.remove('active');
            if (typeof loadSeminars === 'function') loadSeminars();
            if (typeof notify === 'function') notify(seminarId ? 'Seminar updated' : 'Seminar created');
            e.target.reset();
            document.getElementById('seminar-id').value = '';
            document.getElementById('seminar-guest-speaker-group').style.display = 'block';
        } else {
            if (typeof notify === 'function') notify(data.message || 'Failed', 'error');
        }
    } catch (err) {
        console.error(err);
        if (typeof notify === 'function') notify('Network error', 'error');
    }
});
</script>
