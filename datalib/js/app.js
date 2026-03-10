document.addEventListener('DOMContentLoaded', () => {
    
    const grid = document.getElementById('sessions-grid');
    const refreshBtn = document.getElementById('refresh-btn');
    
    // Modals
    const checkoutModal = document.getElementById('checkout-modal');
    const successModal = document.getElementById('success-modal');
    
    const cancelBtn = document.getElementById('btn-cancel');
    const closeSuccessBtn = document.getElementById('btn-close-success');
    const bookingForm = document.getElementById('confirm-booking-form');

    // User Sidebar & Sessions Drawer
    const avatarBtn = document.getElementById('avatar-btn');
    const userSidebar = document.getElementById('user-sidebar');
    const sessionsDrawer = document.getElementById('sessions-drawer');
    const drawerOverlay = document.getElementById('sessions-drawer-overlay');
    const closeDrawerBtn = document.getElementById('close-drawer-btn');
    const openAdvancedBtn = document.getElementById('open-advanced-from-drawer');

    let scrollTimeout = null;
    let clickTimer = null;


    if (avatarBtn && userSidebar) {
        avatarBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userSidebar.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (userSidebar.classList.contains('active') && !userSidebar.contains(e.target) && e.target !== avatarBtn && !avatarBtn.contains(e.target)) {
                userSidebar.classList.remove('active');
            }
        });
    }

    const closeDrawer = () => {
        sessionsDrawer.classList.remove('active');
        drawerOverlay.classList.remove('active');
    };

    if (closeDrawerBtn && drawerOverlay) {
        closeDrawerBtn.addEventListener('click', closeDrawer);
        drawerOverlay.addEventListener('click', closeDrawer);
    }

    if (openAdvancedBtn) {
        openAdvancedBtn.addEventListener('click', () => {
            closeDrawer();
            openAdvancedBooking(selectedDate);
        });
    }


    // Scroll Awareness Logic
    const header = document.querySelector('header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Calendar State
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let selectedDate = new Date().toISOString().split('T')[0];
    let allSessions = [];

    const monthDisplay = document.getElementById('calendar-month-year');
    const calendarGrid = document.getElementById('calendar-grid');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const todayBtn = document.getElementById('today-btn');
    const selectedDateLabel = document.getElementById('selected-date-label');

    // Calendar Navigation Listeners
    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            updateCalendar();
        });
    }

    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            updateCalendar();
        });
    }

    if (todayBtn) {
        todayBtn.addEventListener('click', () => {
            const now = new Date();
            currentMonth = now.getMonth();
            currentYear = now.getFullYear();
            selectedDate = now.toISOString().split('T')[0];
            updateCalendar();
        });
    }

    // Main logic to refresh calendar view
    async function updateCalendar(forceFetch = false) {
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        if (monthDisplay) {
            monthDisplay.textContent = `${monthNames[currentMonth]} ${currentYear}`;
        }
        
        if (allSessions.length === 0 || forceFetch) {
            try {
                const res = await fetch('api.php?action=get_sessions');
                const data = await res.json();
                if (data.success) allSessions = data.sessions;
            } catch(e) {
                console.error("Failed to sync sessions:", e);
            }
        }

        renderCalendarGrid();
        renderSessionsForSelectedDate();
    }

    function renderCalendarGrid() {
        if (!calendarGrid) return;
        calendarGrid.innerHTML = '';
        
        const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const prevMonthLastDay = new Date(currentYear, currentMonth, 0).getDate();
        
        // Previous month padding cells
        for (let i = firstDayOfMonth - 1; i >= 0; i--) {
            const cell = createCell(prevMonthLastDay - i, true);
            calendarGrid.appendChild(cell);
        }

        // Current month cells
        const todayStr = new Date().toISOString().split('T')[0];
        for (let i = 1; i <= daysInMonth; i++) {
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            const cell = createCell(i, false, dateStr === todayStr, dateStr === selectedDate);
            
            // Check for sessions on this day
            const daySessions = allSessions.filter(s => s.date_time.startsWith(dateStr));
            if (daySessions.length > 0) {
                const dotContainer = cell.querySelector('.day-content');
                
                // Only show Red Dot if everything is booked AND we have reached a high volume of sessions
                // Otherwise, as long as there is 1 AVAILABLE session or we haven't hit capacity, show Green
                const hasAvailable = daySessions.some(s => s.status === 'AVAILABLE');
                const bookedCount = daySessions.filter(s => s.status === 'BOOKED').length;
                
                // Assuming 6+ sessions per day for a single instructor is "near full"
                const isFullyBooked = !hasAvailable && bookedCount >= 6;
                
                const dot = document.createElement('div');
                dot.className = `event-dot ${isFullyBooked ? 'dot-booked' : 'dot-available'}`;
                dotContainer.appendChild(dot);
                
                // Optional: show count if multiple
                if (daySessions.length > 1) {
                    const count = document.createElement('span');
                    count.style.fontSize = '0.7rem';
                    count.style.color = 'var(--text-secondary)';
                    count.textContent = `${daySessions.length} slots`;
                    dotContainer.appendChild(count);
                }
            } else {
                // If it's a weekend (Sat/Sun), mark as closed for demo
                const dayOfWeek = new Date(currentYear, currentMonth, i).getDay();
                if (dayOfWeek === 0 || dayOfWeek === 6) {
                    const dot = document.createElement('div');
                    dot.className = 'event-dot dot-closed';
                    cell.querySelector('.day-content').appendChild(dot);
                }
            }
            
            cell.addEventListener('click', () => {
                if (clickTimer) clearTimeout(clickTimer);
                
                clickTimer = setTimeout(() => {
                    selectedDate = dateStr;
                    document.querySelectorAll('.calendar-cell').forEach(c => c.classList.remove('selected'));
                    cell.classList.add('selected');
                    renderSessionsForSelectedDate();
                    sessionsDrawer.classList.add('active');
                    drawerOverlay.classList.add('active');
                }, 200); // Brief delay for dblclick awareness
            });

            cell.addEventListener('dblclick', (e) => {
                if (clickTimer) clearTimeout(clickTimer);
                if (scrollTimeout) clearTimeout(scrollTimeout);
                
                e.stopPropagation();
                selectedDate = dateStr;
                openAdvancedBooking(dateStr);
            });

            calendarGrid.appendChild(cell);
        }

        // Next month padding cells to complete 6-row grid (42 cells)
        const totalUsed = firstDayOfMonth + daysInMonth;
        const paddingNeeded = 42 - totalUsed;
        for (let i = 1; i <= paddingNeeded; i++) {
            const cell = createCell(i, true);
            calendarGrid.appendChild(cell);
        }
    }

    function createCell(day, inactive, isToday, isSelected) {
        const cell = document.createElement('div');
        cell.className = `calendar-cell ${inactive ? 'inactive' : ''} ${isToday ? 'today' : ''} ${isSelected ? 'selected' : ''}`;
        cell.innerHTML = `
            <span class="day-number">${day}</span>
            <div class="day-content"></div>
        `;
        return cell;
    }

    function renderSessionsForSelectedDate() {
        if (!grid) return;
        
        // Update labels
        const [y, m, d] = selectedDate.split('-');
        const dateObj = new Date(y, m - 1, d);
        const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
        
        if (selectedDateLabel) {
            selectedDateLabel.textContent = `Sessions for ${formattedDate}`;
            const sub = document.getElementById('drawer-date-subtitle');
            if (sub) sub.textContent = dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric' });
        }

        const filtered = allSessions.filter(s => s.date_time.startsWith(selectedDate));
        const bookedOnly = filtered.filter(s => s.status === 'BOOKED');
        
        // Render into both drawer and bottom section
        renderSessions(filtered, grid);
        
        const bookedSection = document.getElementById('booked-section');
        const bookedGrid = document.getElementById('booked-sessions-grid');
        const bookedLabel = document.getElementById('booked-date-label-main');

        if (bookedOnly.length > 0) {
            bookedSection.style.display = 'block';
            bookedLabel.textContent = `Booked Appointments for ${formattedDate}`;
            renderSessions(bookedOnly, bookedGrid);
            
            if (scrollTimeout) clearTimeout(scrollTimeout);

        } else {
            if (scrollTimeout) clearTimeout(scrollTimeout);
            bookedSection.style.display = 'none';
        }
    }

    // Build the grid UI dynamically
    function renderSessions(sessions, targetContainer) {
        targetContainer.innerHTML = '';
        if (sessions.length === 0) {
            targetContainer.innerHTML = '<div class="loader-container">No appointments found for this date.</div>';
            return;
        }

        const getTopicIcon = (topic) => {
            const t = topic.toLowerCase();
            if (t.includes('math')) return '📐';
            if (t.includes('science')) return '🧪';
            if (t.includes('computer') || t.includes('code')) return '💻';
            if (t.includes('history')) return '📜';
            if (t.includes('literature') || t.includes('art')) return '🎨';
            return '📚';
        };

        sessions.forEach((s, index) => {
            const badgeClass = s.mode.toLowerCase() === 'online' ? 'badge-online' : 'badge-onsite';
            const dateObj = new Date(s.date_time.replace(' ', 'T'));
            let timeOpts = { hour: 'numeric', minute: '2-digit' };
            const timeStr = dateObj.toLocaleTimeString('en-US', timeOpts);

            const card = document.createElement('div');
            card.className = 'session-card';
            card.style.animationDelay = `${index * 0.1}s`;
            if (s.status === 'BOOKED') card.style.opacity = '0.6';

            card.innerHTML = `
                <div class="session-header">
                    <span class="badge ${badgeClass}">${s.mode}</span>
                    <span style="font-size: 0.75rem; font-weight: 700; color: ${s.status === 'AVAILABLE' ? 'var(--success)' : 'var(--danger)'}">
                        ${s.status}
                    </span>
                </div>
                <div class="session-icon-box">${getTopicIcon(s.topic)}</div>
                <h3 class="session-topic">${s.topic}</h3>
                <div class="session-meta">
                    <div class="meta-row">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        ${timeStr}
                    </div>
                    <div class="meta-row">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        ${s.facilitator_name}
                    </div>
                </div>
                ${s.status === 'AVAILABLE' ? `
                <button class="btn btn-primary" style="width:100%; justify-content:center; border-radius: 10px; font-weight: 700;" onclick="initiateBooking(${s.id}, '${s.topic.replace(/'/g, "\\'")}', '${s.facilitator_name.replace(/'/g, "\\'")}', '${timeStr}', '${s.mode}')">
                    Secure Slot
                </button>` : `
                <button class="btn btn-muted" style="width:100%; justify-content:center; border-radius: 10px;" disabled>Reserved</button>
                `}
            `;
            targetContainer.appendChild(card);
        });

        // Add a helpful hint for custom booking in the drawer
        if (targetContainer.id === 'sessions-grid') {
            const hint = document.createElement('div');
            hint.className = 'drawer-hint';
            hint.style.marginTop = '2rem';
            hint.style.padding = '1rem';
            hint.style.background = 'rgba(47, 129, 247, 0.05)';
            hint.style.borderRadius = '10px';
            hint.style.fontSize = '0.85rem';
            hint.style.color = 'var(--text-secondary)';
            hint.style.textAlign = 'center';
            hint.innerHTML = `
                <p>💡 Don't see a preferred time?</p>
                <p><strong>Double-click</strong> this date on the calendar to create a custom session.</p>
            `;
            targetContainer.appendChild(hint);
        }
    }

    // Interactive Button Click Listener attached globally
    window.initiateBooking = async (id, topic, facilitator, timeStr, mode) => {
        const res = await fetch('api.php?action=lock_session', {
            method: 'POST',
            body: JSON.stringify({ session_id: id })
        });
        const data = await res.json();
        
        if (data.success) {
            document.getElementById('modal-session-id').value = id;
            document.getElementById('modal-topic').textContent = topic;
            document.getElementById('modal-facilitator').textContent = facilitator;
            document.getElementById('modal-datetime').textContent = `${selectedDate} @ ${timeStr}`;
            document.getElementById('modal-mode').textContent = mode;
            
            const badgeEl = document.getElementById('modal-mode');
            badgeEl.className = 'badge ' + (mode.toLowerCase() === 'online' ? 'badge-online' : 'badge-onsite');
            
            checkoutModal.classList.add('active');
            
            // Reload in background
            updateCalendar(true);
        } else {
            alert('This session is no longer available.');
            updateCalendar(true);
        }
    };

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
            if (isFacilitatorAuthenticated) {
                facilitatorsModal.classList.add('active');
                loadFacilitators();
            } else {
                adminLoginModal.classList.add('active');
                document.getElementById('admin-password').focus();
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
        if (!facilitatorsList) return;
        facilitatorsList.innerHTML = '<div class="loader-container">Fetching instructors...</div>';
        
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
        facilitatorsList.innerHTML = '';
        facilitators.forEach(f => {
            const card = document.createElement('div');
            card.className = 'facilitator-card';
            card.style.position = 'relative'; 
            card.innerHTML = `
                <div style="position: absolute; top: 0.75rem; right: 0.75rem; display: flex; gap: 0.25rem;">
                    <button class="btn-icon" title="Edit Instructor" onclick="handleEditFacilitator(${f.id}, '${f.name.replace(/'/g, "\\'")}', '${f.expertise ? f.expertise.replace(/'/g, "\\'") : ''}')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button class="btn-icon" title="Remove Instructor" style="color: var(--danger);" onclick="handleDeleteFacilitator(${f.id}, '${f.name.replace(/'/g, "\\'")}')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                </div>
                <div class="fac-avatar">${f.name.charAt(0)}</div>
                <div class="fac-info">
                    <h4>${f.name}</h4>
                    <p>${f.expertise}</p>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <button class="btn btn-primary btn-sm shatter-btn" onclick="handleShatterAndBook(this, ${f.id}, '${f.name}')">
                            Book
                        </button>
                        <button class="btn btn-outline btn-sm" onclick="handleManageHours(${f.id}, '${f.name.replace(/'/g, "\\'")}')">
                            Schedule
                        </button>
                    </div>
                </div>
            `;
            facilitatorsList.appendChild(card);
        });
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
        document.getElementById('fac-expertise').value = expertise;
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
                updateCalendar(true);
            }
        } catch(e) { console.error(e); }
    };

    if (facCrudForm) {
        facCrudForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('edit-fac-id').value;
            const name = document.getElementById('fac-name').value;
            const expertise = document.getElementById('fac-expertise').value;
            
            const action = id ? 'update_facilitator' : 'add_facilitator';
            const payload = id ? { id, name, expertise } : { name, expertise };

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
            } catch(e) { console.error(e); }
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
            viewInstructorSlots(id, name);
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

    // Release lock protocol
    cancelBtn.addEventListener('click', async () => {
        const id = document.getElementById('modal-session-id').value;
        checkoutModal.classList.remove('active');
        bookingForm.reset();
        
        await fetch('api.php?action=unlock_session', {
            method: 'POST',
            body: JSON.stringify({ session_id: id })
        });
        
        updateCalendar(true);
    });

    // Submitting and processing final reservation module
    bookingForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = document.getElementById('btn-confirm');
        const origText = btn.textContent;
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="spin"><circle cx="12" cy="12" r="10" stroke="rgba(255,255,255,0.3)" stroke-width="4"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke="#fff" stroke-width="4"></path></svg> Confirming...';
        btn.disabled = true;

        const id = document.getElementById('modal-session-id').value;
        const selectedTopic = document.getElementById('modal-topic-select').value;
        const requests = document.getElementById('special-requests').value;

        // Combine logic: if they picked a category, prepend it to the special requests or handle separately
        const finalRequests = selectedTopic ? `[Topic: ${selectedTopic}] ${requests}` : requests;

        const res = await fetch('api.php?action=confirm_booking', {
            method: 'POST',
            body: JSON.stringify({ session_id: id, special_requests: finalRequests })
        });
        const data = await res.json();
        
        btn.textContent = origText;
        btn.disabled = false;

        if (data.success) {
            checkoutModal.classList.remove('active');
            bookingForm.reset();
            successModal.classList.add('active');
        } else {
            alert('There was an error confirming your booking.');
            checkoutModal.classList.remove('active');
            updateCalendar(true);
        }
    });

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

    // Advanced Booking Logic
    const advBookingModal = document.getElementById('advanced-booking-modal');
    const slotsGrid = document.getElementById('time-slots-container');
    const modalFacList = document.getElementById('modal-instructor-list');
    const advBookingForm = document.getElementById('advanced-booking-form');
    let selectedSlot = null;
    let selectedFacId = null;

    function openAdvancedBooking(date) {
        const dateObj = new Date(date);
        document.getElementById('booking-date-display').textContent = dateObj.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
        
        selectedSlot = null;
        selectedFacId = null;
        slotsGrid.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.9rem; font-style: italic;">Select an instructor first to see availability.</p>';
        
        loadModalInstructors();
        advBookingModal.classList.add('active');

        // Sequential pop-in transitions
        const leftPanel = document.querySelector('.booking-left');
        const rightPanel = document.querySelector('.booking-right');
        
        setTimeout(() => leftPanel.classList.add('pop-in'), 100);
        setTimeout(() => rightPanel.classList.add('pop-in'), 300);
    }

    window.closeAdvancedBooking = () => {
        const leftPanel = document.querySelector('.booking-left');
        const rightPanel = document.querySelector('.booking-right');
        
        advBookingModal.classList.remove('active');
        leftPanel.classList.remove('pop-in');
        rightPanel.classList.remove('pop-in');
    };

    async function loadModalInstructors() {
        modalFacList.innerHTML = '<div class="loader-container">Syncing faculty...</div>';
        try {
            const res = await fetch('api.php?action=get_facilitators');
            const data = await res.json();
            if (data.success) {
                renderModalInstructors(data.facilitators);
            }
        } catch (e) {
            modalFacList.innerHTML = '<p>Error loading instructors.</p>';
        }
    }

    function renderModalInstructors(facilitators) {
        modalFacList.innerHTML = '';
        facilitators.forEach(f => {
            const div = document.createElement('div');
            div.className = 'instructor-select-card';
            div.innerHTML = `
                <div class="compact-av">${f.name.charAt(0)}</div>
                <div class="ins-meta">
                    <h5>${f.name}</h5>
                    <p>${f.expertise}</p>
                </div>
            `;
            
            div.addEventListener('click', () => {
                document.querySelectorAll('.instructor-select-card').forEach(c => c.classList.remove('selected'));
                div.classList.add('selected');
                selectedFacId = f.id;
                generateAdvancedSlots(selectedDate);
            });
            
            modalFacList.appendChild(div);
        });
    }

    function generateAdvancedSlots(dateStr) {
        slotsGrid.innerHTML = '';
        const day = new Date(dateStr).getDay();
        
        // Configuration: 7:00 AM start, 1.5h gap, skip 11:30-13:00 (Lunch)
        const daySlots = [
            { start: '7:00 AM', end: '8:30 AM' },
            { start: '8:30 AM', end: '10:00 AM' },
            { start: '10:00 AM', end: '11:30 AM' },
            { start: '1:00 PM', end: '2:30 PM' },
            { start: '2:30 PM', end: '4:00 PM' },
            { start: '4:00 PM', end: '5:30 PM' },
            { start: '5:30 PM', end: '7:00 PM' }
        ];

        // Saturday adjustment
        if (day === 6) {
            daySlots.length = 6; // Closes earlier
        }

        daySlots.forEach(s => {
            const slotStart24 = formatTimeTo24h(s.start);
            const fullDateTime = `${dateStr} ${slotStart24}`;
            
            // Check if this specific instructor is already booked for this slot
            const isTaken = allSessions.some(existing => 
                existing.date_time === fullDateTime && 
                existing.facilitator_id == selectedFacId && 
                existing.status === 'BOOKED'
            );

            const btn = document.createElement('div');
            btn.className = `time-slot-btn ${isTaken ? 'disabled' : ''}`;
            btn.innerHTML = `
                <span class="slot-time">${s.start} - ${s.end}</span>
                <span class="slot-status">${isTaken ? 'Reserved' : 'Available'}</span>
            `;
            
            if (!isTaken) {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('selected'));
                    btn.classList.add('selected');
                    selectedSlot = `${s.start} - ${s.end}`;
                });
            } else {
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';
            }
            
            slotsGrid.appendChild(btn);
        });
    }

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
            
            if (!selectedSlot || !selectedFacId) {
                alert('Please select both an instructor and a time slot.');
                return;
            }

            const btn = document.getElementById('btn-confirm-advanced');
            const orig = btn.textContent;
            btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="spin"><circle cx="12" cy="12" r="10" stroke="rgba(255,255,255,0.3)" stroke-width="3"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke="#fff" stroke-width="3"></path></svg> Linking...';
            btn.disabled = true;

            const mode = document.querySelector('input[name="book-mode"]:checked').value;

            const slotTimeRaw = selectedSlot.split(' - ')[0];


            const payload = {
                name: document.getElementById('book-name').value,
                email: document.getElementById('book-email').value,
                phone: document.getElementById('book-phone').value,
                reminder: document.getElementById('book-reminder').value,
                mode: mode,
                topic: 'Library Consultation',
                slot: selectedSlot,
                facilitator_id: selectedFacId,
                date: selectedDate,
                date_time: `${selectedDate} ${formatTimeTo24h(slotTimeRaw)}`
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
                    selectedSlot = null;
                    selectedFacId = null;
                    
                    // Show success
                    document.getElementById('success-modal').classList.add('active');
                    
                    // DEEP SYNC: Ensure everything is updated
                    await updateCalendar(true);
                    
                    // If manage panel is open, refresh it manually
                    const currentManageId = document.getElementById('manage-facilitator-id').value;
                    if (currentManageId) {
                         loadFacilitatorSessions(currentManageId);
                    }
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

    // Initial load
    updateCalendar(true);
});
