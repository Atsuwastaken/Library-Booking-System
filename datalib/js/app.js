document.addEventListener('DOMContentLoaded', () => {
    
    const grid = document.getElementById('sessions-grid');
    const refreshBtn = document.getElementById('refresh-btn');
    
    // Modals
    const checkoutModal = document.getElementById('checkout-modal');
    const successModal = document.getElementById('success-modal');
    
    const cancelBtn = document.getElementById('btn-cancel');
    const closeSuccessBtn = document.getElementById('btn-close-success');
    const bookingForm = document.getElementById('confirm-booking-form');

    // User Sidebar Modals
    const avatarBtn = document.getElementById('avatar-btn');
    const userSidebar = document.getElementById('user-sidebar');

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
                const isFullyBooked = daySessions.every(s => s.status === 'BOOKED');
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
                selectedDate = dateStr;
                renderCalendarGrid();
                renderSessionsForSelectedDate();
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
        
        // Update label
        const [y, m, d] = selectedDate.split('-');
        const dateObj = new Date(y, m - 1, d);
        if (selectedDateLabel) {
            selectedDateLabel.textContent = `Sessions for ${dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}`;
        }

        const filtered = allSessions.filter(s => s.date_time.startsWith(selectedDate));
        renderSessions(filtered);
    }

    // Build the grid UI dynamically
    function renderSessions(sessions) {
        grid.innerHTML = '';
        if (sessions.length === 0) {
            grid.innerHTML = '<div class="loader-container">No appointments found for this date.</div>';
            return;
        }

        sessions.forEach(s => {
            const badgeClass = s.mode.toLowerCase() === 'online' ? 'badge-online' : 'badge-onsite';
            
            // Format nice date/time string purely using logic
            const dateObj = new Date(s.date_time.replace(' ', 'T'));
            let timeOpts = { hour: 'numeric', minute: '2-digit' };
            const timeStr = dateObj.toLocaleTimeString('en-US', timeOpts);

            const card = document.createElement('div');
            card.className = 'session-card';
            if (s.status === 'BOOKED') card.style.opacity = '0.6';

            card.innerHTML = `
                <div class="session-header">
                    <span class="badge ${badgeClass}">${s.mode}</span>
                    <span style="font-size: 0.75rem; font-weight: 700; color: ${s.status === 'AVAILABLE' ? 'var(--success)' : 'var(--danger)'}">
                        ${s.status}
                    </span>
                </div>
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
                <button class="btn btn-primary" style="width:100%; justify-content:center;" onclick="initiateBooking(${s.id}, '${s.topic.replace(/'/g, "\\'")}', '${s.facilitator_name.replace(/'/g, "\\'")}', '${timeStr}', '${s.mode}')">
                    Book Session
                </button>` : `
                <button class="btn btn-muted" style="width:100%; justify-content:center;" disabled>Reserved</button>
                `}
            `;
            grid.appendChild(card);
        });
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

    // Facilitators Logic
    const facilitatorsModal = document.getElementById('facilitators-modal');
    const facilitatorsBtn = document.getElementById('view-facilitators-btn');
    const facilitatorsList = document.getElementById('facilitators-list');

    if (facilitatorsBtn) {
        facilitatorsBtn.addEventListener('click', (e) => {
            e.preventDefault();
            facilitatorsModal.classList.add('active');
            loadFacilitators();
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
            card.innerHTML = `
                <div class="fac-avatar">${f.name.charAt(0)}</div>
                <div class="fac-info">
                    <h4>${f.name}</h4>
                    <p>${f.expertise}</p>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <button class="btn btn-primary btn-sm shatter-btn" onclick="handleShatterAndBook(this, ${f.id}, '${f.name}')">
                            Book
                        </button>
                        <button class="btn btn-outline btn-sm" onclick="handleManageHours(${f.id}, '${f.name.replace(/'/g, "\\'")}')">
                            Manage Hours
                        </button>
                    </div>
                </div>
            `;
            facilitatorsList.appendChild(card);
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
            <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid var(--border);">
                        <th style="padding: 0.75rem;">Topic</th>
                        <th style="padding: 0.75rem;">Date & Time</th>
                        <th style="padding: 0.75rem;">Mode</th>
                        <th style="padding: 0.75rem;">Status</th>
                        <th style="padding: 0.75rem;">Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        sessions.forEach(s => {
            html += `
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 0.75rem;">${s.topic}</td>
                    <td style="padding: 0.75rem;">${s.date_time}</td>
                    <td style="padding: 0.75rem;">${s.mode}</td>
                    <td style="padding: 0.75rem;"><span class="badge ${s.status === 'AVAILABLE' ? 'badge-online' : 'badge-onsite'}">${s.status}</span></td>
                    <td style="padding: 0.75rem;">
                        ${s.status === 'AVAILABLE' ? `<button class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger);" onclick="handleDeleteSession(${s.id}, ${fid})">Delete</button>` : '-'}
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
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

    // Initial load
    updateCalendar(true);
});
