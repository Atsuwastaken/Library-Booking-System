<div class="modal-overlay" id="cancel-reason-modal">
    <div class="modal-content checkout-box cancel-reason-box">
        <div class="modal-header" style="margin-bottom: 1rem;">
            <h2 id="cancel-reason-title">Cancel Appointment</h2>
            <p id="cancel-reason-message" class="cancel-reason-message">
                You may optionally provide a reason before confirming cancellation.
            </p>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label for="cancel-reason-input" id="cancel-reason-label">Cancellation reason (optional)</label>
            <textarea id="cancel-reason-input" rows="4" placeholder="Type a reason, or leave blank to continue..."></textarea>
        </div>

        <div class="modal-actions">
            <button type="button" class="btn btn-outline" id="cancel-reason-close">Keep Appointment</button>
            <button type="button" class="btn btn-cancel-confirm" id="cancel-reason-confirm">Confirm Cancellation</button>
        </div>
    </div>
</div>
