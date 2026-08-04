<!-- Facilitators Modal Component -->
<div class="modal-overlay" id="facilitators-modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <div>
                <h2>Our Facilitators</h2>
                <p style="font-size: 0.85rem; color: var(--text-secondary);">Browse instructors and their specialties</p>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button class="btn btn-muted"
                    onclick="document.getElementById('facilitators-modal').classList.remove('active')">
                    &times;
                </button>
            </div>
        </div>

        <div id="facilitators-list" class="facilitators-grid" style="margin-top: 2rem;">
            <div class="loader-container">Syncing facilitators...</div>
        </div>
    </div>
</div>
