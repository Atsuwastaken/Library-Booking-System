document.addEventListener('DOMContentLoaded', () => {

    function formatLocalDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function parseLocalDate(dateStr) {
        if (!dateStr) return new Date();
        const [y, m, d] = dateStr.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    const grid = document.getElementById('sessions-grid');
    const refreshBtn = document.getElementById('refresh-btn');

    // Modals
    const successModal = document.getElementById('success-modal');
    const closeSuccessBtn = document.getElementById('btn-close-success');

    // Advanced Booking References
    const advBookingModal = document.getElementById('advanced-booking-modal');
    const advBookingForm = document.getElementById('advanced-booking-form');
    const advTopicSelect = document.getElementById('adv-topic-select');

    // User Sidebar References
    const avatarBtn = document.getElementById('avatar-btn');
    const userSidebar = document.getElementById('user-sidebar');

    let scrollTimeout = null;
    let clickTimer = null;
    let selectedFacId = null;
    let currentUser = null;
    let allDepartments = [];

    async function initUserContext() {
        try {
            const res = await fetch('api.php?action=get_user_info');
            const data = await res.json();
            if (data.success) {
                currentUser = data.user;
            }
        } catch (e) {
            console.error("Failed to load user info:", e);
        }
    }

    async function loadAllDepartments() {
        try {
            const res = await fetch('api.php?action=get_departments');
            const data = await res.json();
            if (data.success) {
                allDepartments = data.departments;
            }
        } catch (e) {
            console.error("Failed to load departments:", e);
        }
    }

    initUserContext();
    loadAllDepartments();


    if (avatarBtn && userSidebar) {
        avatarBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userSidebar.classList.toggle('active');
        });

        function openCancellationReasonModal({
            title = 'Cancel Appointment',
            message = 'You may optionally provide a reason before confirming cancellation.',
            confirmText = 'Confirm Cancellation',
            cancelText = 'Keep Appointment',
            reasonLabel = 'Cancellation reason (optional)',
            reasonPlaceholder = 'Type a reason, or leave blank to continue...'
        } = {}) {
            const modal = document.getElementById('cancel-reason-modal');
            const titleEl = document.getElementById('cancel-reason-title');
            const messageEl = document.getElementById('cancel-reason-message');
            const reasonLabelEl = document.getElementById('cancel-reason-label');
            const reasonEl = document.getElementById('cancel-reason-input');
            const closeBtn = document.getElementById('cancel-reason-close');
            const confirmBtn = document.getElementById('cancel-reason-confirm');

            // If modal is unavailable, safely abort instead of using native browser dialogs.
            if (!modal || !reasonEl || !closeBtn || !confirmBtn) {
                console.warn('Cancellation modal is not available in DOM.');
                return Promise.resolve(null);
            }

            if (titleEl) titleEl.textContent = title;
            if (messageEl) messageEl.textContent = message;
            if (reasonLabelEl) reasonLabelEl.textContent = reasonLabel;
            closeBtn.textContent = cancelText;
            confirmBtn.textContent = confirmText;
            reasonEl.value = '';
            reasonEl.placeholder = reasonPlaceholder;

            return new Promise(resolve => {
                const onConfirm = () => {
                    const val = reasonEl.value.trim();
                    cleanup();
                    resolve(val);
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
                    modal.removeEventListener('click', onBackdrop);
                    document.removeEventListener('keydown', onEsc);
                }

                confirmBtn.addEventListener('click', onConfirm);
                closeBtn.addEventListener('click', onClose);
                modal.addEventListener('click', onBackdrop);
                document.addEventListener('keydown', onEsc);
                modal.classList.add('active');
                reasonEl.focus();
            });
        }

        function openChangeInstructorModal(facilitators = [], currentFacilitatorId = null) {
            const modal = document.getElementById('change-instructor-modal');
            const listEl = document.getElementById('change-instructor-list');
            const closeBtn = document.getElementById('change-instructor-close');
            const confirmBtn = document.getElementById('change-instructor-confirm');
            let selectedFacilitatorId = null;

            if (!modal || !listEl || !closeBtn || !confirmBtn) {
                console.warn('Change instructor modal is not available in DOM.');
                return Promise.resolve(null);
            }

            listEl.innerHTML = '';
            if (!facilitators.length) {
                listEl.innerHTML = '<div class="loader-container">No available facilitators for this topic.</div>';
            } else {
                facilitators.forEach(f => {
                    const card = document.createElement('div');
                    card.className = 'fac-card-new';
                    card.innerHTML = `
                <div class="fac-avatar-new">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <div class="fac-info-new">
                    <h5>${f.name}</h5>
                    <p>${f.position || 'Library Faculty'}</p>
                </div>
            `;

                    card.addEventListener('click', () => {
                        listEl.querySelectorAll('.fac-card-new').forEach(c => c.classList.remove('selected'));
                        card.classList.add('selected');
                        selectedFacilitatorId = String(f.id);
                    });

                    if (currentFacilitatorId && String(f.id) === String(currentFacilitatorId)) {
                        card.classList.add('selected');
                        selectedFacilitatorId = String(f.id);
                    }

                    listEl.appendChild(card);
                });
            }

            return new Promise(resolve => {
                const onConfirm = () => {
                    if (!selectedFacilitatorId) {
                        alert('Please select an instructor first.');
                        return;
                    }
                    cleanup();
                    resolve(selectedFacilitatorId);
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
                    modal.removeEventListener('click', onBackdrop);
                    document.removeEventListener('keydown', onEsc);
                }

                confirmBtn.addEventListener('click', onConfirm);
                closeBtn.addEventListener('click', onClose);
                modal.addEventListener('click', onBackdrop);
                document.addEventListener('keydown', onEsc);
                modal.classList.add('active');
                if (listEl.firstElementChild && listEl.firstElementChild.classList.contains('fac-card-new')) {
                    listEl.firstElementChild.focus?.();
                }
            });
        }

        document.addEventListener('click', (e) => {
            if (userSidebar.classList.contains('active') && !userSidebar.contains(e.target) && e.target !== avatarBtn && !avatarBtn.contains(e.target)) {
                userSidebar.classList.remove('active');
            }
        });
    }





    // Tab Switching Logic
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    if (tabBtns.length > 0) {
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.getAttribute('data-tab');

                // Update buttons
                tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Update panes
                tabPanes.forEach(pane => {
                    pane.classList.remove('active');
                    if (pane.id === `${targetTab}-pane`) {
                        pane.classList.add('active');
                    }
                });

                if (targetTab === 'appointments') {
                    loadAppointments();
                } else if (targetTab === 'facilitators') {
                    loadFacilitators();
                } else if (targetTab === 'my-sessions') {
                    loadFacilitatorSessions();
                } else if (targetTab === 'profile') {
                    loadProfile();
                }
            });
        });
    }

    // Calendar State
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let selectedDate = formatLocalDate(new Date());
    let allSessions = [];
    let allOffDays = [];
    let isMonthView = false; // Start with Week view as requested

    const monthDisplay = document.getElementById('calendar-month-year');
    const calendarGrid = document.getElementById('calendar-grid');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const todayBtn = document.getElementById('today-btn');
    const selectedDateLabel = document.getElementById('selected-date-label');
    const toggleViewBtn = document.getElementById('toggle-view-btn');

    // Calendar Navigation Listeners
    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', () => {
            if (isMonthView) {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
            } else {
                const date = parseLocalDate(selectedDate);
                date.setDate(date.getDate() - 7);
                selectedDate = formatLocalDate(date);
                currentMonth = date.getMonth();
                currentYear = date.getFullYear();
            }
            updateCalendar();
        });
    }

    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', () => {
            if (isMonthView) {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
            } else {
                const date = parseLocalDate(selectedDate);
                date.setDate(date.getDate() + 7);
                selectedDate = formatLocalDate(date);
                currentMonth = date.getMonth();
                currentYear = date.getFullYear();
            }
            updateCalendar();
        });
    }

    if (todayBtn) {
        todayBtn.addEventListener('click', () => {
            const now = new Date();
            currentMonth = now.getMonth();
            currentYear = now.getFullYear();
            selectedDate = formatLocalDate(now);
            updateCalendar();
        });
    }

    if (toggleViewBtn) {
        toggleViewBtn.addEventListener('click', () => {
            isMonthView = !isMonthView;
            const span = toggleViewBtn.querySelector('span');
            const svg = toggleViewBtn.querySelector('svg');
            const calendarHero = document.querySelector('.calendar-hero');
            const calendarCard = document.querySelector('.calendar-card');

            if (isMonthView) {
                span.textContent = 'Collapse';
                svg.innerHTML = '<polyline points="17 11 12 6 7 11"></polyline><polyline points="17 18 12 13 7 18"></polyline>';
                if (calendarCard) calendarCard.classList.add('expanded');
                if (calendarHero) calendarHero.classList.add('expanded');
            } else {
                span.textContent = 'Expand';
                svg.innerHTML = '<polyline points="7 13 12 18 17 13"></polyline><polyline points="7 6 12 11 17 6"></polyline>';
                if (calendarCard) calendarCard.classList.remove('expanded');
                if (calendarHero) calendarHero.classList.remove('expanded');
            }
            renderCalendarGrid();
        });
    }


    // Main logic to refresh calendar view
    async function updateCalendar(forceFetch = false) {
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        if (monthDisplay) {
            monthDisplay.textContent = `${monthNames[currentMonth]} ${currentYear}`;
        }

        // Ensure selectedDate is within the current month/year context when navigating (Only in month view)
        if (isMonthView) {
            const selDate = parseLocalDate(selectedDate);
            if (selDate.getMonth() !== currentMonth || selDate.getFullYear() !== currentYear) {
                selectedDate = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-01`;
            }
        }

        if (allSessions.length === 0 || forceFetch) {
            try {
                const [appointmentsRes, offDaysRes] = await Promise.all([
                    fetch('api.php?action=get_appointments&my_appointments=true'),
                    fetch('api.php?action=get_off_days')
                ]);

                const appointmentsData = await appointmentsRes.json();
                if (appointmentsData.success) allSessions = appointmentsData.appointments;

                const offDaysData = await offDaysRes.json();
                if (offDaysData.success) allOffDays = offDaysData.off_days || [];
            } catch (e) {
                console.error("Failed to sync sessions:", e);
            }
        }

        renderCalendarGrid();
        loadPublicSeminars();
        loadMiniInstructors();
    }

    let allSeminars = [];

    async function loadPublicSeminars() {
        try {
            const res = await fetch('api.php?action=get_seminars');
            if (!res.ok) throw new Error('Network response was not ok');
            const data = await res.json();

            if (data.success && data.seminars) {
                allSeminars = data.seminars;
                const list = document.getElementById('seminars-list');
                if (list) {
                    if (allSeminars.length === 0) {
                        list.innerHTML = '<div style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1rem 0;">No upcoming events scheduled.</div>';
                        return;
                    }
                    list.innerHTML = '';
                    allSeminars.slice(0, 4).forEach(s => { // Show top 4
                        const date = new Date(s.date_time);
                        const card = document.createElement('div');
                        card.className = 'seminar-item-card';
                        card.innerHTML = `
                            <div class="seminar-date-badge">
                                <span class="mon">${date.toLocaleString('default', { month: 'short' })}</span>
                                <span class="day">${date.getDate()}</span>
                            </div>
                            <div class="seminar-info">
                                <h4>${s.topic}</h4>
                                <p>${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} • ${s.venue || 'TBA'}</p>
                            </div>
                        `;
                        list.appendChild(card);
                    });
                }
                renderCalendarGrid(); // Refresh grid with seminars
            } else {
                const list = document.getElementById('seminars-list');
                if (list) list.innerHTML = '<div class="empty-notice">No upcoming seminars scheduled.</div>';
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function loadMiniInstructors() {
        const list = document.getElementById('mini-instructors-list');
        if (!list) return;

        try {
            const res = await fetch('api.php?action=get_facilitators');
            const data = await res.json();

            if (data.success && Array.isArray(data.facilitators)) {
                list.innerHTML = '';
                const instructors = data.facilitators.slice(0, 5);

                if (instructors.length === 0) {
                    list.innerHTML = '<div style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1rem 0;">No instructors available.</div>';
                    return;
                }

                instructors.forEach(f => {
                    const initials = f.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
                    const card = document.createElement('div');
                    card.className = 'mini-instructor-card';
                    card.innerHTML = `
                        <div class="mini-instructor-avatar">${initials}</div>
                        <div class="mini-instructor-info">
                            <h4>${f.name}</h4>
                            <p>${f.position || 'Library Instructor'}</p>
                        </div>
                        <div style="color: var(--secondary); opacity: 0.5;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </div>
                    `;
                    card.onclick = () => {
                        const btn = document.getElementById('view-facilitators-btn');
                        if (btn) btn.click();
                    };
                    list.appendChild(card);
                });
            }
        } catch (e) { }
    }

    function renderCalendarGrid() {
        if (!calendarGrid) return;
        calendarGrid.innerHTML = '';

        if (selectedDateLabel) {
            const date = parseLocalDate(selectedDate);
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            selectedDateLabel.textContent = `Showing schedule for ${date.toLocaleDateString('en-US', options)}`;
        }

        const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const prevMonthLastDay = new Date(currentYear, currentMonth, 0).getDate();

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const gapDate = new Date(today);
        gapDate.setDate(today.getDate() + 2);

        let cells = [];

        // Previous month padding cells
        for (let i = firstDayOfMonth - 1; i >= 0; i--) {
            const dayNum = prevMonthLastDay - i;
            const date = new Date(currentYear, currentMonth - 1, dayNum);
            const dateStr = formatLocalDate(date);
            const isRestricted = date < gapDate;
            const cell = createCell(dayNum, true, false, dateStr === selectedDate, dateStr, isRestricted);
            cells.push({ cell, dateStr, isRestricted });
        }

        // Current month cells
        const todayStr = formatLocalDate(new Date());
        for (let i = 1; i <= daysInMonth; i++) {
            const date = new Date(currentYear, currentMonth, i);
            const dateStr = formatLocalDate(date);
            const isRestricted = date < gapDate;
            const cell = createCell(i, false, dateStr === todayStr, dateStr === selectedDate, dateStr, isRestricted);
            cells.push({ cell, dateStr, isRestricted });
        }

        // Next month padding cells
        const totalUsed = cells.length;
        const paddingNeeded = 42 - totalUsed;
        for (let i = 1; i <= paddingNeeded; i++) {
            const date = new Date(currentYear, currentMonth + 1, i);
            const dateStr = formatLocalDate(date);
            const isRestricted = date < gapDate;
            const cell = createCell(i, true, false, dateStr === selectedDate, dateStr, isRestricted);
            cells.push({ cell, dateStr, isRestricted });
        }

        // If Week View, find the week containing selectedDate
        if (!isMonthView) {
            const selectedDateObj = parseLocalDate(selectedDate);
            const dayOfWeek = selectedDateObj.getDay();
            const startOfWeek = new Date(selectedDateObj);
            startOfWeek.setDate(selectedDateObj.getDate() - dayOfWeek);

            const weekStartStr = formatLocalDate(startOfWeek);

            // Find the index in our cells array that matches the start of the week
            let startIndex = cells.findIndex(c => c.dateStr === weekStartStr);

            if (startIndex !== -1) {
                cells = cells.slice(startIndex, startIndex + 7);
            } else {
                cells = cells.slice(0, 7);
            }
        }

        cells.forEach(({ cell, dateStr, isRestricted }) => {
            const daySessions = allSessions.filter(s => s.date_time.startsWith(dateStr) && s.booking_status === 'CONFIRMED');
            const daySeminars = allSeminars.filter(s => s.date_time.startsWith(dateStr));
            const offDay = allOffDays.find(item => item.date === dateStr);
            const dotContainer = cell.querySelector('.day-content');

            if (daySessions.length > 0) {
                const dot = document.createElement('div');
                dot.className = `event-dot dot-booked`;
                dotContainer.appendChild(dot);
                if (daySessions.length > 1) {
                    const count = document.createElement('span');
                    count.style.fontSize = '0.7rem';
                    count.style.color = 'var(--text-secondary)';
                    count.textContent = `${daySessions.length} slots`;
                    dotContainer.appendChild(count);
                }
            }

            if (daySeminars.length > 0) {
                const tag = document.createElement('div');
                tag.className = `seminar-tag`;
                tag.innerHTML = `<svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg> Seminar`;
                dotContainer.appendChild(tag);
            }

            if (offDay) {
                const dot = document.createElement('div');
                dot.className = 'event-dot dot-offday';
                dotContainer.appendChild(dot);
                cell.classList.add('off-day');
                cell.setAttribute('title', offDay.description ? `Off-day: ${offDay.description}` : 'Off-day');
            } else if (daySessions.length === 0 && daySeminars.length === 0) {
                const cellDate = new Date(dateStr);
                const dayOfWeek = cellDate.getDay();
                if (dayOfWeek === 0) {
                    const dot = document.createElement('div');
                    dot.className = 'event-dot dot-closed';
                    dotContainer.appendChild(dot);
                }
            }

            // Click listener for date selection
            cell.addEventListener('click', () => {
                if (offDay) {
                    showCalendarNotice(offDay.description ? offDay.description : 'This day is unavailable for booking.');
                    return;
                }

                const selectedDayOfWeek = parseLocalDate(dateStr).getDay();
                if (selectedDayOfWeek === 0) {
                    return;
                }

                if (isRestricted) {
                    alert('Due to preparation requirements, bookings must be made at least 2 days in advance.');
                    return;
                }
                selectedDate = dateStr;
                document.querySelectorAll('.calendar-cell').forEach(c => c.classList.remove('selected'));
                cell.classList.add('selected');

                if (typeof openAdvancedBooking === 'function') {
                    openAdvancedBooking(dateStr);
                }
            });

            calendarGrid.appendChild(cell);
        });
    }

    function createCell(day, inactive, isToday, isSelected, dateStr, isRestricted) {
        const cell = document.createElement('div');
        const isSundayClosed = parseLocalDate(dateStr).getDay() === 0;
        cell.className = `calendar-cell ${inactive ? 'inactive' : ''} ${isToday ? 'today' : ''} ${isSelected ? 'selected' : ''} ${isRestricted ? 'restricted' : ''} ${isSundayClosed ? 'sunday-closed' : ''}`;
        cell.setAttribute('data-date', dateStr);
        cell.innerHTML = `
            <span class="day-number">${day}</span>
            <div class="day-content"></div>
        `;
        return cell;
    }

    function showCalendarNotice(message) {
        let notice = document.getElementById('calendar-notice');
        if (!notice) {
            notice = document.createElement('div');
            notice.id = 'calendar-notice';
            notice.className = 'calendar-toast';
            document.body.appendChild(notice);
        }

        notice.textContent = message;
        notice.classList.add('show');
        clearTimeout(window.calendarNoticeTimer);
        window.calendarNoticeTimer = setTimeout(() => {
            notice.classList.remove('show');
        }, 3000);
    }



    // Facilitators & Admin Access Logic
    const adminLoginModal = document.getElementById('admin-login-modal');
    const adminLoginForm = document.getElementById('admin-login-form');
    let isFacilitatorAuthenticated = false;

    const facilitatorsModal = document.getElementById('facilitators-modal');
    const facilitatorsBtn = document.getElementById('view-facilitators-btn');
    const facilitatorsList = document.getElementById('facilitators-list');

    if (facilitatorsBtn) {
        facilitatorsBtn.addEventListener('click', (e) => {
            e.preventDefault();
            // Find the tab button for facilitators and click it
            const facTabBtn = document.querySelector('.tab-btn[data-tab="facilitators"]');
            if (facTabBtn) {
                facTabBtn.click();
            }
        });
    }

    if (adminLoginForm) {
        adminLoginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const pass = document.getElementById('admin-password').value;
            const errorMsg = document.getElementById('admin-error');

            // Hardcoded management password for Demo
            if (pass === 'admin') {
                isFacilitatorAuthenticated = true;
                adminLoginModal.classList.remove('active');
                adminLoginForm.reset();
                errorMsg.style.display = 'none';

                // Proceed to facilitators modal
                facilitatorsModal.classList.add('active');
                loadFacilitators();
            } else {
                errorMsg.style.display = 'block';
                document.getElementById('admin-password').classList.add('shake');
                setTimeout(() => document.getElementById('admin-password').classList.remove('shake'), 400);
            }
        });
    }

    async function loadFacilitators() {
        const modalList = document.getElementById('facilitators-list');
        const mainList = document.getElementById('main-facilitators-list');

        if (modalList) modalList.innerHTML = '<div class="loader-container">Fetching instructors...</div>';
        if (mainList) mainList.innerHTML = '<div class="loader-container">Fetching our faculty...</div>';

        try {
            const res = await fetch('api.php?action=get_facilitators');
            const data = await res.json();

            if (data.success) {
                renderFacilitators(data.facilitators);
            }
        } catch (e) {
            console.error(e);
        }
    }

    function renderFacilitators(facilitators) {
        const modalList = document.getElementById('facilitators-list');
        const mainList = document.getElementById('main-facilitators-list');
        const adminControls = document.getElementById('admin-facilitator-controls');

        if (adminControls) {
            adminControls.style.display = isFacilitatorAuthenticated ? 'flex' : 'none';
        }

        const buildCard = (f) => {
            const initial = f.name.charAt(0).toUpperCase();
            const card = document.createElement('div');
            card.className = 'fac-profile-card';
            card.innerHTML = `
                ${isFacilitatorAuthenticated ? `
                <div class="fac-admin-pill">
                    <button class="btn-icon" title="Edit Instructor" onclick="handleEditFacilitator(${f.id}, '${f.name.replace(/'/g, "\\'")}', '${f.expertise ? f.expertise.replace(/'/g, "\\'") : ''}')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button class="btn-icon" title="Remove Instructor" style="color: var(--danger);" onclick="handleDeleteFacilitator(${f.id}, '${f.name.replace(/'/g, "\\'")}')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                    <button class="btn-icon" title="Schedule" onclick="handleManageHours(${f.id}, '${f.name.replace(/'/g, "\\'")}')">
                         <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    </button>
                </div>
                ` : ''}
                <div class="fac-visual-box">
                    ${f.image ? `<img src="${f.image}" alt="${f.name}" class="fac-avatar-img">` : `
                    <div class="fac-default-logo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    `}
                </div>
                <strong class="fac-name-new">${f.name}</strong>
                <span class="fac-subject-new">${f.position || 'Library Faculty'}</span>
                
                <button class="btn-select-fac" onclick="handleShatterAndBook(this, ${f.id}, '${f.name}')">Select</button>
            `;
            return card;
        };

        if (modalList) {
            modalList.innerHTML = '';
            facilitators.forEach(f => modalList.appendChild(buildCard(f)));
        }
        if (mainList) {
            mainList.innerHTML = '';
            facilitators.forEach(f => mainList.appendChild(buildCard(f)));

            // Add search listener
            const searchInput = document.getElementById('fac-directory-search');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const term = e.target.value.toLowerCase();
                    const cards = mainList.querySelectorAll('.fac-profile-card');
                    cards.forEach(card => {
                        const name = card.querySelector('.fac-name-new').textContent.toLowerCase();
                        const subject = card.querySelector('.fac-subject-new').textContent.toLowerCase();
                        if (name.includes(term) || subject.includes(term)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        }
    }

    const facFormPanel = document.getElementById('facilitator-form-panel');
    const facCrudForm = document.getElementById('fac-crud-form');
    const facFormTitle = document.getElementById('fac-form-title');
    const facSaveBtn = document.getElementById('fac-save-btn');

    window.showFacilitatorForm = () => {
        facFormPanel.style.display = 'block';
        facFormTitle.textContent = 'Add New Instructor';
        facCrudForm.reset();
        document.getElementById('edit-fac-id').value = '';
        document.getElementById('fac-name').focus();

        const facTopics = document.getElementById('fac-topic-ids');
        if (facTopics) {
            facTopics.innerHTML = '';
            allTopics.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name;
                facTopics.appendChild(opt);
            });
        }
    };

    window.hideFacilitatorForm = () => {
        facFormPanel.style.display = 'none';
        facCrudForm.reset();
    };

    window.handleEditFacilitator = (id, name, expertise) => {
        facFormPanel.style.display = 'block';
        facFormTitle.textContent = `Edit Profile: ${name}`;
        document.getElementById('edit-fac-id').value = id;
        document.getElementById('fac-name').value = name;

        const facTopics = document.getElementById('fac-topic-ids');
        if (facTopics) {
            facTopics.innerHTML = '';
            allTopics.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name;
                // Pre-select if expertise string contains topic name
                if (expertise && expertise.includes(t.name)) {
                    opt.selected = true;
                }
                facTopics.appendChild(opt);
            });
        }

        facFormPanel.scrollIntoView({ behavior: 'smooth' });
    };

    window.handleDeleteFacilitator = async (id, name) => {
        if (!confirm(`Are you sure you want to completely remove ${name}? This will also wipe all their scheduled sessions.`)) return;

        try {
            const res = await fetch('api.php?action=delete_facilitator', {
                method: 'POST',
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.success) {
                loadFacilitators();
                await updateCalendar(true);
            }
        } catch (e) { console.error(e); }
    };

    if (facCrudForm) {
        facCrudForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('edit-fac-id').value;
            const name = document.getElementById('fac-name').value;

            const facTopics = document.getElementById('fac-topic-ids');
            const topicIds = Array.from(facTopics.selectedOptions).map(opt => parseInt(opt.value));

            const action = id ? 'update_facilitator' : 'add_facilitator';
            const payload = id ? { id, name, topic_ids: topicIds } : { name, topic_ids: topicIds };

            try {
                const res = await fetch(`api.php?action=${action}`, {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    hideFacilitatorForm();
                    loadFacilitators();
                } else {
                    alert('Error saving facilitator data.');
                }
            } catch (e) { console.error(e); }
        });
    }

    const managePanel = document.getElementById('facilitator-manage-panel');
    const manageFacName = document.getElementById('manage-facilitator-name');
    const manageFacIdInput = document.getElementById('manage-facilitator-id');
    const manageSessionsList = document.getElementById('manage-sessions-list');
    const addSessionForm = document.getElementById('add-session-form');

    window.handleManageHours = (id, name) => {
        facilitatorsList.style.display = 'none';
        managePanel.style.display = 'block';
        manageFacName.textContent = `Manage Hours for ${name}`;
        manageFacIdInput.value = id;

        const newTopicSelect = document.getElementById('new-session-topic');
        if (newTopicSelect) {
            newTopicSelect.innerHTML = '';
            // Since a session must have a topic, we populate it with all topics
            allTopics.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.name; // Keep as name string since add_session takes a string topic and DB is string
                opt.textContent = t.name;
                newTopicSelect.appendChild(opt);
            });
        }

        loadFacilitatorSessions(id);
    };

    window.closeManagePanel = () => {
        facilitatorsList.style.display = 'grid';
        managePanel.style.display = 'none';
    };

    async function loadFacilitatorSessions(fid) {
        manageSessionsList.innerHTML = '<div class="loader-container">Loading slots...</div>';

        // Use allSessions if already loaded, or fetch fresh
        if (allSessions.length === 0) await updateCalendar(true);

        const mine = allSessions.filter(s => s.facilitator_id == fid);
        renderManageSlots(mine, fid);
    }

    function renderManageSlots(sessions, fid) {
        if (sessions.length === 0) {
            manageSessionsList.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 1rem;">No hours scheduled yet.</p>';
            return;
        }

        let html = `
            <div style="background: #fff; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-sm);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; background: #f8fafc; border-bottom: 2px solid var(--border); color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            <th style="padding: 1rem;">Topic</th>
                            <th style="padding: 1rem;">Schedule</th>
                            <th style="padding: 1rem;">Mode</th>
                            <th style="padding: 1rem;">Status</th>
                            <th style="padding: 1rem; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        sessions.forEach(s => {
            const statusColor = s.status === 'AVAILABLE' ? 'var(--success)' : 'var(--danger)';
            html += `
                <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s ease;">
                    <td style="padding: 1rem; font-weight: 600;">${s.topic}</td>
                    <td style="padding: 1rem; font-size: 0.9rem;">${s.date_time}</td>
                    <td style="padding: 1rem;"><span class="badge ${s.mode.toLowerCase() === 'online' ? 'badge-online' : 'badge-onsite'}">${s.mode}</span></td>
                    <td style="padding: 1rem;"><span style="color: ${statusColor}; font-weight: 700; font-size: 0.8rem;">${s.status}</span></td>
                    <td style="padding: 1rem; text-align: right;">
                        ${s.status === 'AVAILABLE' ? `<button class="btn btn-muted btn-sm" style="color: var(--danger); border-color: rgba(207, 34, 46, 0.2);" onclick="handleDeleteSession(${s.id}, ${fid})">Remove</button>` : '<span style="color: var(--text-secondary); font-style: italic; font-size: 0.8rem;">Locked</span>'}
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        manageSessionsList.innerHTML = html;
    }

    window.handleDeleteSession = async (sid, fid) => {
        if (!confirm('Are you sure you want to remove this available slot?')) return;

        const res = await fetch('api.php?action=remove_session', {
            method: 'POST',
            body: JSON.stringify({ session_id: sid })
        });
        const data = await res.json();

        if (data.success) {
            await updateCalendar(true); // Master refresh
            loadFacilitatorSessions(fid); // Panel refresh
        }
    };

    if (addSessionForm) {
        addSessionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fid = manageFacIdInput.value;
            const topic = document.getElementById('new-session-topic').value;
            const dt = document.getElementById('new-session-dt').value;
            const mode = document.getElementById('new-session-mode').value;

            const res = await fetch('api.php?action=add_session', {
                method: 'POST',
                body: JSON.stringify({ facilitator_id: fid, topic, date_time: dt, mode })
            });
            const data = await res.json();

            if (data.success) {
                addSessionForm.reset();
                await updateCalendar(true);
                loadFacilitatorSessions(fid);
            } else {
                alert('Error adding session slot.');
            }
        });
    }

    window.handleShatterAndBook = (btn, id, name) => {
        // Prevent multiple clicks
        if (btn.classList.contains('shattered')) return;
        btn.classList.add('shattered');

        const rect = btn.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;

        // Create particles
        const particleCount = 20;
        for (let i = 0; i < particleCount; i++) {
            createParticle(centerX, centerY);
        }

        // Hide button after a tiny delay
        setTimeout(() => {
            btn.style.opacity = '0';
            btn.style.transform = 'scale(0)';
        }, 50);

        // Redirect after the effect
        setTimeout(() => {
            openAdvancedBooking(selectedDate, id);
        }, 800);
    };

    function createParticle(x, y) {
        const particle = document.createElement('div');
        particle.className = 'shatter-particle';

        // Random trajectory
        const angle = Math.random() * Math.PI * 2;
        const velocity = 2 + Math.random() * 5;
        const vx = Math.cos(angle) * velocity;
        const vy = Math.sin(angle) * velocity;

        particle.style.left = `${x}px`;
        particle.style.top = `${y}px`;

        // Custom colors (blueish light)
        const colors = ['#ffffff', '#79c0ff', '#2f81f7', '#e0f2fe'];
        particle.style.background = colors[Math.floor(Math.random() * colors.length)];

        document.body.appendChild(particle);

        let posX = x;
        let posY = y;
        let opacity = 1;

        function animate() {
            posX += vx;
            posY += vy;
            opacity -= 0.02;

            particle.style.left = `${posX}px`;
            particle.style.top = `${posY}px`;
            particle.style.opacity = opacity;

            if (opacity > 0) {
                requestAnimationFrame(animate);
            } else {
                particle.remove();
            }
        }

        requestAnimationFrame(animate);
    }

    window.viewInstructorSlots = (id, name) => {
        facilitatorsModal.classList.remove('active');
        // Filter current list to only this instructor
        const filtered = allSessions.filter(s => s.facilitator_id == id);
        selectedDateLabel.textContent = `Sessions with ${name}`;
        renderSessions(filtered);
        // Scroll to sessions
        document.getElementById('sessions-grid').scrollIntoView({ behavior: 'smooth' });
    };



    closeSuccessBtn.addEventListener('click', () => {
        successModal.classList.remove('active');
        updateCalendar(true);
    });

    // Provide the dynamic style injector needed for local load instances
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes spinner { to { transform: rotate(360deg); } } 
        .spin { animation: spinner .6s linear infinite; }
        
        .shatter-particle {
            position: fixed;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            box-shadow: 0 0 10px rgba(255,255,255,0.8);
        }
        
        .shattered {
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
    `;
    document.head.appendChild(style);

    let allFacilitators = [];

    let allTopics = [];
    const modalFacList = document.getElementById('modal-instructor-list');

    async function loadTopics(deptId = null) {
        if (!advTopicSelect) return;
        try {
            let url = 'api.php?action=get_topics';
            if (deptId) url += `&department_id=${deptId}`;

            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                allTopics = data.topics;
                advTopicSelect.innerHTML = '<option value="" disabled selected>Select a topic...</option>';
                allTopics.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.name;
                    advTopicSelect.appendChild(opt);
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    // Load topics initially
    loadTopics();

    function getDisclaimerDepartmentContext() {
        const requesterDeptEl = document.getElementById('staff-req-dept');
        if (requesterDeptEl && requesterDeptEl.value) {
            const opt = requesterDeptEl.options[requesterDeptEl.selectedIndex];
            return {
                id: requesterDeptEl.value,
                label: opt ? opt.text : 'the selected requester department'
            };
        }

        if (currentUser && currentUser.department_id) {
            return {
                id: currentUser.department_id,
                label: currentUser.department_name || 'your current department'
            };
        }

        return null;
    }

    async function evaluateTopicDisclaimer(topicIdVal) {
        const disclaimer = document.getElementById('topic-disclaimer');
        if (!disclaimer) return;
        disclaimer.style.display = 'none';

        if (!topicIdVal) return;

        const deptCtx = getDisclaimerDepartmentContext();
        if (!deptCtx || !deptCtx.id) return;

        try {
            const res = await fetch(`api.php?action=get_topic_details&topic_id=${topicIdVal}`);
            const tDetails = await res.json();
            if (tDetails.success && tDetails.departments) {
                const coversTargetDept = tDetails.departments.some(d => d.id == deptCtx.id);
                if (!coversTargetDept) {
                    disclaimer.querySelector('.disclaimer-text').textContent = `Your chosen topic isn't under ${deptCtx.label}. Change it if this is a mistake.`;
                    disclaimer.style.display = 'flex';
                }
            }
        } catch (e) { }
    }

    if (advTopicSelect) {
        advTopicSelect.addEventListener('change', async (e) => {
            const topicIdVal = e.target.value;
            const isInstructional = advBookingType && advBookingType.value === 'Instructional Program';
            const hasDept = advDeptSelect && advDeptSelect.value;

            if (isInstructional && (!hasDept || !topicIdVal)) {
                if (modalFacList) {
                    const helperText = !hasDept
                        ? 'Select a department and topic to view facilitators.'
                        : 'Select a topic to view facilitators.';
                    modalFacList.innerHTML = `<div class="loader-container">${helperText}</div>`;
                }
                selectedFacId = null;
                renderTimeAxisZones();
                return;
            }

            if (modalFacList) modalFacList.innerHTML = '<div class="loader-container">Syncing faculty...</div>';

            try {
                let url = 'api.php?action=get_facilitators';
                if (topicIdVal !== 'All') {
                    url += `&topic_id=${topicIdVal}`;
                }
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) {
                    renderModalInstructors(data.facilitators);
                }
            } catch (err) { }

            await evaluateTopicDisclaimer(topicIdVal);

            selectedFacId = null;
        });
    }

    const advDeptSelect = document.getElementById('adv-dept-select');
    const staffReqDeptSelect = document.getElementById('staff-req-dept');
    if (advDeptSelect) {
        advDeptSelect.addEventListener('change', (e) => {
            loadTopics(e.target.value);
            selectedFacId = null;
            if (modalFacList && advBookingType && advBookingType.value === 'Instructional Program') {
                modalFacList.innerHTML = '<div class="loader-container">Select a topic to view facilitators.</div>';
            }
            const disclaimer = document.getElementById('topic-disclaimer');
            if (disclaimer) disclaimer.style.display = 'none';
            renderTimeAxisZones();
        });
    }

    if (staffReqDeptSelect) {
        staffReqDeptSelect.addEventListener('change', async () => {
            if (!advTopicSelect || !advTopicSelect.value) return;
            await evaluateTopicDisclaimer(advTopicSelect.value);
        });
    }

    // Attach event listeners for the advanced booking type and time inputs
    const advBookingType = document.getElementById('adv-booking-type');
    const bookingStartTime = document.getElementById('booking-start-time');
    const bookingEndTime = document.getElementById('booking-end-time');

    if (advBookingType) {
        advBookingType.addEventListener('change', handleBookingTypeChange);
    }
    if (bookingStartTime) {
        bookingStartTime.addEventListener('change', validateBookingTime);
    }
    if (bookingEndTime) {
        bookingEndTime.addEventListener('change', validateBookingTime);
    }

    function handleBookingTypeChange() {
        if (!advBookingType) return;
        const type = advBookingType.value;
        const topicSec = document.getElementById('topic-section');
        const instSec = document.getElementById('instructor-section');
        const timeSec = document.getElementById('time-selection-section');
        const standardTimeInputs = document.getElementById('standard-time-inputs');
        const wholeDayNotice = document.getElementById('whole-day-notice');
        const timeAxisWrapper = document.querySelector('.time-axis-container');
        const timeLabel = document.getElementById('time-label');
        const durationHint = document.getElementById('time-duration-hint');
        const errorEl = document.getElementById('time-error-msg');

        if (!topicSec || !instSec || !timeSec) return;

        if (type === 'Instructional Program') {
            document.getElementById('dept-section').style.display = 'block';
            topicSec.style.display = 'block';
            instSec.style.display = 'block';
            if (modalFacList) {
                const hasDept = advDeptSelect && advDeptSelect.value;
                const hasTopic = advTopicSelect && advTopicSelect.value;
                if (!hasDept) {
                    modalFacList.innerHTML = '<div class="loader-container">Select a department and topic to view facilitators.</div>';
                } else if (!hasTopic) {
                    modalFacList.innerHTML = '<div class="loader-container">Select a topic to view facilitators.</div>';
                }
            }
        } else {
            document.getElementById('dept-section').style.display = 'none';
            topicSec.style.display = 'none';
            instSec.style.display = 'none';
            selectedFacId = null;
            if (modalFacList) modalFacList.innerHTML = '';
            renderTimeAxisZones();
        }

        if (type) {
            timeSec.style.display = 'block';

            if (type === 'Seminar') {
                // Seminar = whole day, hide time inputs
                if (standardTimeInputs) standardTimeInputs.style.display = 'none';
                if (timeAxisWrapper) timeAxisWrapper.style.display = 'none';
                if (wholeDayNotice) wholeDayNotice.style.display = 'flex';
                if (timeLabel) timeLabel.textContent = 'Time: (Seminars are whole-day events)';
                if (durationHint) durationHint.textContent = '';
                if (errorEl) { errorEl.textContent = ''; errorEl.style.display = 'none'; }
                // Set hidden time values to full day for the API
                const st = document.getElementById('booking-start-time');
                const et = document.getElementById('booking-end-time');
                if (st) st.value = '09:00';
                if (et) et.value = '20:00';
            } else {
                // Standard types (Instructional / Orientation)
                if (standardTimeInputs) standardTimeInputs.style.display = 'block';
                if (timeAxisWrapper) timeAxisWrapper.style.display = 'block';
                if (wholeDayNotice) wholeDayNotice.style.display = 'none';
                if (timeLabel) timeLabel.textContent = 'Pick a Time:';
                // Update hint based on type
                if (durationHint) {
                    durationHint.textContent = 'Minimum 30 minutes · Maximum 4 hours';
                }
                validateBookingTime();
            }
        } else {
            timeSec.style.display = 'none';
        }
    }

    function validateBookingTime() {
        if (!advBookingType) return true;
        const type = advBookingType.value;

        // Seminars are always whole-day — skip validation
        if (type === 'Seminar') return true;

        const startObj = bookingStartTime;
        const endObj = bookingEndTime;
        const errorEl = document.getElementById('time-error-msg');

        if (!errorEl || !startObj || !endObj) return true;

        // Clear error immediately if either field is empty
        if (!startObj.value || !endObj.value) {
            errorEl.textContent = '';
            errorEl.style.display = 'none';
            return true;
        }

        const [h1, m1] = startObj.value.split(':').map(Number);
        const [h2, m2] = endObj.value.split(':').map(Number);
        const startMins = h1 * 60 + m1;
        const endMins = h2 * 60 + m2;
        const diff = endMins - startMins;

        // 9 AM (540) to 8 PM (1200)
        if (startMins < 540 || endMins > 1200) {
            errorEl.textContent = 'Time selection is limited from 9:00 AM to 8:00 PM.';
            errorEl.style.display = 'block';
            return false;
        }

        // Must not be in the past (today only)
        const now = new Date();
        if (selectedDate === formatLocalDate(now)) {
            const currentMins = now.getHours() * 60 + now.getMinutes();
            if (startMins < currentMins + 5) {
                errorEl.textContent = 'You cannot book an appointment for a time that has already passed.';
                errorEl.style.display = 'block';
                return false;
            }
        }

        if (endMins <= startMins) {
            errorEl.textContent = 'End time must be after start time.';
            errorEl.style.display = 'block';
            return false;
        }

        // 4. Duration limits for Instructional Program and Orientation
        if (type === 'Instructional Program' || type === 'Orientation') {
            if (diff < 30) {
                errorEl.textContent = `${type}s must be at least 30 minutes.`;
                errorEl.style.display = 'block';
                return false;
            }
            if (diff > 240) {
                errorEl.textContent = `${type}s cannot exceed 4 hours.`;
                errorEl.style.display = 'block';
                return false;
            }
        }

        // 5. Facilitator Conflict Check (Instructional Program only)
        if (type === 'Instructional Program' && selectedFacId) {
            const conflict = allSessions.find(s => {
                if (s.facilitator_id != selectedFacId || s.status !== 'CONFIRMED') return false;
                if (!s.date_time.startsWith(selectedDate)) return false;

                const sTimeStr = s.date_time.split(' ')[1];
                const eTimeStr = s.end_time ? s.end_time.split(' ')[1] : null;
                if (!sTimeStr || !eTimeStr) return false;

                const [sh, sm] = sTimeStr.split(':').map(Number);
                const [eh, em] = eTimeStr.split(':').map(Number);
                const sMins = sh * 60 + sm;
                const eMins = eh * 60 + em;

                return (startMins < eMins && endMins > sMins);
            });

            if (conflict) {
                errorEl.textContent = 'The selected facilitator is already booked at this time.';
                errorEl.style.display = 'block';
                return false;
            }
        }

        // All good — clear any previous error
        errorEl.textContent = '';
        errorEl.style.display = 'none';
        return true;
    }

    function openAdvancedBooking(date, preSelectFacId = null) {
        const dateObj = new Date(date);
        if (dateObj.getDay() === 0) {
            alert('Library bookings are closed on Sundays. Please choose another date.');
            return;
        }

        const formattedDate = dateObj.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });

        const dateDisplay = document.getElementById('booking-date-display');
        if (dateDisplay) dateDisplay.textContent = formattedDate;

        selectedFacId = preSelectFacId;

        // Reset new fields
        const typeSelect = document.getElementById('adv-booking-type');
        if (typeSelect) {
            typeSelect.value = preSelectFacId ? 'Instructional Program' : '';
        }

        const startTime = document.getElementById('booking-start-time');
        const endTime = document.getElementById('booking-end-time');
        if (startTime) startTime.value = '';
        if (endTime) endTime.value = '';

        const errorEl = document.getElementById('time-error-msg');
        if (errorEl) { errorEl.textContent = ''; errorEl.style.display = 'none'; }

        // Reset seminar / standard time UI state
        const wholeDayNotice = document.getElementById('whole-day-notice');
        const standardTimeInputs = document.getElementById('standard-time-inputs');
        const timeAxisWrapper = document.querySelector('.time-axis-container');
        const durationHint = document.getElementById('time-duration-hint');
        if (wholeDayNotice) wholeDayNotice.style.display = 'none';
        if (standardTimeInputs) standardTimeInputs.style.display = 'block';
        if (timeAxisWrapper) timeAxisWrapper.style.display = 'block';
        if (durationHint) durationHint.textContent = '';

        const reqDeptSelect = document.getElementById('staff-req-dept');
        if (reqDeptSelect) {
            reqDeptSelect.innerHTML = '<option value="" disabled selected>Select department...</option>';
            allDepartments.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.name;
                reqDeptSelect.appendChild(opt);
            });
        }

        if (advDeptSelect) {
            advDeptSelect.innerHTML = '<option value="" disabled selected>Select a department...</option>';
            allDepartments.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.name;
                advDeptSelect.appendChild(opt);
            });
            advDeptSelect.value = '';
        }

        if (advTopicSelect) {
            advTopicSelect.innerHTML = '<option value="" disabled selected>Select a department first...</option>';
            advTopicSelect.value = '';
        }

        const disclaimer = document.getElementById('topic-disclaimer');
        if (disclaimer) disclaimer.style.display = 'none';

        loadModalInstructors();
        renderTimeAxisZones();
        advBookingModal.classList.add('active');

        // Ensure UI state is updated
        if (typeof handleBookingTypeChange === 'function') {
            handleBookingTypeChange();
        }
    }

    window.closeAdvancedBooking = () => {
        advBookingModal.classList.remove('active');
    };

    function renderTimeAxisZones() {
        const container = document.getElementById('axis-zones-container');
        if (!container) return;
        container.innerHTML = '';

        // Consts for 9AM - 8PM (11 hours)
        const startH = 9;
        const totalH = 11;

        // 1. Lunch Break (Fixed 12PM - 1PM)
        addZone(12, 13, 'zone-lunch');

        // 2. Instructor Bookings
        if (selectedFacId) {
            const bookings = allSessions.filter(s =>
                s.facilitator_id == selectedFacId &&
                s.date_time.startsWith(selectedDate) &&
                s.booking_status === 'CONFIRMED'
            );

            bookings.forEach(b => {
                const bDate = new Date(b.date_time);
                const sH = bDate.getHours() + (bDate.getMinutes() / 60);

                // Estimate end time if missing (default 1h)
                let eH = sH + 1;
                if (b.end_time) {
                    const eDate = new Date(b.end_time);
                    eH = eDate.getHours() + (eDate.getMinutes() / 60);
                }
                addZone(sH, eH, 'zone-booked');
            });
        }

        // 3. Current Selection
        const sTime = document.getElementById('booking-start-time').value;
        const eTime = document.getElementById('booking-end-time').value;
        if (sTime && eTime) {
            const [h1, m1] = sTime.split(':').map(Number);
            const [h2, m2] = eTime.split(':').map(Number);
            const startVal = h1 + (m1 / 60);
            const endVal = h2 + (m2 / 60);
            addZone(startVal, endVal, 'zone-selected');
        }

        function addZone(s, e, className) {
            if (e <= startH || s >= startH + totalH) return;
            const left = Math.max(0, ((s - startH) / totalH) * 100);
            const width = Math.min(100 - left, ((e - s) / totalH) * 100);

            if (width <= 0) return;

            const zone = document.createElement('div');
            zone.className = `axis-zone ${className}`;
            zone.style.left = `${left}%`;
            zone.style.width = `${width}%`;

            // Format time for tooltip
            const formatHour = (h) => {
                const hour = Math.floor(h);
                const mins = Math.round((h - hour) * 60);
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const displayH = hour % 12 || 12;
                return `${displayH}:${String(mins).padStart(2, '0')} ${ampm}`;
            };

            const sLabel = formatHour(s);
            const eLabel = formatHour(e);
            const typeLabel = className === 'zone-lunch' ? 'Lunch Break' : (className === 'zone-booked' ? 'Already Booked' : 'Your Selection');
            zone.title = `${typeLabel}: ${sLabel} - ${eLabel}`;

            container.appendChild(zone);
        }
    }

    // Attach listeners for axis live updates
    document.addEventListener('input', (e) => {
        if (e.target.id === 'booking-start-time' || e.target.id === 'booking-end-time') {
            renderTimeAxisZones();
            validateBookingTime();
        }
    });

    async function loadModalInstructors() {
        if (!modalFacList) return;

        if (!advBookingType || advBookingType.value !== 'Instructional Program') {
            modalFacList.innerHTML = '';
            return;
        }

        const hasDept = advDeptSelect && advDeptSelect.value;
        const hasTopic = advTopicSelect && advTopicSelect.value;

        if (!hasDept) {
            modalFacList.innerHTML = '<div class="loader-container">Select a department and topic to view facilitators.</div>';
            return;
        }

        if (!hasTopic) {
            modalFacList.innerHTML = '<div class="loader-container">Select a topic to view facilitators.</div>';
            return;
        }

        // Trigger the change event to fetch initial instructors based on selected topic
        if (advTopicSelect) {
            advTopicSelect.dispatchEvent(new Event('change'));
        }
    }

    function renderModalInstructors(facilitators) {
        modalFacList.innerHTML = '';
        if (!facilitators || facilitators.length === 0) {
            modalFacList.innerHTML = '<div class="loader-container">No facilitators available for the selected topic.</div>';
            return;
        }

        facilitators.forEach(f => {
            const div = document.createElement('div');
            div.className = 'fac-card-new';
            div.innerHTML = `
                <div class="fac-avatar-new">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <div class="fac-info-new">
                    <h5>${f.name}</h5>
                    <p>${f.position || 'Library Faculty'}</p>
                </div>
            `;

            div.addEventListener('click', () => {
                document.querySelectorAll('.fac-card-new').forEach(c => c.classList.remove('selected'));
                div.classList.add('selected');
                selectedFacId = f.id;
                renderTimeAxisZones();
            });

            if (selectedFacId && f.id == selectedFacId) {
                div.classList.add('selected');
                renderTimeAxisZones();
            }

            modalFacList.appendChild(div);
        });
    }

    // generateAdvancedSlots removed as we now use custom time range inputs


    // Helper to format time for comparison
    function formatTimeTo24h(timeStr) {
        const [time, modifier] = timeStr.split(' ');
        let [hours, minutes] = time.split(':');
        if (hours === '12') {
            hours = modifier === 'AM' ? '00' : '12';
        } else if (modifier === 'PM') {
            hours = parseInt(hours, 10) + 12;
        }
        return `${String(hours).padStart(2, '0')}:${minutes}:00`;
    }

    if (advBookingForm) {
        advBookingForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const selectedDateObj = parseLocalDate(selectedDate);
            if (selectedDateObj.getDay() === 0) {
                alert('Library bookings are closed on Sundays. Please choose another date.');
                return;
            }

            const type = document.getElementById('adv-booking-type').value;
            const startTime = document.getElementById('booking-start-time').value;
            const endTime = document.getElementById('booking-end-time').value;

            if (!type) {
                alert('Please select a booking type.');
                return;
            }

            // For Seminars, times are auto-set to full-day — skip manual time check
            if (type !== 'Seminar' && (!startTime || !endTime)) {
                alert('Please select both start and end times.');
                return;
            }

            if (type === 'Instructional Program' && !selectedFacId) {
                alert('Please select an instructor for the Instructional Program.');
                return;
            }

            if (typeof validateBookingTime === 'function' && !validateBookingTime()) {
                return;
            }

            const btn = document.getElementById('btn-confirm-advanced');
            const orig = btn.textContent;
            btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="spin"><circle cx="12" cy="12" r="10" stroke="rgba(255,255,255,0.3)" stroke-width="3"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke="#fff" stroke-width="3"></path></svg> Linking...';
            btn.disabled = true;

            const mode = document.querySelector('input[name="book-mode"]:checked').value;

            let finalTopic = type;
            let finalFacId = selectedFacId || 1; // Default to first available or handle in API

            if (type === 'Instructional Program') {
                finalTopic = (advTopicSelect && advTopicSelect.value) ? advTopicSelect.options[advTopicSelect.selectedIndex].text : 'Library Consultation';
            }

            const customRequestor = {};
            const reqName = document.getElementById('staff-req-name')?.value?.trim();
            const reqEmail = document.getElementById('staff-req-email')?.value?.trim();
            const reqDept = document.getElementById('staff-req-dept');
            const isFacilitator = !!(currentUser && currentUser.facilitator_id);

            if (isFacilitator) {
                if (!reqName || !reqEmail || !(reqDept && reqDept.value)) {
                    alert('As a facilitator, requester name, email, and department are required.');
                    btn.textContent = orig;
                    btn.disabled = false;
                    return;
                }
            }

            if (reqName || reqEmail || (reqDept && reqDept.value)) {
                customRequestor.name = reqName;
                customRequestor.email = reqEmail;
                customRequestor.dept_id = reqDept.value;
                customRequestor.dept_name = reqDept.options[reqDept.selectedIndex]?.text;
            }

            const payload = {
                type: type,
                topic: finalTopic,
                name: document.getElementById('book-name').value,
                email: document.getElementById('book-email').value,
                phone: document.getElementById('book-phone').value,
                department: (currentUser && currentUser.department_id) ? currentUser.department_id : '',
                notes: document.getElementById('book-notes').value,
                mode: mode,
                facilitator_id: finalFacId,
                date: selectedDate,
                date_time: `${selectedDate} ${startTime}:00`,
                end_time: `${selectedDate} ${endTime}:00`,
                custom_requestor: Object.keys(customRequestor).length > 0 ? customRequestor : null
            };

            try {
                const res = await fetch('api.php?action=advanced_booking', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                btn.textContent = orig;
                btn.disabled = false;

                if (data.success) {
                    advBookingModal.classList.remove('active');
                    advBookingForm.reset();
                    selectedFacId = null;

                    document.getElementById('success-modal').classList.add('active');
                    await updateCalendar(true);


                } else {
                    alert('Error: ' + (data.message || 'The system could not establish the link.'));
                }
            } catch (e) {
                console.error(e);
                btn.textContent = orig;
                btn.disabled = false;
            }
        });
    }

    let appointmentsCache = [];
    let appointmentsSubview = 'active';

    const CLOSED_APPOINTMENT_STATUSES = new Set(['CANCELLED', 'DECLINED']);
    const COMPLETED_APPOINTMENT_STATUS = 'COMPLETED';

    function isCancelledOrDeclinedStatus(status) {
        return CLOSED_APPOINTMENT_STATUSES.has(String(status || '').trim().toUpperCase());
    }

    function isCompletedStatus(status) {
        return String(status || '').trim().toUpperCase() === COMPLETED_APPOINTMENT_STATUS;
    }

    function getClosedStatusLabel(status) {
        return String(status || '').trim().toUpperCase() === 'DECLINED' ? 'Declined' : 'Cancelled';
    }

    function setAppointmentsSubview(view) {
        if (view === 'cancelled' || view === 'completed') {
            appointmentsSubview = view;
        } else {
            appointmentsSubview = 'active';
        }

        const subtabButtons = document.querySelectorAll('.appointment-subtab-btn');
        subtabButtons.forEach(btn => {
            const isActive = btn.getAttribute('data-view') === appointmentsSubview;
            btn.classList.toggle('active', isActive);
            btn.classList.toggle('btn-primary', isActive);
            btn.classList.toggle('btn-outline', !isActive);
        });

        renderAppointments(appointmentsCache);
    }

    function initAppointmentsSubtabs() {
        const activeBtn = document.getElementById('appointments-subtab-active');
        const cancelledBtn = document.getElementById('appointments-subtab-cancelled');
        const completedBtn = document.getElementById('appointments-subtab-completed');

        if (activeBtn) {
            activeBtn.addEventListener('click', () => setAppointmentsSubview('active'));
        }

        if (cancelledBtn) {
            cancelledBtn.addEventListener('click', () => setAppointmentsSubview('cancelled'));
        }

        if (completedBtn) {
            completedBtn.addEventListener('click', () => setAppointmentsSubview('completed'));
        }
    }

    initAppointmentsSubtabs();

    async function loadAppointments() {
        const grid = document.getElementById('my-appointments-grid');
        if (!grid) return;

        grid.innerHTML = '<div class="loader-container">Loading appointments...</div>';

        // Ensure facilitators are loaded for admin view
        if (isFacilitatorAuthenticated && (!allFacilitators || allFacilitators.length === 0)) {
            try {
                const resF = await fetch('api.php?action=get_facilitators');
                const dataF = await resF.json();
                if (dataF.success) allFacilitators = dataF.facilitators;
            } catch (e) { }
        }

        try {
            const res = await fetch('api.php?action=get_appointments&my_appointments=true');
            const data = await res.json();

            if (data.success) {
                appointmentsCache = Array.isArray(data.appointments) ? data.appointments : [];
                renderAppointments(appointmentsCache);
            } else {
                grid.innerHTML = '<p>Failed to load appointments.</p>';
            }
        } catch (e) {
            console.error(e);
            grid.innerHTML = '<p>Error loading appointments.</p>';
        }
    }

    function renderAppointments(apps) {
        const grid = document.getElementById('my-appointments-grid');
        const allApps = Array.isArray(apps) ? apps : [];
        const filteredApps = allApps.filter(app => {
            const normalizedStatus = String(app.booking_status || '').trim().toUpperCase();
            if (appointmentsSubview === 'cancelled') {
                return isCancelledOrDeclinedStatus(normalizedStatus);
            } else if (appointmentsSubview === 'completed') {
                return isCompletedStatus(normalizedStatus);
            } else {
                return !isCancelledOrDeclinedStatus(normalizedStatus) && !isCompletedStatus(normalizedStatus);
            }
        });

        if (filteredApps.length === 0) {
            const emptyText = appointmentsSubview === 'cancelled'
                ? 'No cancelled/declined appointments found.'
                : appointmentsSubview === 'completed'
                    ? 'No completed appointments found.'
                    : 'No appointments found.';
            grid.innerHTML = `<div class="loader-container">${emptyText}</div>`;
            return;
        }

        grid.innerHTML = '';
        filteredApps.forEach(app => {
            const dateObj = new Date(app.date_time.replace(/-/g, '/'));
            const dateStr = !isNaN(dateObj) ? dateObj.toLocaleDateString() : app.date_time.split(' ')[0];
            const timeStr = !isNaN(dateObj) ? dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : app.date_time.split(' ')[1];
            const normalizedStatus = String(app.booking_status).trim().toUpperCase();
            const isClosed = isCancelledOrDeclinedStatus(normalizedStatus);
            const cancelledByText = String(app.cancelled_by || '').trim();
            const cancelledByAdmin = cancelledByText !== '' && /admin/i.test(cancelledByText);
            const lockedByStudentCancellation = normalizedStatus === 'CANCELLED' && !cancelledByAdmin;

            let endTimeStr = 'N/A';
            if (app.end_time) {
                const eObj = new Date(app.end_time.replace(/-/g, '/'));
                endTimeStr = !isNaN(eObj) ? eObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : app.end_time.split(' ')[1] || 'N/A';
            }

            const card = document.createElement('div');
            card.className = 'session-card';

            const isFacilitatorBooking = currentUser && currentUser.facilitator_id && app.facilitator_id == currentUser.facilitator_id;
            const isOwnBooking = currentUser && app.student_name && app.student_name === currentUser.name;

            let html = `
                <div class="session-info">
                    <h4>${app.appointment_type || 'Consultation'} ${app.topic && app.topic !== app.appointment_type ? `- ${app.topic}` : ''}</h4>
                    <p style="margin-bottom: 0.5rem"><strong>Date:</strong> ${dateStr} (${timeStr} - ${endTimeStr})</p>
                    <p style="margin-bottom: 0.5rem"><strong>Mode:</strong> ${app.mode}</p>
                    <p style="margin-bottom: 0.5rem"><strong>Status:</strong> ${app.booking_status}</p>
            `;

            if (isFacilitatorBooking && !isOwnBooking) {
                html += `<p style="margin-bottom: 0.5rem"><strong>Role:</strong> Assigned Facilitator</p>`;
            }

            if (isFacilitatorAuthenticated) {
                html += `
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border);">
                        <strong>Admin Controls:</strong>
                        ${isClosed ? `
                            <div style="margin-top: 0.65rem; margin-bottom: 0.75rem; padding: 0.7rem; background: #fef2f2; border-left: 3px solid var(--danger); border-radius: 4px;">
                                ${app.cancelled_date_time ? `<p style="margin: 0 0 0.35rem 0; font-size: 0.85rem; color: #64748b;"><strong>${getClosedStatusLabel(normalizedStatus)} on:</strong> ${new Date(app.cancelled_date_time).toLocaleString()}</p>` : ''}
                                ${cancelledByText ? `<p style="margin: 0 0 0.35rem 0; font-size: 0.85rem; color: #64748b;"><strong>${getClosedStatusLabel(normalizedStatus)} by:</strong> ${cancelledByText}</p>` : ''}
                                <p style="margin: 0; font-size: 0.85rem; color: #64748b;"><strong>Reason:</strong> ${app.cancellation_reason ? app.cancellation_reason : 'No reason provided'}</p>
                            </div>
                        ` : ''}
                        ${lockedByStudentCancellation ? `<p style="margin: 0.35rem 0 0.75rem 0; font-size: 0.82rem; color: #9a3412; background: #fff7ed; border-left: 3px solid #f97316; padding: 0.55rem 0.7rem; border-radius: 4px;">This appointment was cancelled by a student and is read-only.</p>` : ''}
                        <div class="form-group" style="margin-top: 0.5rem;">
                            <label>Status</label>
                            <select id="status-${app.session_id}" class="login-input" ${lockedByStudentCancellation ? 'disabled' : ''}>
                                <option value="PENDING" ${app.booking_status === 'PENDING' ? 'selected' : ''}>Pending</option>
                                <option value="CONFIRMED" ${app.booking_status === 'CONFIRMED' ? 'selected' : ''}>Confirmed</option>
                                <option value="COMPLETED" ${app.booking_status === 'COMPLETED' ? 'selected' : ''}>Completed</option>
                                <option value="CANCELLED" ${String(app.booking_status).toUpperCase() === 'CANCELLED' ? 'selected' : ''}>CANCELLED</option>
                                <option value="DECLINED" ${String(app.booking_status).toUpperCase() === 'DECLINED' ? 'selected' : ''}>DECLINED</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Venue</label>
                            <input type="text" id="venue-${app.session_id}" class="login-input" value="${app.venue || 'TBA'}" ${lockedByStudentCancellation ? 'disabled' : ''}>
                        </div>
                        <div class="form-group">
                            <label>Facilitator</label>
                            <select id="fac-${app.session_id}" class="login-input" ${lockedByStudentCancellation ? 'disabled' : ''}>
                                <option value="null">TBA</option>
                                ${(allFacilitators || []).map(f => `<option value="${f.id}" ${app.facilitator_id == f.id ? 'selected' : ''}>${f.name}</option>`).join('')}
                            </select>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="saveAppointmentAdmin(${app.session_id})" style="width: 100%; margin-top: 0.5rem; ${lockedByStudentCancellation ? 'opacity: 0.6; cursor: not-allowed;' : ''}" ${lockedByStudentCancellation ? 'disabled' : ''}>Save Changes</button>
                    </div>
                `;
            } else {
                html += `
                    <p style="margin-bottom: 0.5rem"><strong>Venue:</strong> ${app.venue || 'TBA'}</p>
                    <p style="margin-bottom: 0.5rem"><strong>Instructor:</strong> ${app.facilitator_name || 'TBA'}</p>
                    ${normalizedStatus === 'COMPLETED' ? `
                        <div style="margin-top: 1rem; padding: 0.75rem; background: #f0fdf4; border-left: 3px solid #22c55e; border-radius: 4px;">
                            <p style="margin: 0 0 0.5rem 0; color: #22c55e; font-weight: 600;">✓ Completed</p>
                            ${app.evaluation_notes ? `<p style="margin: 0; font-size: 0.85rem; color: #64748b;"><strong>Notes:</strong> ${app.evaluation_notes}</p>` : ''}
                        </div>
                    ` : ''}
                    ${isClosed ? `
                        <div style="margin-top: 1rem; padding: 0.75rem; background: #fef2f2; border-left: 3px solid var(--danger); border-radius: 4px;">
                            <p style="margin: 0 0 0.5rem 0; color: var(--danger); font-weight: 600;">${getClosedStatusLabel(normalizedStatus)}</p>
                            ${app.cancelled_date_time ? `<p style="margin: 0 0 0.5rem 0; font-size: 0.85rem; color: #64748b;">${getClosedStatusLabel(normalizedStatus)} on ${new Date(app.cancelled_date_time).toLocaleString()}</p>` : ''}
                            ${app.cancelled_by ? `<p style="margin: 0 0 0.5rem 0; font-size: 0.85rem; color: #64748b;"><strong>By:</strong> ${app.cancelled_by}</p>` : ''}
                            ${app.cancellation_reason ? `<p style="margin: 0; font-size: 0.85rem; color: #64748b;"><strong>Reason:</strong> ${app.cancellation_reason}</p>` : ''}
                        </div>
                    ` : ''}
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        ${!isClosed && normalizedStatus !== 'COMPLETED' ? `<button class="btn btn-outline btn-sm" onclick="cancelAppointmentUser(${app.session_id})" style="color: var(--danger); border-color: var(--danger);">Cancel</button>` : ''}
                        ${app.appointment_type === 'Instructional Program' && app.facilitator_id && !isClosed && normalizedStatus !== 'COMPLETED' ? `<button class="btn btn-muted btn-sm" onclick="changeInstructor(${app.session_id}, '${String(app.topic || '').replace(/'/g, "\\'")}', ${app.facilitator_id || 'null'})">Change Instructor</button>` : ''}
                    </div>
                `;
            }

            html += `</div>`;
            card.innerHTML = html;
            grid.appendChild(card);
        });
    }

    window.saveAppointmentAdmin = async (bookingId) => {
        const statusEl = document.getElementById(`status-${bookingId}`);
        if (!statusEl || statusEl.disabled) return;

        const st = document.getElementById(`status-${bookingId}`).value;
        const vn = document.getElementById(`venue-${bookingId}`).value;
        const fc = document.getElementById(`fac-${bookingId}`).value;
        let cancellationReason = null;
        let cancelledBy = null;
        let evaluationNotes = null;

        const normalizedStatus = String(st).trim().toUpperCase();
        if (normalizedStatus === 'COMPLETED') {
            const modalResult = await promptCompletionEvaluation();
            if (modalResult === null) return;
            evaluationNotes = modalResult.message;
        } else if (normalizedStatus === 'CONFIRMED') {
            if (typeof window.promptConfirmedNote === 'function') {
                const modalResult = await window.promptConfirmedNote();
                if (modalResult === null) return;
                evaluationNotes = modalResult.message;
            }
        } else if (normalizedStatus === 'CANCELLED' || normalizedStatus === 'DECLINED') {
            const actionWord = normalizedStatus === 'DECLINED' ? 'declined' : 'cancelled';
            const reasonInput = await openCancellationReasonModal({
                title: normalizedStatus === 'DECLINED' ? 'Decline This Appointment?' : 'Cancel This Appointment?',
                message: `This appointment will be marked as ${actionWord.toUpperCase()}. You may optionally include a reason.`,
                confirmText: normalizedStatus === 'DECLINED' ? 'Yes, Decline Appointment' : 'Yes, Cancel Appointment',
                cancelText: 'Go Back',
                reasonLabel: normalizedStatus === 'DECLINED' ? 'Decline reason (optional)' : 'Cancellation reason (optional)',
                reasonPlaceholder: normalizedStatus === 'DECLINED' ? 'Type a reason for declining, or leave blank to continue...' : 'Type a reason for cancellation, or leave blank to continue...'
            });
            if (reasonInput === null) return;
            cancellationReason = reasonInput;
            cancelledBy = currentUser ? currentUser.name : 'Admin';
        }

        try {
            const res = await fetch('api.php?action=update_appointment', {
                method: 'POST',
                body: JSON.stringify({ id: bookingId, status: st, venue: vn, facilitator_id: fc, cancellation_reason: cancellationReason, cancelled_by: cancelledBy, evaluation_notes: evaluationNotes })
            });
            const data = await res.json();
            if (data.success) {
                loadAppointments();
            } else {
                alert('Failed to update.');
            }
        } catch (e) {
            console.error(e);
        }
    };

    window.cancelAppointmentUser = async (bookingId) => {
        const reasonInput = await openCancellationReasonModal({
            title: 'Cancel Appointment?',
            message: 'You can tell us why you are cancelling, or leave it blank and continue.',
            confirmText: 'Confirm Cancellation',
            cancelText: 'Keep Appointment'
        });
        if (reasonInput === null) return;

        const cancelledBy = currentUser ? currentUser.name : 'User';

        try {
            const res = await fetch('api.php?action=cancel_appointment', {
                method: 'POST',
                body: JSON.stringify({ id: bookingId, cancellation_reason: reasonInput, cancelled_by: cancelledBy })
            });
            const data = await res.json();
            if (data.success) {
                loadAppointments();
                updateCalendar(true);
            }
        } catch (e) { }
    };

    window.changeInstructor = async (bookingId, topicName, currentFacilitatorId = null) => {
        let topicId = null;

        try {
            if (!allTopics || !allTopics.length) {
                const topicsRes = await fetch('api.php?action=get_topics');
                const topicsData = await topicsRes.json();
                if (topicsData.success && Array.isArray(topicsData.topics)) {
                    allTopics = topicsData.topics;
                }
            }

            const match = (allTopics || []).find(t =>
                String(t.name || '').trim().toLowerCase() === String(topicName || '').trim().toLowerCase()
            );
            topicId = match ? match.id : null;
        } catch (e) { }

        if (!topicId) {
            alert('No facilitator list found for this topic.');
            return;
        }

        let facilitators = [];
        try {
            const resF = await fetch(`api.php?action=get_facilitators&topic_id=${topicId}`);
            const dataF = await resF.json();
            if (dataF.success && Array.isArray(dataF.facilitators)) {
                facilitators = dataF.facilitators;
            }
        } catch (e) { }

        if (!facilitators.length) {
            alert('No available facilitators found for this topic.');
            return;
        }

        const selectedFacilitatorId = await openChangeInstructorModal(facilitators, currentFacilitatorId);
        if (!selectedFacilitatorId) return;

        try {
            const res = await fetch('api.php?action=change_instructor', {
                method: 'POST',
                body: JSON.stringify({ id: bookingId, facilitator_id: selectedFacilitatorId })
            });
            const data = await res.json();
            if (data.success) {
                loadAppointments();
            }
        } catch (e) { }
    };


    // === MINIMAL MINI CALENDAR LOGIC ===
    let miniCurrentMonth = new Date().getMonth();
    let miniCurrentYear = new Date().getFullYear();

    function renderMiniCalendar() {
        const miniGrid = document.getElementById('mini-calendar-grid');
        const miniDateDisplay = document.getElementById('mini-current-date');
        if (!miniGrid) return;

        miniGrid.innerHTML = '';

        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        if (miniDateDisplay) {
            miniDateDisplay.textContent = `${monthNames[miniCurrentMonth]} ${miniCurrentYear}`;
        }

        // Days of week header
        ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => {
            const el = document.createElement('div');
            el.className = 'mini-day-name';
            el.textContent = day; // Use full 3 letters as in second image
            miniGrid.appendChild(el);
        });

        const firstDay = new Date(miniCurrentYear, miniCurrentMonth, 1).getDay();
        const daysInMonth = new Date(miniCurrentYear, miniCurrentMonth + 1, 0).getDate();
        const prevMonthLastDay = new Date(miniCurrentYear, miniCurrentMonth, 0).getDate();

        const now = new Date();
        const todayStr = formatLocalDate(now);
        const gapDate = new Date();
        gapDate.setDate(now.getDate() + 2);

        // Previous month padding cells
        for (let i = firstDay - 1; i >= 0; i--) {
            const dayNum = prevMonthLastDay - i;
            const el = document.createElement('div');
            el.className = 'mini-day empty';
            el.textContent = dayNum;
            miniGrid.appendChild(el);
        }

        for (let i = 1; i <= daysInMonth; i++) {
            const dayEl = document.createElement('div');
            dayEl.className = 'mini-day';
            dayEl.textContent = i;

            const thisDate = new Date(miniCurrentYear, miniCurrentMonth, i);
            const dateStr = formatLocalDate(thisDate);

            if (dateStr === todayStr) dayEl.classList.add('today');
            if (dateStr === selectedDate) dayEl.classList.add('selected');

            // Sunday restriction or past/near-past
            if (thisDate < gapDate || thisDate.getDay() === 0) dayEl.classList.add('restricted');

            dayEl.addEventListener('click', () => {
                if (dayEl.classList.contains('restricted')) {
                    if (thisDate.getDay() === 0) {
                        alert('Library is closed on Sundays.');
                    } else {
                        alert('Bookings must be made 2 days in advance.');
                    }
                    return;
                }

                selectedDate = dateStr;
                document.querySelectorAll('.mini-day').forEach(d => d.classList.remove('selected'));
                dayEl.classList.add('selected');

                if (typeof openAdvancedBooking === 'function') {
                    openAdvancedBooking(dateStr);
                }
            });

            miniGrid.appendChild(dayEl);
        }

        // Next month padding cells
        const totalUsed = firstDay + daysInMonth;
        const paddingNeeded = 42 - totalUsed;
        for (let i = 1; i <= paddingNeeded; i++) {
            const el = document.createElement('div');
            el.className = 'mini-day empty';
            el.textContent = i;
            miniGrid.appendChild(el);
        }
    }

    // Attach Mini Nav Listeners
    document.getElementById('mini-prev-month')?.addEventListener('click', () => {
        miniCurrentMonth--;
        if (miniCurrentMonth < 0) {
            miniCurrentMonth = 11;
            miniCurrentYear--;
        }
        renderMiniCalendar();
    });

    document.getElementById('mini-next-month')?.addEventListener('click', () => {
        miniCurrentMonth++;
        if (miniCurrentMonth > 11) {
            miniCurrentMonth = 0;
            miniCurrentYear++;
        }
        renderMiniCalendar();
    });

    renderMiniCalendar();

    // Hook into updateCalendar
    const originalUpdateCalendar = updateCalendar;
    updateCalendar = async function (forceFetch = false) {
        await originalUpdateCalendar(forceFetch);
        renderMiniCalendar();
    };

    // Initial load
    updateCalendar(true);

    async function loadFacilitatorSessions() {
        const grid = document.getElementById('my-sessions-grid');
        if (!grid || !currentUser || !currentUser.facilitator_id) return;

        grid.innerHTML = '<div class="loader-container">Fetching your sessions...</div>';

        try {
            const res = await fetch(`api.php?action=get_facilitator_sessions&facilitator_id=${currentUser.facilitator_id}`);
            const data = await res.json();
            if (data.success) {
                if (data.sessions.length === 0) {
                    grid.innerHTML = '<div class="loader-container" style="opacity: 0.5;">No confirmed sessions assigned to you yet.</div>';
                    return;
                }
                grid.innerHTML = '';
                data.sessions.forEach(s => {
                    const card = document.createElement('div');
                    card.className = 'session-card';
                    const dt = new Date(s.date_time.replace(/-/g, '/'));
                    const timeStr = dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                    card.innerHTML = `
                        <div class="session-top">
                            <span class="session-type">${s.type}</span>
                            <span class="session-status" style="background: #ecfdf5; color: #059669;">CONFIRMED</span>
                        </div>
                        <h4>${s.topic}</h4>
                        <div class="session-meta">
                            <span>📅 ${dt.toLocaleDateString()} at ${timeStr}</span>
                            <span>📍 ${s.venue || 'TBA'} (${s.mode})</span>
                        </div>
                        <div class="session-student-info" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee; font-size: 0.8rem;">
                            <p><strong>Requestor:</strong> ${s.requestor_name}</p>
                            <p style="color: #64748b;">${s.requestor_email} | ${s.department_name || 'N/A'}</p>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            }
        } catch (e) {
            grid.innerHTML = '<p>Error loading sessions.</p>';
        }
    }

    async function loadProfile() {
        const profileStatus = document.getElementById('profile-status');
        if (profileStatus) profileStatus.style.display = 'none';

        try {
            const res = await fetch('api.php?action=get_user_info');
            const data = await res.json();

            if (data.success && data.user) {
                const u = data.user;
                document.getElementById('prof-name').value = u.name || '';
                document.getElementById('prof-email').value = u.email || '';

                const deptSelect = document.getElementById('prof-department');
                if (deptSelect) {
                    if (deptSelect.options.length <= 1) {
                        deptSelect.innerHTML = '<option value="">Select department</option>' +
                            allDepartments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
                    }
                    deptSelect.value = u.department_id || '';
                }

                const isStudent = (u.user_type === 'student');
                const studentSection = document.getElementById('prof-student-section');
                if (studentSection) studentSection.style.display = isStudent ? 'block' : 'none';

                if (isStudent) {
                    document.getElementById('prof-student-number').value = u.student_number || '';
                    document.getElementById('prof-year-level').value = u.year_level || '';
                    document.getElementById('prof-enrollment-status').value = u.enrollment_status || 'Regular';

                    const deptName = deptSelect.options[deptSelect.selectedIndex]?.text || '';
                    const isBasicEd = ["Grade School", "Junior High School", "Preschool"].some(d => deptName.toLowerCase().includes(d.toLowerCase()));
                    const programField = document.getElementById('prof-program')?.closest('.field');
                    const yearField = document.getElementById('prof-year-level')?.closest('.field');
                    const statusField = document.getElementById('prof-enrollment-status')?.closest('.field');

                    if (programField) programField.style.display = isBasicEd ? 'none' : 'block';
                    if (yearField) yearField.style.display = isBasicEd ? 'none' : 'block';
                    if (statusField) statusField.style.display = isBasicEd ? 'none' : 'block';

                    await loadProfilePrograms(u.department_id, u.course_program);
                }

                document.getElementById('prof-role-badge').textContent = u.role ? u.role.charAt(0).toUpperCase() + u.role.slice(1) : 'General';
                document.getElementById('prof-type-text').textContent = u.user_type ? u.user_type.charAt(0).toUpperCase() + u.user_type.slice(1) : 'N/A';
            }
        } catch (e) {
            console.error("Failed to load profile:", e);
        }
    }

    async function loadProfilePrograms(deptId, selectedProgramId = null) {
        const programSelect = document.getElementById('prof-program');
        if (!programSelect) return;

        const deptSelect = document.getElementById('prof-department');
        const deptName = deptSelect?.options[deptSelect.selectedIndex]?.text || '';
        const isBasicEd = ["Grade School", "Junior High School", "Preschool"].some(d => deptName.toLowerCase().includes(d.toLowerCase()));
        const programField = programSelect.closest('.field');
        const yearField = document.getElementById('prof-year-level')?.closest('.field');
        const statusField = document.getElementById('prof-enrollment-status')?.closest('.field');

        if (programField) {
            programField.style.display = isBasicEd ? 'none' : 'block';
            if (isBasicEd) programSelect.value = '';
        }
        if (yearField) {
            yearField.style.display = isBasicEd ? 'none' : 'block';
            if (isBasicEd) document.getElementById('prof-year-level').value = '';
        }
        if (statusField) {
            statusField.style.display = isBasicEd ? 'none' : 'block';
            if (isBasicEd) document.getElementById('prof-enrollment-status').value = '';
        }

        if (!deptId) {
            programSelect.innerHTML = '<option value="">Select program</option>';
            return;
        }

        try {
            const res = await fetch(`api.php?action=get_programs&department_id=${deptId}`);
            const data = await res.json();
            if (data.success) {
                programSelect.innerHTML = '<option value="">Select program</option>' +
                    data.programs.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
                if (selectedProgramId) programSelect.value = selectedProgramId;
            }
        } catch (e) {
            console.error("Failed to load programs:", e);
        }
    }

    const profileForm = document.getElementById('profile-update-form');
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const name = document.getElementById('prof-name').value;
            const email = document.getElementById('prof-email').value;
            const currentPassword = document.getElementById('prof-current-password').value;
            const newPassword = document.getElementById('prof-new-password').value;
            const confirmPassword = document.getElementById('prof-confirm-password').value;

            if (newPassword && newPassword !== confirmPassword) {
                showProfileStatus('New passwords do not match.', 'error');
                return;
            }

            if (newPassword && !currentPassword) {
                showProfileStatus('Please enter your current password to change it.', 'error');
                return;
            }

            const payload = {
                name,
                email,
                department_id: document.getElementById('prof-department').value,
                student_number: document.getElementById('prof-student-number').value,
                year_level: document.getElementById('prof-year-level').value,
                course_program: document.getElementById('prof-program').value,
                enrollment_status: document.getElementById('prof-enrollment-status').value,

                current_password: currentPassword,
                new_password: newPassword
            };

            try {
                const res = await fetch('api.php?action=update_profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    showProfileStatus('Profile updated successfully!', 'success');
                    const sidebarName = document.querySelector('.pl-user strong');
                    const sidebarAvatar = document.querySelector('.pl-avatar');
                    if (sidebarName) sidebarName.textContent = name;
                    if (sidebarAvatar) sidebarAvatar.textContent = name.trim().charAt(0).toUpperCase();

                    document.getElementById('prof-current-password').value = '';
                    document.getElementById('prof-new-password').value = '';
                    document.getElementById('prof-confirm-password').value = '';
                } else {
                    showProfileStatus(data.message || 'Failed to update profile.', 'error');
                }
            } catch (err) {
                showProfileStatus('An error occurred. Please try again.', 'error');
            }
        });
    }

    function showProfileStatus(msg, type) {
        const el = document.getElementById('profile-status');
        if (!el) return;
        el.textContent = msg;
        el.style.display = 'block';
        el.style.background = type === 'success' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)';
        el.style.color = type === 'success' ? '#059669' : '#dc2626';
    }

    const profDeptSelect = document.getElementById('prof-department');
    if (profDeptSelect) {
        profDeptSelect.addEventListener('change', () => {
            loadProfilePrograms(profDeptSelect.value);
        });
    }
});
