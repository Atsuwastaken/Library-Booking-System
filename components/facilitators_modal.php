<!-- Facilitators Modal Component -->
<div class="modal-overlay" id="facilitators-modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <div>
                <h2>Our Facilitators</h2>
                <p style="font-size: 0.85rem; color: var(--text-secondary);">Manage instructors and their schedules</p>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button class="btn btn-primary btn-sm" onclick="showFacilitatorForm()">+ Add New Instructor</button>
                <button class="btn btn-muted"
                    onclick="document.getElementById('facilitators-modal').classList.remove('active')">
                    &times;
                </button>
            </div>
        </div>
        <!-- Facilitator Management/Edit Form -->
        <div id="facilitator-form-panel" style="display: none; margin-top: 1.5rem; background: #f0f9ff; padding: 1.5rem; border-radius: 12px; border: 1px solid rgba(47, 129, 247, 0.2);">
            <h3 id="fac-form-title" style="margin-bottom: 1rem; color: var(--primary-color);">Add New Instructor</h3>
            <form id="fac-crud-form">
                <input type="hidden" id="edit-fac-id">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="fac-name">Full Name</label>
                        <input type="text" id="fac-name" class="login-input" placeholder="e.g. Dr. June Garcia" required>
                    </div>
                    <div class="form-group">
                        <label for="fac-topic-ids">Assigned Topics</label>
                        <select id="fac-topic-ids" class="login-input" multiple required style="min-height: 80px;">
                            <!-- Populated dynamically via JS -->
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; margin-top: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-muted" onclick="hideFacilitatorForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="fac-save-btn">Save Instructor</button>
                </div>
            </form>
        </div>

        <div id="facilitators-list" class="facilitators-grid" style="margin-top: 2rem;">
            <div class="loader-container">Syncing facilitators...</div>
        </div>

        <!-- Hidden Management Panel -->
        <div id="facilitator-manage-panel" style="display: none; margin-top: 2rem; border-top: 2px solid var(--border); padding-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 id="manage-facilitator-name">Manage Instructor Hours</h3>
                <button class="btn btn-muted btn-sm" onclick="closeManagePanel()">Back to Directory</button>
            </div>
            
            <form id="add-session-form" style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                <input type="hidden" id="manage-facilitator-id">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="new-session-topic">Session Topic</label>
                        <select id="new-session-topic" class="login-input" required>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="new-session-dt">Date & Time</label>
                        <input type="datetime-local" id="new-session-dt" class="login-input" required>
                    </div>
                    <div class="form-group">
                        <label for="new-session-mode">Mode</label>
                        <select id="new-session-mode" class="login-input">
                            <option value="Onsite">Onsite</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Add Slot</button>
                    </div>
                </div>
            </form>

            <div id="manage-sessions-list">
                <!-- Session list for specific facilitator -->
            </div>
        </div>
    </div>
</div>
