<div class="modal-overlay admin-modal" id="cancel-reason-modal">
    <div class="modal-content admin-modal-card admin-modal-sm">
        <div class="modal-header">
            <h3 id="cancel-reason-title">Cancel Appointment</h3>
            <button class="btn-close" id="cancel-reason-close-top" type="button">&times;</button>
        </div>
        <div class="modal-body">
            <div class="status-warning-banner" style="margin-bottom: 1.25rem; display: flex;">
                <div class="warning-icon">⚠️</div>
                <div class="warning-content">
                    <strong>Impact of Action</strong>
                    <p id="cancel-reason-message">This will update the appointment status and notify the student.</p>
                </div>
            </div>

            <div class="form-group">
                <label id="cancel-reason-label" for="cancel-reason-input">Reason for Cancellation (Required)</label>
                <textarea id="cancel-reason-input" class="form-control" rows="4"
                    placeholder="Type a detailed reason for this action..."></textarea>
                <p class="field-note danger" id="cancel-reason-error-msg"
                    style="display: none; margin-top: 0.5rem; font-weight: 600;">Please provide a reason to continue.
                </p>
            </div>

            <div class="admin-modal-footer" style="margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" id="cancel-reason-close" style="flex: 1;">Go Back</button>
                <button type="button" class="btn btn-primary btn-danger-solid" id="cancel-reason-confirm"
                    style="flex: 1.2;">Confirm Action</button>
            </div>
        </div>
    </div>
</div>