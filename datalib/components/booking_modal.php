<!-- Advanced Multi-Panel Booking Component -->
<div class="modal-overlay" id="advanced-booking-modal">
    <div class="modal-content" style="max-width: 1100px; padding: 0; background: transparent; box-shadow: none; border: none;">
        <div class="booking-container">
            <!-- Left Panel: Instructor & Time Selection -->
            <div class="booking-left">
                <div class="panel-header" style="margin-bottom: 2.5rem;">
                    <div class="icon-circle"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <polyline points="17 11 19 13 23 9"></polyline>
                        </svg></div>
                    <div>
                        <h3>Consultation Setup</h3>
                        <p id="booking-date-display">Configure your library session</p>
                    </div>
                </div>

                <!-- Instructor Picker Section -->
                <div style="margin-bottom: 2.5rem;">
                    <label class="section-label">1. Pick an Instructor</label>
                    <div id="modal-instructor-list" class="instructor-selection-grid">
                        <div class="loader-container">Loading faculty...</div>
                    </div>
                </div>
                
                <!-- Time Slot Section -->
                <div>
                    <label class="section-label">2. Select Time Window</label>
                    <div id="time-slots-container" class="slots-grid">
                        <p style="color: var(--text-secondary); font-size: 0.9rem; font-style: italic;">Select an instructor first to see availability.</p>
                    </div>
                </div>

                <div class="lunch-break-note" style="margin-top: 2rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                        <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                        <line x1="6" y1="1" x2="6" y2="4"></line>
                        <line x1="10" y1="1" x2="10" y2="4"></line>
                        <line x1="14" y1="1" x2="14" y2="4"></line>
                    </svg>
                    Lunch break (12:00 PM - 1:00 PM) excluded.
                </div>
            </div>

            <!-- Right Panel: User Information Form -->
            <div class="booking-right">
                <button class="modal-close-btn"
                    onclick="closeAdvancedBooking()">&times;</button>

                <div class="panel-header" style="margin-bottom: 2.5rem;">
                    <div class="icon-circle" style="background: var(--primary-color);"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                    <h3>Your Credentials</h3>
                    <p>Enter your identifying information</p>
                </div>

                <form id="advanced-booking-form">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="book-name" class="booking-input" placeholder="e.g. Juan De La Cruz"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Working Email Address</label>
                        <input type="email" id="book-email" class="booking-input" placeholder="@student.auf.edu.ph"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Preferred Mode</label>
                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                            <label class="mode-radio">
                                <input type="radio" name="book-mode" value="Onsite" checked>
                                <span class="mode-chip">🏛️ Onsite</span>
                            </label>
                            <label class="mode-radio">
                                <input type="radio" name="book-mode" value="Online">
                                <span class="mode-chip">🌐 Online</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Phone Number <small style="opacity: 0.6">(Optional)</small></label>
                        <input type="tel" id="book-phone" class="booking-input" placeholder="+63 9xx xxx xxxx">
                    </div>

                    <div class="form-group">
                        <label>Remind You</label>
                        <div class="reminder-options">
                            <select id="book-reminder" class="booking-select">
                                <option value="10">10 minutes before</option>
                                <option value="30" selected>30 minutes before</option>
                                <option value="60">1 hour before</option>
                                <option value="1440">1 day before</option>
                            </select>
                            <div class="ping-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btn-confirm-advanced"
                        style="width: 100%; margin-top: 2rem; padding: 1.2rem; justify-content: center; font-size: 1.1rem; font-weight: 800; border-radius: 12px; box-shadow: 0 4px 12px rgba(47, 129, 247, 0.3);">
                        Establish Reservation
                    </button>

                    <p class="form-security-note">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Secure biometric encryption active
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .booking-container {
        display: flex;
        gap: 2rem;
        align-items: flex-start;
        padding-bottom: 2rem;
    }

    .booking-left {
        flex: 1.4;
        background: #f8fafc;
        padding: 3.5rem;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        border: 1px solid var(--border);
        opacity: 0;
        transform: scale(0.9) translateY(20px);
        transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .booking-left.pop-in {
        opacity: 1;
        transform: scale(1) translateY(0);
    }

    .booking-right {
        flex: 1;
        background: #ffffff;
        padding: 3.5rem;
        position: relative;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        border: 1px solid var(--border);
        opacity: 0;
        transform: scale(0.9) translateY(20px);
        transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .booking-right.pop-in {
        opacity: 1;
        transform: scale(1) translateY(0);
    }

    .section-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-secondary);
        letter-spacing: 0.05em;
        margin-bottom: 1.25rem;
        padding-left: 0.25rem;
    }

    .instructor-selection-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        max-height: 250px;
        overflow-y: auto;
        padding-right: 8px;
    }

    .instructor-select-card {
        background: white;
        border: 2px solid var(--border);
        border-radius: 14px;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .instructor-select-card:hover {
        border-color: var(--secondary);
        transform: translateY(-3px);
        background: #f0f7ff;
    }

    .instructor-select-card.selected {
        border-color: var(--secondary);
        background: #eff6ff;
        box-shadow: 0 8px 20px rgba(47, 129, 247, 0.1);
    }

    .compact-av {
        width: 40px;
        height: 40px;
        background: var(--secondary);
        color: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .instructor-select-card.selected .compact-av {
        box-shadow: 0 4px 10px rgba(47, 129, 247, 0.3);
    }

    .ins-meta h5 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 0;
    }

    .ins-meta p {
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin: 0;
    }

    .modal-close-btn {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        background: #f1f5f9;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
    }

    .modal-close-btn:hover {
        background: #e2e8f0;
        color: var(--danger);
        transform: rotate(90deg);
    }

    .panel-header h3 {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--primary-color);
        margin-bottom: 0.25rem;
        letter-spacing: -0.02em;
    }

    .panel-header p {
        color: var(--text-secondary);
        font-size: 1rem;
    }

    .icon-circle {
        width: 44px;
        height: 44px;
        background: var(--secondary);
        color: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.25rem;
    }

    .slots-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
        max-height: 250px;
        overflow-y: auto;
        padding-right: 8px;
    }

    .time-slot-btn {
        background: white;
        border: 2px solid var(--border);
        padding: 1rem;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        cursor: pointer;
        color: var(--text-primary);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .time-slot-btn:hover {
        border-color: var(--secondary);
        transform: scale(1.02);
        background: #f0f7ff;
    }

    .time-slot-btn.selected {
        border-color: var(--secondary);
        background: var(--secondary);
        color: white;
        box-shadow: 0 10px 20px rgba(47, 129, 247, 0.2);
    }

    .slot-time {
        font-weight: 800;
        font-size: 1.05rem;
    }

    .slot-status {
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-secondary);
        opacity: 0.9;
    }

    .lunch-break-note {
        margin-top: 1.5rem;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #fef3c7;
        color: #92400e;
        padding: 1rem;
        border-radius: 12px;
        font-weight: 600;
    }

    .mode-radio {
        cursor: pointer;
        flex: 1;
    }

    .mode-radio input {
        display: none;
    }

    .mode-chip {
        display: block;
        padding: 0.8rem;
        text-align: center;
        background: #f1f5f9;
        border: 2px solid transparent;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        color: var(--text-secondary);
    }

    .mode-radio input:checked + .mode-chip {
        background: #eff6ff;
        border-color: var(--secondary);
        color: var(--secondary);
        box-shadow: 0 4px 12px rgba(47, 129, 247, 0.1);
    }

    .booking-input,
    .booking-select {
        width: 100%;
        padding: 1.1rem;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        font-size: 1rem;
        font-family: inherit;
        color: var(--text-primary);
        transition: all 0.2s ease;
        background: #f8fafc;
    }

    .booking-input:focus,
    .booking-select:focus {
        border-color: var(--secondary);
        background: white;
        outline: none;
        box-shadow: 0 0 0 4px rgba(47, 129, 247, 0.1);
    }

    .ping-icon {
        position: absolute;
        right: 1.1rem;
        color: var(--secondary);
        pointer-events: none;
    }

    .form-security-note {
        text-align: center;
        margin-top: 2rem;
        font-size: 0.8rem;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-weight: 500;
    }

    @media (max-width: 1000px) {
        .booking-container {
            flex-direction: column;
            gap: 1.5rem;
        }

        .booking-left, .booking-right {
            padding: 2.5rem;
        }
    }
</style>