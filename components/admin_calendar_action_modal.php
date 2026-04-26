<div class="modal-overlay admin-modal" id="admin-calendar-action-modal">
    <div class="modal-content admin-modal-card admin-modal-sm">
        <div class="modal-header">
            <h3 id="admin-calendar-action-title">Choose Action</h3>
            <button class="btn-close" id="admin-calendar-action-close" type="button">&times;</button>
        </div>
        <div class="modal-body">
            <p id="admin-calendar-action-date" style="margin: 0 0 0.75rem 0; color: #64748b;"></p>
            <p id="admin-calendar-action-offday" style="display:none; margin: 0 0 1rem 0; padding: 0.75rem; border-radius: 8px; background: #fff7ed; border-left: 3px solid #f97316; color: #9a3412;"></p>
            <div class="admin-modal-footer" style="margin-top: 0; flex-direction: column;">
                <button type="button" class="btn btn-primary" id="admin-calendar-action-book">Book Appointment</button>
                <button type="button" class="btn btn-outline" id="admin-calendar-action-offday-btn">Mark / Update Off-Day</button>
                <button type="button" class="btn btn-outline" id="admin-calendar-action-remove-offday-btn" style="display:none;">Remove Off-Day</button>
                <button type="button" class="btn btn-outline" id="admin-calendar-action-cancel">Close</button>
            </div>
        </div>
    </div>
</div>