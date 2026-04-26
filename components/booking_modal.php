<?php
$isStudent = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
$readonlyAttr = $isStudent ? 'readonly style="background-color: #e2e8f0; cursor: not-allowed;"' : '';
?>
<!-- Refined Booking Component -->
<div class="modal-overlay" id="advanced-booking-modal">
    <div class="modal-content booking-modal-new">
        <button class="modal-close-new" onclick="closeAdvancedBooking()">&times;</button>
        
        <h1 class="modal-title-new">Booking for <span id="booking-date-display">(Selected Date)</span></h1>
        
        <!-- Hidden credentials for the API -->
        <input type="hidden" id="book-name" value="<?= htmlspecialchars($firstName ?? '') ?>">
        <input type="hidden" id="book-email" value="<?= htmlspecialchars($studentEmail ?? '') ?>">
        <input type="hidden" id="book-phone" value="<?= htmlspecialchars($studentPhone ?? '') ?>">
        
        <form id="advanced-booking-form" class="booking-flow">
            <?php 
            $userRole = $_SESSION['user_role'] ?? 'Student';
            $isStaffOrAdmin = in_array(strtolower($userRole), ['staff', 'admin']);
            ?>

            <!-- NEW: Requestor Details for Staff/Admin -->
            <div id="staff-requestor-section" style="display: <?= $isStaffOrAdmin ? 'block' : 'none' ?>; background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
                <h4 style="margin-bottom: 0.8rem; font-size: 0.9rem; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    Booking for another requestor
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem;">
                    <div>
                        <label class="label-new" style="font-size: 0.75rem;">Requestor Name</label>
                        <input type="text" id="staff-req-name" class="input-new" placeholder="Full Name">
                    </div>
                    <div>
                        <label class="label-new" style="font-size: 0.75rem;">Requestor Email</label>
                        <input type="email" id="staff-req-email" class="input-new" placeholder="email@example.com">
                    </div>
                </div>
                <div style="margin-top: 0.8rem;">
                    <label class="label-new" style="font-size: 0.75rem;">Requestor Department</label>
                    <select id="staff-req-dept" class="select-new">
                        <option value="" disabled selected>Select department...</option>
                        <!-- Populated via JS -->
                    </select>
                </div>
                <p style="font-size: 0.65rem; color: #64748b; margin-top: 0.5rem;">For facilitator accounts, these requester fields are required.</p>
            </div>
            <!-- 1. Booking Type -->
            <div class="booking-section">
                <label for="adv-booking-type" class="label-new">Pick a booking type ("Instructional Program", "Seminar", "Orientation")</label>
                <select id="adv-booking-type" class="select-new" required>
                    <option value="" disabled selected>Select an option...</option>
                    <option value="Instructional Program">Instructional Program</option>
                    <option value="Orientation">Orientation</option>
                    <option value="Seminar">Seminar</option>
                </select>
            </div>
            
            <!-- 1.5 Department (Instructional Only) -->
            <div class="booking-section" id="dept-section" style="display: none;">
                <label for="adv-dept-select" class="label-new">Pick a Department</label>
                <select id="adv-dept-select" class="select-new">
                    <option value="" disabled selected>Select a department...</option>
                </select>
            </div>

            <div class="booking-section" id="topic-section" style="display: none;">
                <label for="adv-topic-select" class="label-new">Pick a Topic</label>
                <select id="adv-topic-select" class="select-new">
                    <option value="" disabled selected>Select a topic...</option>
                </select>
                <!-- Disclaimer box -->
                <div id="topic-disclaimer" class="disclaimer-alert" style="display: none;">
                    <div class="disclaimer-icon">⚠️</div>
                    <div class="disclaimer-text">Your chosen topic isn't under your current department. Change it if this is a mistake, if not, proceed.</div>
                </div>
            </div>

            <!-- 3. Facilitator -->
            <div class="booking-section" id="instructor-section" style="display: none;">
                <label class="label-new">Pick a Facilitator</label>
                <div id="modal-instructor-list" class="facilitator-grid-new">
                    <!-- Cards populated via JS -->
                    <div class="loader-container">Syncing faculty...</div>
                </div>
            </div>

            <!-- 4. Time Selection -->
            <div class="booking-section" id="time-selection-section" style="display: none;">
                <label class="label-new" id="time-label">Pick a Time:</label>
                <div class="time-axis-container">
                    <div class="time-axis-labels" id="axis-labels-new" style="position: relative; height: 1.5rem; display: block;">
                        <span style="position: absolute; left: 0;">9:00 AM</span>
                        <span style="position: absolute; left: 27.27%; transform: translateX(-50%);">12:00 PM</span>
                        <span style="position: absolute; left: 54.54%; transform: translateX(-50%);">3:00 PM</span>
                        <span style="position: absolute; left: 100%; transform: translateX(-100%);">8:00 PM</span>
                    </div>
                    <div class="time-axis-track">
                        <div class="axis-line-new"></div>
                        <div class="axis-ticks-new">
                            <!-- 12 points for 11 intervals (9AM to 8PM) -->
                            <span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span>
                        </div>
                        <div id="axis-zones-container">
                            <!-- Selected, Lunch, Booked zones -->
                        </div>
                    </div>
                    <div class="axis-legend-new">
                        <div class="legend-item-new selected">
                            <span class="ln-box"></span>
                            <span class="ln-text">(Booking Time Selected)</span>
                        </div>
                        <div class="legend-item-new lunch">
                            <span class="ln-box"></span>
                            <span class="ln-text">Lunch Break</span>
                        </div>
                        <div class="legend-item-new occupied">
                            <span class="ln-box"></span>
                            <span class="ln-text">Instructor Already Booked</span>
                        </div>
                    </div>

                <!-- Standard time inputs (Instructional / Orientation) -->
                <div id="standard-time-inputs">
                    <div class="time-inputs-row">
                        <div class="time-input-group">
                            <label for="booking-start-time">Start</label>
                            <input type="time" id="booking-start-time" class="input-mini">
                        </div>
                        <div class="time-input-group">
                            <label for="booking-end-time">End</label>
                            <input type="time" id="booking-end-time" class="input-mini">
                        </div>
                    </div>
                    <p id="time-duration-hint" style="font-size: 0.72rem; color: #64748b; text-align: center; margin-top: 0.5rem;"></p>
                </div>

                <!-- Whole Day notice for Seminars -->
                <div id="whole-day-notice" style="display: none; margin-top: 1rem; background: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid #3b82f6; border-radius: 6px; padding: 0.9rem 1rem; gap: 0.75rem; align-items: flex-start;">
                    <span style="font-size: 1.3rem; flex-shrink: 0;">&#128197;</span>
                    <div>
                        <strong style="font-size: 0.85rem; color: #1e3a5f;">Whole Day Event</strong>
                        <p style="font-size: 0.78rem; color: #2563eb; margin: 0.2rem 0 0;">Seminars occupy the full day from <strong>9:00 AM to 8:00 PM</strong>. No specific time slot is required.</p>
                    </div>
                </div>

                <p id="time-error-msg" class="error-text-new" style="margin-top: 0.5rem;"></p>
                </div>
            </div>

            <!-- 5. Notes -->
            <div class="booking-section">
                <label for="book-notes" class="label-new">Notes / Special Requests:</label>
                <textarea id="book-notes" class="textarea-new" placeholder="I will be bringing 2 other classmates for the instruction program" rows="2"></textarea>
            </div>

            <!-- 6. Mode -->
            <div class="booking-section">
                <label class="label-new">Mode of Appointment:</label>
                <div class="radio-group-new">
                    <label class="radio-item-new">
                        <input type="radio" name="book-mode" value="Onsite" checked>
                        <span>On-Site</span>
                    </label>
                    <label class="radio-item-new">
                        <input type="radio" name="book-mode" value="Online">
                        <span>Online</span>
                    </label>
                </div>
            </div>

            <!-- 7. Reminder -->
            <div class="booking-section">
                <label for="book-reminder" class="label-new">Set a Reminder:</label>
                <select id="book-reminder" class="select-new">
                    <option value="10">10 minutes before appointment</option>
                    <option value="30">30 minutes before appointment</option>
                    <option value="60" selected>1 hour before appointment</option>
                    <option value="1440">1 day before appointment</option>
                </select>
            </div>

            <div class="booking-footer-info">
                <p>Your Appointment status will be "Pending" until the Library admin "Confirms" or "Cancels" your booking request, you will be notified through email if your appointment was accepted. The VENUE or PLATFORM will also be appointed by the library admin.</p>
                <p style="margin-top: 1rem;">Details such as the booking request's associated NAME, EMAIL, and COLLEGE, will be automatically taken from the user account used to BOOK this request.</p>
            </div>

            <button type="submit" class="btn-book-final" id="btn-confirm-advanced">Book</button>
        </form>
    </div>
