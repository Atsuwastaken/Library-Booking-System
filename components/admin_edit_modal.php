<div class="modal-overlay admin-modal" id="admin-appointment-modal">
    <div class="modal-content admin-modal-card admin-modal-lg">
        <div class="modal-header">
            <h3>Manage Appointment</h3>
            <button class="btn-close"
                onclick="this.closest('.modal-overlay').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Appointment Summary Header -->
            <div class="appointment-summary-header">
                <div class="summary-upper-grid">
                    <!-- Column 1: Requestor Details -->
                    <div class="summary-col">
                        <span class="summary-label">Requestor</span>
                        <div id="summary-name" class="summary-main"></div>
                        <div id="summary-email" class="summary-sub"></div>
                    </div>
                    <!-- Column 2: Schedule Details -->
                    <div class="summary-col">
                        <span class="summary-label">Schedule</span>
                        <div id="summary-date" class="summary-main"></div>
                        <div id="summary-time" class="summary-sub"></div>
                    </div>
                    <!-- Column 3: Status Details -->
                    <div class="summary-col" style="align-items: flex-end; text-align: right;">
                        <span class="summary-label">Current Status</span>
                        <div id="summary-status-badge"></div>
                    </div>
                </div>
                
                <div class="summary-lower-info">
                    <div class="summary-type-line">
                        <span class="summary-label">Appointment Type:</span>
                        <span id="summary-type" class="summary-type-value"></span>
                    </div>
                    <div id="summary-topic" class="summary-topic-large"></div>
                </div>
            </div>

            <div id="cancellation-info"
                style="display: none; margin-bottom: 1.5rem; padding: 1rem; background: #fff1f2; border: 1px solid #fecaca; border-radius: 12px;">
                <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                    <div style="color: #e11d48; margin-top: 2px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div>
                        <p id="closed-status-title"
                            style="margin: 0 0 0.25rem 0; color: #9f1239; font-weight: 700; font-size: 0.95rem;">
                            Appointment Closed</p>
                        <div style="font-size: 0.85rem; color: #be123c; opacity: 0.9; line-height: 1.4;">
                            <span id="cancelled-date-info"></span> • <span id="cancelled-by-info"></span>
                        </div>
                        <p id="cancellation-reason-info"
                            style="margin: 0.75rem 0 0 0; padding-top: 0.75rem; border-top: 1px solid rgba(225, 29, 72, 0.1); font-size: 0.85rem; color: #9f1239; font-style: italic;">
                        </p>
                    </div>
                </div>
            </div>

            <p id="admin-manage-lock-note"
                style="display:none; margin: 0 0 1.5rem 0; padding: 1rem; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 12px; color: #9a3412; font-size: 0.88rem; gap: 0.75rem; align-items: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                This appointment was cancelled by the student and is read-only.
            </p>

            <form id="admin-app-form">
                <input type="hidden" id="admin-app-id">

                <div class="admin-form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Status</label>
                        <select id="admin-app-status" class="form-control" onchange="handleAdminStatusChange()">
                            <option value="PENDING">PENDING</option>
                            <option value="CONFIRMED">CONFIRMED</option>
                            <option value="CANCELLED">CANCELLED</option>
                            <option value="DECLINED">DECLINED</option>
                            <option value="COMPLETED">COMPLETED</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1.5;">
                        <label>Assigned Instructor</label>
                        <select id="admin-app-facilitator" class="form-control">
                            <option value="0">To Be Assigned</option>
                            <!-- Populated via JS -->
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Venue / Link</label>
                    <input type="text" id="admin-app-venue" class="form-control" placeholder="Room 302 or Zoom Link">
                </div>

                <!-- Status Change Warning & Reason Area -->
                <div id="status-action-container"
                    style="display: none; margin-top: 1.5rem; animation: slideDown 0.3s ease;">
                    <div class="status-warning-banner" id="status-warning-banner">
                        <div class="warning-icon">⚠️</div>
                        <div class="warning-content">
                            <strong id="status-warning-title">Caution: Destructive Action</strong>
                            <p id="status-warning-msg">This will notify the student and cannot be easily undone.</p>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1rem;">
                        <label id="status-reason-label">Reason / Message for Student</label>
                        <textarea id="status-reason-input" class="form-control" rows="3"
                            placeholder="Explain the reason for this status change..."></textarea>
                        <p class="field-note danger" id="status-reason-error" style="display: none;">A reason is
                            required to proceed.</p>
                    </div>
                </div>

                <div class="admin-modal-footer" style="margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;"
                        onclick="this.closest('.modal-overlay').classList.remove('active')">Close</button>
                    <button type="button" class="btn btn-outline-danger" id="admin-archive-btn" style="flex: 1; display: none;"
                        onclick="toggleArchiveSession()">Archive</button>
                    <button type="button" class="btn btn-primary" id="admin-save-btn" style="flex: 2;"
                        onclick="saveAdminAppointment()">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay admin-modal" id="completion-evaluation-modal">
    <div class="modal-content admin-modal-card admin-modal-sm">
        <div class="modal-header">
            <h3 id="appointment-note-title">Completion Message</h3>
            <button class="btn-close" id="completion-eval-close" type="button">&times;</button>
        </div>
        <div class="modal-body">
            <p id="appointment-note-description" style="margin: 0 0 0.85rem 0; color: #475569; font-size: 0.86rem;">
                Add the completion message for this appointment. This will be used later for evaluation/email workflows.
            </p>
            <div class="form-group">
                <label id="appointment-note-label" for="completion-eval-notes">Message</label>
                <textarea id="completion-eval-notes" class="form-control" rows="5"
                    placeholder="Enter completion message for later evaluation/email use..."></textarea>
            </div>
            <div class="admin-modal-footer">
                <button class="btn btn-outline" id="completion-eval-cancel" type="button"
                    style="flex: 1;">Cancel</button>
                <button class="btn btn-primary" id="completion-eval-confirm" type="button" style="flex: 1;">Save &
                    Complete</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.adminAppointmentLocked = false;

    window.promptAppointmentNote = function (options = {}) {
        const modal = document.getElementById('completion-evaluation-modal');
        const titleEl = document.getElementById('appointment-note-title');
        const descEl = document.getElementById('appointment-note-description');
        const labelEl = document.getElementById('appointment-note-label');
        const notesEl = document.getElementById('completion-eval-notes');
        const closeBtn = document.getElementById('completion-eval-close');
        const cancelBtn = document.getElementById('completion-eval-cancel');
        const confirmBtn = document.getElementById('completion-eval-confirm');

        if (!modal || !titleEl || !descEl || !labelEl || !notesEl || !closeBtn || !cancelBtn || !confirmBtn) {
            return Promise.resolve(null);
        }

        const title = options.title || 'Appointment Note';
        const description = options.description || 'Add a note for this appointment update.';
        const label = options.label || 'Note';
        const placeholder = options.placeholder || 'Enter note...';
        const confirmText = options.confirmText || 'Save';

        titleEl.textContent = title;
        descEl.textContent = description;
        labelEl.textContent = label;
        notesEl.placeholder = placeholder;
        confirmBtn.textContent = confirmText;

        notesEl.value = '';

        return new Promise(resolve => {
            const onConfirm = () => {
                const message = notesEl.value.trim();
                if (!message) {
                    notesEl.focus();
                    return;
                }

                cleanup();
                resolve({ message });
            };

            const onClose = () => {
                cleanup();
                resolve(null);
            };

            const onBackdrop = (e) => {
                if (e.target === modal) onClose();
            };

            const onEsc = (e) => {
                if (e.key === 'Escape') onClose();
            };

            function cleanup() {
                modal.classList.remove('active');
                confirmBtn.removeEventListener('click', onConfirm);
                closeBtn.removeEventListener('click', onClose);
                cancelBtn.removeEventListener('click', onClose);
                modal.removeEventListener('click', onBackdrop);
                document.removeEventListener('keydown', onEsc);
            }

            confirmBtn.addEventListener('click', onConfirm);
            closeBtn.addEventListener('click', onClose);
            cancelBtn.addEventListener('click', onClose);
            modal.addEventListener('click', onBackdrop);
            document.addEventListener('keydown', onEsc);
            modal.classList.add('active');
            notesEl.focus();
        });
    };

    window.promptCompletionEvaluation = function () {
        return window.promptAppointmentNote({
            title: 'Completion Message',
            description: 'Add the completion message for this appointment. This will be used for the completion email and evaluation workflows.',
            label: 'Message',
            placeholder: 'Enter completion message for email/evaluation...',
            confirmText: 'Save & Complete'
        });
    };

    window.promptConfirmedNote = function () {
        return window.promptAppointmentNote({
            title: 'Confirmation Note',
            description: 'Add a short note that will be included in the confirmation email to the user.',
            label: 'Confirmed Note',
            placeholder: 'Enter confirmation note from admin...',
            confirmText: 'Save & Confirm'
        });
    };

    window.editAppointment = async function (id, status, venue, currentFacId, cancelledDateTime, cancelledBy, cancellationReason, summaryData = {}) {
        document.getElementById('admin-app-id').value = id;
        document.getElementById('admin-app-status').value = status;
        document.getElementById('admin-app-venue').value = venue || '';

        // Populate Summary
        document.getElementById('summary-name').textContent = summaryData.name || 'N/A';
        document.getElementById('summary-email').textContent = summaryData.email || 'N/A';
        document.getElementById('summary-topic').textContent = summaryData.topic || 'N/A';
        const typeText = summaryData.type || 'N/A';
        const modeText = summaryData.mode ? ` (${summaryData.mode})` : '';
        document.getElementById('summary-type').textContent = typeText + modeText;
        document.getElementById('summary-date').textContent = summaryData.date || 'N/A';
        document.getElementById('summary-time').textContent = summaryData.time || 'N/A';

        const badge = document.getElementById('summary-status-badge');
        const normStatus = String(status || '').toUpperCase();
        badge.textContent = normStatus;
        badge.className = 'summary-status-pill ' + normStatus.toLowerCase();

        // Reset status action container
        document.getElementById('status-action-container').style.display = 'none';
        document.getElementById('status-reason-input').value = '';
        document.getElementById('status-reason-error').style.display = 'none';

        // Load facilitators into dropdown
        const facSelect = document.getElementById('admin-app-facilitator');
        facSelect.innerHTML = '<option value="0">To Be Assigned</option>';

        const cancelledByText = String(cancelledBy || '').trim();
        const cancelledByAdmin = cancelledByText !== '' && /admin/i.test(cancelledByText);
        const lockedByStudentCancellation = normStatus === 'CANCELLED' && !cancelledByAdmin;
        window.adminAppointmentLocked = lockedByStudentCancellation;

        // Show/hide cancellation info
        const cancellationInfo = document.getElementById('cancellation-info');
        const isClosedStatus = normStatus === 'CANCELLED' || normStatus === 'DECLINED';
        if (isClosedStatus) {
            cancellationInfo.style.display = 'block';
            const closedLabel = normStatus === 'DECLINED' ? 'Declined' : 'Cancelled';
            const closedStatusTitle = document.getElementById('closed-status-title');
            if (closedStatusTitle) {
                closedStatusTitle.textContent = `Appointment ${closedLabel}`;
            }

            document.getElementById('cancelled-date-info').textContent = cancelledDateTime
                ? `${closedLabel} on ${new Date(cancelledDateTime).toLocaleString()}`
                : `${closedLabel} on: N/A`;
            document.getElementById('cancelled-by-info').textContent = cancelledByText
                ? `${closedLabel} by: ${cancelledByText}`
                : `${closedLabel} by: N/A`;
            document.getElementById('cancellation-reason-info').innerHTML = cancellationReason
                ? `<strong>Reason:</strong> ${cancellationReason}`
                : '<strong>Reason:</strong> No reason provided';
        } else {
            cancellationInfo.style.display = 'none';
        }

        // Lock controls if cancelled by student.
        const lockNote = document.getElementById('admin-manage-lock-note');
        const statusEl = document.getElementById('admin-app-status');
        const venueEl = document.getElementById('admin-app-venue');
        const facEl = document.getElementById('admin-app-facilitator');
        const saveBtn = document.getElementById('admin-save-btn');

        statusEl.disabled = lockedByStudentCancellation;
        venueEl.disabled = lockedByStudentCancellation;
        facEl.disabled = lockedByStudentCancellation;
        if (saveBtn) {
            saveBtn.disabled = lockedByStudentCancellation;
            saveBtn.style.opacity = lockedByStudentCancellation ? '0.6' : '1';
            saveBtn.style.cursor = lockedByStudentCancellation ? 'not-allowed' : 'pointer';
        }

        if (lockNote) {
            lockNote.style.display = lockedByStudentCancellation ? 'flex' : 'none';
        }

        try {
            const res = await fetch('api.php?action=get_facilitators');
            const data = await res.json();
            if (data.success) {
                data.facilitators.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.id;
                    opt.textContent = f.name;
                    if (f.id == currentFacId) opt.selected = true;
                    facSelect.appendChild(opt);
                });
            }
        } catch (e) { }

        // Handle Archive Button
        const archiveBtn = document.getElementById('admin-archive-btn');
        if (archiveBtn) {
            const isArchived = !!summaryData.archived_at;
            archiveBtn.style.display = 'block';
            archiveBtn.textContent = isArchived ? 'Unarchive' : 'Archive';
            archiveBtn.className = isArchived ? 'btn btn-outline-primary' : 'btn btn-outline-danger';
        }

        document.getElementById('admin-appointment-modal').classList.add('active');
    };

    window.toggleArchiveSession = async function () {
        const id = document.getElementById('admin-app-id').value;
        const archiveBtn = document.getElementById('admin-archive-btn');
        if (!id || !archiveBtn) return;

        const isArchiving = archiveBtn.textContent.trim() === 'Archive';
        const confirmMsg = isArchiving
            ? "Are you sure you want to archive this session? It will be hidden from the default list."
            : "Are you sure you want to unarchive this session?";

        if (!confirm(confirmMsg)) return;

        const action = isArchiving ? 'archive_session' : 'unarchive_session';
        try {
            const res = await fetch(`api.php?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('admin-appointment-modal').classList.remove('active');
                if (window.loadRequests) window.loadRequests();
            } else {
                alert(data.message || 'Action failed.');
            }
        } catch (e) {
            alert('An error occurred while trying to process the archive request.');
        }
    };

    window.handleAdminStatusChange = function () {
        const status = document.getElementById('admin-app-status').value;
        const container = document.getElementById('status-action-container');
        const warningBanner = document.getElementById('status-warning-banner');
        const warningTitle = document.getElementById('status-warning-title');
        const warningMsg = document.getElementById('status-warning-msg');
        const reasonLabel = document.getElementById('status-reason-label');
        const reasonInput = document.getElementById('status-reason-input');

        const normalizedStatus = String(status).toUpperCase();
        const currentStatus = document.getElementById('summary-status-badge').textContent.toUpperCase();

        // Only show reason area if the status is actually changing to something requiring a note
        if (normalizedStatus === currentStatus) {
            container.style.display = 'none';
            return;
        }

        if (normalizedStatus === 'CANCELLED' || normalizedStatus === 'DECLINED') {
            container.style.display = 'block';
            warningBanner.style.display = 'flex';
            const isDecline = normalizedStatus === 'DECLINED';

            warningTitle.textContent = isDecline ? 'Confirm Decline' : 'Confirm Cancellation';
            warningMsg.textContent = isDecline
                ? 'Declining this request will update the appointment status and notify the student.'
                : 'Cancelling this appointment will update the appointment status and notify the student.';

            reasonLabel.textContent = isDecline ? 'Decline Reason (Required)' : 'Cancellation Reason (Required)';
            reasonInput.placeholder = isDecline
                ? 'Explain why this request is being declined...'
                : 'Explain the reason for cancellation...';


        } else if (normalizedStatus === 'COMPLETED') {
            container.style.display = 'block';
            warningBanner.style.display = 'none';
            reasonLabel.textContent = 'Completion Message (Required)';
            reasonInput.placeholder = 'Enter a completion note or summary for the student...';

        } else if (normalizedStatus === 'CONFIRMED') {
            container.style.display = 'block';
            warningBanner.style.display = 'none';
            reasonLabel.textContent = 'Confirmation Note (Required)';
            reasonInput.placeholder = 'Enter a note to include in the confirmation email...';

        } else {
            container.style.display = 'none';
        }

        document.getElementById('status-reason-error').style.display = 'none';
    };

    window.saveAdminAppointment = async function () {
        if (window.adminAppointmentLocked) return;

        const id = document.getElementById('admin-app-id').value;
        const status = document.getElementById('admin-app-status').value;
        const venue = document.getElementById('admin-app-venue').value;
        const facilitator_id = document.getElementById('admin-app-facilitator').value;
        const reasonInput = document.getElementById('status-reason-input');
        const reason = reasonInput.value.trim();
        const errorEl = document.getElementById('status-reason-error');

        const normalizedStatus = String(status).toUpperCase();
        const currentStatus = document.getElementById('summary-status-badge').textContent.toUpperCase();
        const isStatusChanging = normalizedStatus !== currentStatus;

        // Validation for required fields
        if (isStatusChanging && ['CANCELLED', 'DECLINED', 'COMPLETED', 'CONFIRMED'].includes(normalizedStatus)) {
            if (!reason) {
                errorEl.style.display = 'block';
                reasonInput.focus();
                return;
            }

        }


        const saveBtn = document.getElementById('admin-save-btn');
        const originalBtnText = saveBtn.innerHTML;

        const setSaving = (isSaving) => {
            saveBtn.disabled = isSaving;
            saveBtn.innerHTML = isSaving ? '<span class="prompt-spinner"></span> Processing...' : originalBtnText;
            saveBtn.style.opacity = isSaving ? '0.7' : '1';
        };

        const payload = {
            id: id,
            status: status,
            venue: venue,
            facilitator_id: facilitator_id,
            cancellation_reason: ['CANCELLED', 'DECLINED'].includes(normalizedStatus) ? reason : null,
            cancelled_by: ['CANCELLED', 'DECLINED'].includes(normalizedStatus) ? 'Admin' : null,
            evaluation_notes: normalizedStatus === 'COMPLETED' ? reason : (normalizedStatus === 'CONFIRMED' ? reason : null)
        };

        try {
            setSaving(true);
            const res = await fetch('api.php?action=update_appointment', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                notify('Appointment updated successfully');
                document.getElementById('admin-appointment-modal').classList.remove('active');
                if (typeof loadRequests === 'function') loadRequests();
                if (typeof loadAdminCalendarContext === 'function') loadAdminCalendarContext();
            } else {
                notify(data.message || 'Failed to update appointment', 'error');
            }
        } catch (e) {
            console.error(e);
            notify('A network error occurred. Please try again.', 'error');
        } finally {
            setSaving(false);
        }
    }
</script>