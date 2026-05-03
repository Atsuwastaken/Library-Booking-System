<div class="modal-overlay" id="change-instructor-modal">
    <div class="modal-content booking-modal-new" style="max-width: 500px;">
        <button class="modal-close-new" id="change-instructor-close">&times;</button>
        
        <div class="modal-header" style="margin-bottom: 1.5rem; text-align: center;">
            <h2 id="change-instructor-title" style="font-size: 1.5rem; font-weight: 800; color: #1e293b;">Change Instructor</h2>
            <p id="change-instructor-message" style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">
                Select a different instructor for your session.
            </p>
            <div style="margin-top: 1rem; padding: 0.75rem; background: #fff7ed; border-left: 3px solid #f97316; border-radius: 4px; text-align: left;">
                <p style="margin: 0; font-size: 0.78rem; color: #9a3412; line-height: 1.4;">
                    <strong>Note:</strong> Changing instructors will reset your appointment status to <strong>Pending</strong> for administrative approval.
                </p>
            </div>
        </div>

        <div class="booking-section" style="margin-bottom: 1.5rem;">
            <label class="label-new">Select Instructor</label>
            <select id="change-instructor-select" class="select-new">
                <option value="" disabled selected>Choose an instructor...</option>
            </select>
        </div>

        <div class="booking-section">
            <label class="label-new">Instructor Availability (Today)</label>
            <div class="time-axis-container" style="padding: 1rem 0;">
                <div class="time-axis-labels" id="change-axis-labels" style="position: relative; height: 1.5rem; display: block;">
                    <span style="position: absolute; left: 0;">9:00 AM</span>
                    <span style="position: absolute; left: 27.27%; transform: translateX(-50%);">12:00 PM</span>
                    <span style="position: absolute; left: 54.54%; transform: translateX(-50%);">3:00 PM</span>
                    <span style="position: absolute; left: 100%; transform: translateX(-100%);">8:00 PM</span>
                </div>
                <div class="time-axis-track">
                    <div class="axis-line-new"></div>
                    <div class="axis-ticks-new">
                        <span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span>
                    </div>
                    <div id="change-axis-zones">
                        <!-- Zones populated via JS -->
                    </div>
                </div>
                <div class="axis-legend-new" style="justify-content: center; gap: 1rem;">
                    <div class="legend-item-new selected">
                        <span class="ln-box"></span>
                        <span class="ln-text">Your Session</span>
                    </div>
                    <div class="legend-item-new occupied">
                        <span class="ln-box"></span>
                        <span class="ln-text">Booked</span>
                    </div>
                </div>
            </div>
        </div>

        <p id="change-instructor-error" class="error-text-new" style="margin-top: 1rem; display: none;"></p>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="button" class="btn-book-final" id="change-instructor-confirm" style="margin: 0; flex: 1;">Confirm Change</button>
        </div>
    </div>
</div>