</div>

<style>
.booking-modal-new {
    max-width: 650px;
    width: 95%;
    margin: 2rem auto;
    background: #fff;
    border-radius: 8px;
    padding: 2.5rem;
    position: relative;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.modal-title-new {
    text-align: center;
    font-size: 2.2rem;
    font-weight: 600;
    color: #1a1e23;
    margin-bottom: 2rem;
}

.modal-close-new {
    position: absolute;
    top: 1rem;
    right: 1.5rem;
    background: none;
    border: none;
    font-size: 2rem;
    color: #ccc;
    cursor: pointer;
}

.booking-flow {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.label-new {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
}

.select-new, .textarea-new, .input-mini {
    width: 100%;
    padding: 0.6rem;
    border: 1.5px solid #7c6eff55;
    border-radius: 4px;
    font-family: inherit;
    font-size: 0.9rem;
}

.select-new:focus, .textarea-new:focus {
    outline: none;
    border-color: #7c6eff;
}

.facilitator-grid-new {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-top: 0.5rem;
}

.fac-card-new {
    border: 1.5px solid #eee;
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 1rem;
    cursor: pointer;
    transition: all 0.2s;
}

.fac-card-new:hover {
    background: #f8f9ff;
    border-color: #7c6eff44;
}

.fac-card-new.selected {
    background: #e9f0ff;
    border-color: #7c6eff;
}

.fac-avatar-new {
    width: 50px;
    height: 50px;
    background: #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.fac-info-new h5 {
    font-size: 0.85rem;
    font-weight: 700;
    margin: 0;
}

.fac-info-new p {
    font-size: 0.75rem;
    color: #666;
    margin: 0.1rem 0 0;
    line-height: 1.2;
}

/* Time Axis Styles */
.time-axis-container {
    padding: 1rem 0;
}

.time-axis-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.7rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
}

.time-axis-track {
    height: 30px;
    position: relative;
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.axis-line-new {
    width: 100%;
    height: 2px;
    background: #e2e8f0;
}

.axis-ticks-new {
    position: absolute;
    width: 100%;
    display: flex;
    justify-content: space-between;
    pointer-events: none;
}

.axis-ticks-new span {
    width: 1px;
    height: 12px;
    background: #cbd5e1;
}

.axis-legend-new {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    margin-top: 1rem;
}

.legend-item-new {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.3rem;
    text-align: center;
}

.legend-item-new .ln-box {
    width: 30px;
    height: 6px;
    border-radius: 3px;
}

.legend-item-new.selected .ln-box { background: #10b981; }
.legend-item-new.lunch .ln-box { background: #f59e0b; }
.legend-item-new.occupied .ln-box { background: #ef4444; }

.ln-text {
    font-size: 0.65rem;
    font-weight: 700;
    color: #333;
}

.time-inputs-row {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    justify-content: center;
}

.time-input-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.time-input-group label {
    font-size: 0.8rem;
    font-weight: 700;
}

.input-mini {
    width: 120px;
    background: #f8fafc;
}

.radio-group-new {
    display: flex;
    gap: 1.5rem;
}

.radio-item-new {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
}

.radio-item-new input {
    accent-color: #6366f1;
}

.booking-footer-info {
    font-size: 0.75rem;
    color: #555;
    line-height: 1.4;
    background: #f8fafc;
    padding: 1rem;
    border-radius: 4px;
    border-left: 3px solid #7c6eff;
}

.btn-book-final {
    background: #6366f1;
    color: white;
    border: none;
    padding: 0.8rem;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s;
    width: 140px;
    margin: 1rem auto 0;
}

.btn-book-final:hover {
    background: #4f46e5;
}

.axis-zone {
    position: absolute;
    height: 10px;
    top: 50%;
    transform: translateY(-50%);
    border-radius: 5px;
    opacity: 0.6;
}

.disclaimer-alert {
    margin-top: 1rem;
    padding: 1rem;
    background: #fff8eb;
    border: 1px solid #ffe8cc;
    border-left: 4px solid #f59e0b;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: fadeInSlide 0.3s ease-out;
}

.disclaimer-icon {
    font-size: 1.2rem;
}

.disclaimer-text {
    font-size: 0.8rem;
    font-weight: 600;
    color: #92400e;
    line-height: 1.4;
}

@keyframes fadeInSlide {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.zone-selected { background: #10b981; border: 1.5px solid #059669; }
.zone-lunch { background: #f59e0b; border: 1.5px solid #d97706; }
.zone-booked { background: #ef4444; border: 1.5px solid #dc2626; }

@media (max-width: 600px) {
    .facilitator-grid-new {
        grid-template-columns: 1fr;
    }
}
</style>