<div class="modal-overlay admin-modal" id="admin-seminar-modal">
    <div class="modal-content admin-modal-card admin-modal-md">
        <div class="modal-header">
            <h3 id="seminar-modal-title">Add New Institutional Seminar</h3>
            <button class="btn-close" type="button" data-close-modal>&times;</button>
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
