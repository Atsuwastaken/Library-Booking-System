<div class="modal-overlay" id="change-instructor-modal">
    <div class="modal-content checkout-box cancel-reason-box">
        <div class="modal-header" style="margin-bottom: 1rem;">
            <h2 id="change-instructor-title">Change Instructor</h2>
            <p id="change-instructor-message" class="cancel-reason-message">
                Select your preferred instructor. Your appointment will return to PENDING for admin confirmation.
            </p>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label>Preferred instructor</label>
            <div id="change-instructor-list" class="facilitator-grid-new">
                <div class="loader-container">Loading available facilitators...</div>
            </div>
        </div>

        <div class="modal-actions">
            <button type="button" class="btn btn-outline" id="change-instructor-close">Keep Current Instructor</button>
            <button type="button" class="btn btn-primary" id="change-instructor-confirm">Confirm Change</button>
        </div>
    </div>
</div>
