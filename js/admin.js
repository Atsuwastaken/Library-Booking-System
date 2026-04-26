document.addEventListener('DOMContentLoaded', () => {
    loadRequests();
    loadSeminars();
    loadFacilitators();
    loadTopicCatalog();
    loadUsersAdminData();
    loadAdminCalendarContext();
    initAdminCalendar();
    initAdminBookingModal();
    initTabs();
    initExportLogs();
    initUsersDirectoryUI();

    const topicSearch = document.getElementById('topics-search');
    const facilitatorSearch = document.getElementById('facilitators-search');
    if (topicSearch) {
        topicSearch.addEventListener('input', () => loadTopicCatalog(topicSearch.value));
    }
    if (facilitatorSearch) {
        facilitatorSearch.addEventListener('input', () => loadFacilitators(facilitatorSearch.value));
    }

    const refreshUsersBtn = document.getElementById('refresh-users-admin');
    if (refreshUsersBtn) {
        refreshUsersBtn.addEventListener('click', () => loadUsersAdminData());
    }

    const usersSearch = document.getElementById('users-admin-search');
    if (usersSearch) {
        usersSearch.addEventListener('input', () => {
            adminUsersSearchTerm = usersSearch.value.trim().toLowerCase();
            renderRegistrationRequestsTable();
            renderUsersAdminTable();
        });
    }
});

function initExportLogs() {
    const exportBtn = document.getElementById('export-logs-btn');
    if (!exportBtn) return;

    exportBtn.addEventListener('click', async () => {
        const originalText = exportBtn.textContent;
        exportBtn.disabled = true;
        exportBtn.textContent = 'Exporting...';

        try {
            const response = await fetch('api.php?action=export_session_logs_csv');
            if (!response.ok) {
                let message = 'Failed to export logs.';
                try {
                    const payload = await response.json();
                    message = payload.message || message;
                } catch (parseErr) {
                    // Keep fallback message when response is not JSON.
                }
                throw new Error(message);
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `session_logs_last_3_years_${new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-')}.csv`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
            notify('Session logs exported successfully');
        } catch (err) {
            console.error(err);
            notify(err.message || 'Failed to export logs', 'error');
        } finally {
            exportBtn.disabled = false;
            exportBtn.textContent = originalText;
        }
    });
}

function notify(message, type = 'success') {
    const container = document.getElementById('admin-toasts');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        ${message}
    `;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function initTabs() {
    const links = document.querySelectorAll('.nav-link[data-tab]');
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const tab = link.getAttribute('data-tab');
            links.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            document.querySelectorAll('.admin-tab-content').forEach(c => c.style.display = 'none');
            const target = document.getElementById(`tab-${tab}`);
            if (target) target.style.display = 'block';

            if (tab === 'users') {
                loadUsersAdminData();
            }
        });
    });
}

let requestAppointmentsCache = [];
let requestFilterHandlersBound = false;
let adminUsersCache = [];
let adminRegistrationRequestsCache = [];
let adminDepartmentOptionsCache = [];
let adminUsersSearchTerm = '';

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function buildDepartmentOptions(selectedValue) {
    const selected = String(selectedValue || '');
    const options = ['<option value="">None</option>'];
    adminDepartmentOptionsCache.forEach(d => {
        const id = String(d.id);
        options.push(`<option value="${id}" ${id === selected ? 'selected' : ''}>${escapeHtml(d.name)}</option>`);
    });
    return options.join('');
}

function normalizeText(value) {
    return String(value ?? '').trim().toLowerCase();
}

function normalizeRole(roleValue) {
    const value = normalizeText(roleValue);
    if (value === 'admin' || value === 'staff' || value === 'student') {
        return value;
    }
    return 'student';
}

function isTruthy(value) {
    if (typeof value === 'boolean') return value;
    if (typeof value === 'number') return value !== 0;
    const normalized = normalizeText(value);
    return normalized === '1' || normalized === 'true' || normalized === 'yes';
}

function matchesUsersSearch(valueList) {
    if (!adminUsersSearchTerm) return true;
    return valueList
        .map(value => String(value ?? '').toLowerCase())
        .join(' ')
        .includes(adminUsersSearchTerm);
}

function getRoleBadge(roleValue) {
    const role = String(roleValue || 'student').toLowerCase();
    const palette = {
        admin: '#b91c1c',
        staff: '#1d4ed8',
        student: '#047857'
    };
    const color = palette[role] || '#334155';
    return `<span class="users-admin-chip" style="border-color:${color}33; color:${color}; background:${color}14;">${escapeHtml(role)}</span>`;
}

function initUsersDirectoryUI() {
    const tabButtons = document.querySelectorAll('.users-directory-tab[data-users-pane]');
    if (tabButtons.length) {
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetPane = button.getAttribute('data-users-pane');
                if (!targetPane) return;

                tabButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                document.querySelectorAll('.users-directory-pane').forEach(pane => pane.classList.remove('active'));
                const pane = document.getElementById(`users-pane-${targetPane}`);
                if (pane) pane.classList.add('active');
            });
        });
    }

    const modalOpeners = [
        ['open-add-staff-modal', 'add-staff-modal'],
        ['open-add-facilitator-user-modal', 'add-facilitator-user-modal'],
        ['open-add-admin-modal', 'add-admin-modal']
    ];

    modalOpeners.forEach(([openBtnId, modalId]) => {
        const openBtn = document.getElementById(openBtnId);
        const modal = document.getElementById(modalId);
        if (openBtn && modal) {
            openBtn.addEventListener('click', () => modal.classList.add('active'));
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.classList.remove('active');
                }
            });
        }
    });

    document.querySelectorAll('[data-close-modal]').forEach(button => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-close-modal');
            if (!modalId) return;
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('active');
        });
    });

    const addStaffForm = document.getElementById('add-staff-form');
    if (addStaffForm) {
        addStaffForm.addEventListener('submit', (event) => {
            event.preventDefault();
            handleCreateAdminUser(event.currentTarget, {
                role: 'staff',
                facilitatorEnabled: false,
                modalId: 'add-staff-modal',
                successMessage: 'Staff account added successfully.'
            });
        });
    }

    const addFacilitatorForm = document.getElementById('add-facilitator-user-form');
    if (addFacilitatorForm) {
        addFacilitatorForm.addEventListener('submit', (event) => {
            event.preventDefault();
            handleCreateAdminUser(event.currentTarget, {
                role: 'staff',
                facilitatorEnabled: true,
                modalId: 'add-facilitator-user-modal',
                successMessage: 'Facilitator account added successfully.'
            });
        });
    }

    const addAdminForm = document.getElementById('add-admin-form');
    if (addAdminForm) {
        addAdminForm.addEventListener('submit', (event) => {
            event.preventDefault();
            handleCreateAdminUser(event.currentTarget, {
                role: 'admin',
                facilitatorEnabled: false,
                modalId: 'add-admin-modal',
                successMessage: 'Admin account added successfully.'
            });
        });
    }
}

async function handleCreateAdminUser(formEl, config) {
    if (!formEl) return;

    const submitBtn = formEl.querySelector('button[type="submit"]');
    const name = formEl.querySelector('[name="name"]')?.value?.trim() || '';
    const email = formEl.querySelector('[name="email"]')?.value?.trim() || '';
    const password = formEl.querySelector('[name="password"]')?.value || '';

    if (!name || !email || !password) {
        notify('Name, email, and password are required.', 'error');
        return;
    }

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';
    }

    try {
        const res = await fetch('api.php?action=add_user_admin', {
            method: 'POST',
            body: JSON.stringify({
                name,
                email,
                password,
                role: config.role,
                facilitator_enabled: config.facilitatorEnabled
            })
        });
        const data = await res.json();
        if (!data.success) {
            notify(data.message || 'Failed to add user account.', 'error');
            return;
        }

        const modal = document.getElementById(config.modalId);
        if (modal) modal.classList.remove('active');
        formEl.reset();
        notify(config.successMessage || 'Account added successfully.');
        await loadUsersAdminData();
    } catch (error) {
        console.error(error);
        notify('Failed to add user account.', 'error');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            if (config.role === 'admin') {
                submitBtn.textContent = 'Create Admin Account';
            } else if (config.facilitatorEnabled) {
                submitBtn.textContent = 'Create Facilitator Account';
            } else {
                submitBtn.textContent = 'Create Staff Account';
            }
        }
    }
}

async function loadUsersAdminData() {
    const studentTbody = document.getElementById('users-admin-students-tbody');
    const staffTbody = document.getElementById('users-admin-staff-tbody');
    const facilitatorTbody = document.getElementById('users-admin-facilitators-tbody');
    const adminTbody = document.getElementById('users-admin-admins-tbody');
    const completedTbody = document.getElementById('users-completed-appointments-tbody');
    const requestsTbody = document.getElementById('registration-requests-tbody');
    if (!studentTbody || !staffTbody || !facilitatorTbody || !adminTbody || !completedTbody || !requestsTbody) return;

    studentTbody.innerHTML = '<tr><td colspan="7" style="padding: 1rem; color: #94a3b8;">Loading students...</td></tr>';
    staffTbody.innerHTML = '<tr><td colspan="5" style="padding: 1rem; color: #94a3b8;">Loading staff...</td></tr>';
    facilitatorTbody.innerHTML = '<tr><td colspan="5" style="padding: 1rem; color: #94a3b8;">Loading facilitators...</td></tr>';
    adminTbody.innerHTML = '<tr><td colspan="5" style="padding: 1rem; color: #94a3b8;">Loading admins...</td></tr>';
    completedTbody.innerHTML = '<tr><td colspan="6" style="padding: 1rem; color: #94a3b8;">Loading completed appointments...</td></tr>';
    requestsTbody.innerHTML = '<tr><td colspan="6" style="padding: 1rem; color: #94a3b8;">Loading registration requests...</td></tr>';

    try {
        const [usersRes, requestsRes, departmentsRes, appointmentsRes] = await Promise.all([
            fetch('api.php?action=get_users_admin'),
            fetch('api.php?action=get_registration_requests&status=PENDING'),
            fetch('api.php?action=get_departments'),
            fetch('api.php?action=get_appointments')
        ]);

        const usersData = await usersRes.json();
        const requestsData = await requestsRes.json();
        const departmentsData = await departmentsRes.json();
        const appointmentsData = await appointmentsRes.json();

        adminUsersCache = usersData.success && Array.isArray(usersData.users) ? usersData.users : [];
        adminRegistrationRequestsCache = requestsData.success && Array.isArray(requestsData.requests) ? requestsData.requests : [];
        adminDepartmentOptionsCache = departmentsData.success && Array.isArray(departmentsData.departments) ? departmentsData.departments : [];
        requestAppointmentsCache = appointmentsData.success && Array.isArray(appointmentsData.appointments)
            ? appointmentsData.appointments
            : [];

        const pendingCountEl = document.getElementById('pending-requests-count');
        if (pendingCountEl) pendingCountEl.textContent = `${adminRegistrationRequestsCache.length} pending`;

        const usersCountEl = document.getElementById('users-total-count');
        if (usersCountEl) usersCountEl.textContent = `${adminUsersCache.length} users`;

        renderRegistrationRequestsTable();
        renderUsersAdminTable();
    } catch (error) {
        console.error('Failed to load admin users tab data:', error);
        studentTbody.innerHTML = '<tr><td colspan="7" style="padding: 1rem; color: #ef4444;">Failed to load users.</td></tr>';
        staffTbody.innerHTML = '<tr><td colspan="5" style="padding: 1rem; color: #ef4444;">Failed to load users.</td></tr>';
        facilitatorTbody.innerHTML = '<tr><td colspan="5" style="padding: 1rem; color: #ef4444;">Failed to load users.</td></tr>';
        adminTbody.innerHTML = '<tr><td colspan="5" style="padding: 1rem; color: #ef4444;">Failed to load users.</td></tr>';
        completedTbody.innerHTML = '<tr><td colspan="6" style="padding: 1rem; color: #ef4444;">Failed to load completed appointments.</td></tr>';
        requestsTbody.innerHTML = '<tr><td colspan="6" style="padding: 1rem; color: #ef4444;">Failed to load registration requests.</td></tr>';
    }
}

function renderRegistrationRequestsTable() {
    const tbody = document.getElementById('registration-requests-tbody');
    if (!tbody) return;

    tbody.innerHTML = '';
    const filteredRequests = adminRegistrationRequestsCache.filter(req => matchesUsersSearch([
        req.name,
        req.email,
        req.student_number,
        req.department_name,
        req.status,
        req.review_note
    ]));

    if (!filteredRequests.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="padding: 1rem; color: #94a3b8;">No pending registration requests.</td></tr>';
        return;
    }

    filteredRequests.forEach(req => {
        const requestedRole = normalizeRole(req.requested_role || 'student');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div style="display:flex; flex-direction:column; gap:0.35rem; min-width: 150px;">
                    <input type="text" class="form-control" data-field="name" value="${escapeHtml(req.name || '')}" readonly>
                </div>
            </td>
            <td><input type="text" class="form-control" data-field="email" value="${escapeHtml(req.email || '')}" readonly></td>
            <td><input type="text" class="form-control" data-field="student_number" value="${escapeHtml(req.student_number || '')}" readonly></td>
            <td>
                <div class="users-combobox-stack">
                    <span class="users-combobox-label">Department</span>
                    <input type="text" class="users-input" data-field="department_name" value="${escapeHtml(req.department_name || 'Not set')}" readonly>
                    <input type="hidden" data-field="department_id" value="${escapeHtml(req.department_id || '')}">
                    <span class="users-combobox-note">College is fixed from the registration request.</span>
                </div>
            </td>
            <td>
                <div style="display:flex; flex-direction:column; gap:0.45rem;">
                    <select class="form-control users-combobox" data-field="role">
                        <option value="student" ${requestedRole === 'student' ? 'selected' : ''}>student</option>
                        <option value="staff" ${requestedRole === 'staff' ? 'selected' : ''}>staff</option>
                        <option value="admin" ${requestedRole === 'admin' ? 'selected' : ''}>admin</option>
                    </select>
                    <label class="users-facilitator-toggle">
                        <input type="checkbox" data-field="facilitator_enabled" ${req.requested_facilitator_id ? 'checked' : ''}>
                        <span>Facilitator</span>
                    </label>
                </div>
            </td>
            <td>
                <div class="action-btns">
                    <button class="btn btn-primary btn-sm" type="button">Approve</button>
                    <button class="btn btn-outline btn-sm" type="button">Reject</button>
                </div>
            </td>
        `;

        const approveBtn = tr.querySelector('.btn.btn-primary');
        const rejectBtn = tr.querySelector('.btn.btn-outline');

        approveBtn.addEventListener('click', () => approveRegistrationRequest(req.id, tr));
        rejectBtn.addEventListener('click', () => rejectRegistrationRequest(req.id));

        tbody.appendChild(tr);
    });
}

function renderUsersAdminTable() {
    const studentTbody = document.getElementById('users-admin-students-tbody');
    const staffTbody = document.getElementById('users-admin-staff-tbody');
    const facilitatorTbody = document.getElementById('users-admin-facilitators-tbody');
    const adminTbody = document.getElementById('users-admin-admins-tbody');
    if (!studentTbody || !staffTbody || !facilitatorTbody || !adminTbody) return;

    studentTbody.innerHTML = '';
    staffTbody.innerHTML = '';
    facilitatorTbody.innerHTML = '';
    adminTbody.innerHTML = '';

    const filteredUsers = adminUsersCache.filter(user => matchesUsersSearch([
        user.name,
        user.email,
        user.student_number,
        user.department_name,
        user.role,
        user.facilitator_name,
        user.is_facilitator ? 'facilitator' : 'not facilitator'
    ]));

    const students = filteredUsers.filter(user => normalizeRole(user.role) === 'student' && !isTruthy(user.is_facilitator));
    const staff = filteredUsers.filter(user => normalizeRole(user.role) === 'staff' && !isTruthy(user.is_facilitator));
    const facilitators = filteredUsers.filter(user => isTruthy(user.is_facilitator));
    const admins = filteredUsers.filter(user => normalizeRole(user.role) === 'admin' && !isTruthy(user.is_facilitator));

    renderStudentRows(students, studentTbody, 'No student users found.');
    renderCompactRoleRows(staff, staffTbody, 'No staff users found.');
    renderCompactRoleRows(facilitators, facilitatorTbody, 'No facilitator users found.');
    renderCompactRoleRows(admins, adminTbody, 'No admin users found.');
}

function renderStudentRows(users, tbody, emptyMessage) {
    if (!users.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="padding: 1rem; color: #94a3b8;">${emptyMessage}</td></tr>`;
        return;
    }

    users.forEach(user => {
        const role = normalizeRole(user.role || 'student');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div style="display:flex; flex-direction:column; gap:0.35rem; min-width: 150px;">
                    <input type="text" class="form-control" data-field="name" value="${escapeHtml(user.name || '')}">
                </div>
            </td>
            <td><input type="email" class="form-control" data-field="email" value="${escapeHtml(user.email || '')}"></td>
            <td><input type="text" class="form-control" data-field="student_number" value="${escapeHtml(user.student_number || '')}"></td>
            <td>
                <select class="form-control users-combobox" data-field="department_id">
                    ${buildDepartmentOptions(user.department_id)}
                </select>
            </td>
            <td>
                <select class="form-control users-combobox" data-field="role">
                    <option value="student" ${role === 'student' ? 'selected' : ''}>student</option>
                    <option value="staff" ${role === 'staff' ? 'selected' : ''}>staff</option>
                    <option value="admin" ${role === 'admin' ? 'selected' : ''}>admin</option>
                </select>
            </td>
            <td>
                <label class="users-facilitator-toggle">
                    <input type="checkbox" data-field="facilitator_enabled" ${user.is_facilitator ? 'checked' : ''}>
                    <span>Facilitator</span>
                </label>
                <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.3rem;">${escapeHtml(user.facilitator_name || 'No facilitator profile')}</div>
            </td>
            <td>
                <div class="action-btns">
                    <button class="btn btn-primary btn-sm" type="button">Save</button>
                    <button class="btn btn-outline btn-sm users-delete-btn" type="button">Delete</button>
                </div>
            </td>
        `;

        const saveBtn = tr.querySelector('button.btn-primary');
        const deleteBtn = tr.querySelector('.users-delete-btn');
        saveBtn.addEventListener('click', () => saveAdminUserRow(user.id, tr));
        deleteBtn.addEventListener('click', () => deleteAdminUserRow(user.id, user.name || 'this user'));

        tbody.appendChild(tr);
    });
}

function renderCompactRoleRows(users, tbody, emptyMessage) {
    if (!users.length) {
        tbody.innerHTML = `<tr><td colspan="5" style="padding: 1rem; color: #94a3b8;">${emptyMessage}</td></tr>`;
        return;
    }

    users.forEach(user => {
        const role = normalizeRole(user.role || 'student');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div style="display:flex; flex-direction:column; gap:0.35rem; min-width: 150px;">
                    <input type="text" class="form-control" data-field="name" value="${escapeHtml(user.name || '')}">
                </div>
            </td>
            <td><input type="email" class="form-control" data-field="email" value="${escapeHtml(user.email || '')}"></td>
            <td>
                <select class="form-control users-combobox" data-field="role">
                    <option value="student" ${role === 'student' ? 'selected' : ''}>student</option>
                    <option value="staff" ${role === 'staff' ? 'selected' : ''}>staff</option>
                    <option value="admin" ${role === 'admin' ? 'selected' : ''}>admin</option>
                </select>
            </td>
            <td>
                <label class="users-facilitator-toggle">
                    <input type="checkbox" data-field="facilitator_enabled" ${user.is_facilitator ? 'checked' : ''}>
                    <span>Facilitator</span>
                </label>
                <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.3rem;">${escapeHtml(user.facilitator_name || 'No facilitator profile')}</div>
            </td>
            <td>
                <div class="action-btns">
                    <button class="btn btn-primary btn-sm" type="button">Save</button>
                    <button class="btn btn-outline btn-sm users-delete-btn" type="button">Delete</button>
                </div>
            </td>
        `;

        const saveBtn = tr.querySelector('button.btn-primary');
        const deleteBtn = tr.querySelector('.users-delete-btn');
        saveBtn.addEventListener('click', () => saveAdminUserRow(user.id, tr));
        deleteBtn.addEventListener('click', () => deleteAdminUserRow(user.id, user.name || 'this user'));

        tbody.appendChild(tr);
    });
}

async function approveRegistrationRequest(requestId, rowEl) {
    const role = normalizeRole(rowEl.querySelector('[data-field="role"]')?.value || 'student');
    const facilitatorEnabled = rowEl.querySelector('[data-field="facilitator_enabled"]')?.checked || false;
    const lockedDepartmentId = rowEl.querySelector('[data-field="department_id"]')?.value || '';
    let departmentId = parseInt(lockedDepartmentId, 10) || null;
    if (role !== 'student' || facilitatorEnabled) {
        departmentId = null;
    }

    try {
        const res = await fetch('api.php?action=approve_registration_request', {
            method: 'POST',
            body: JSON.stringify({
                request_id: requestId,
                role,
                department_id: departmentId || null,
                facilitator_enabled: facilitatorEnabled
            })
        });
        const data = await res.json();
        if (data.success) {
            notify('Registration approved');
            adminUsersSearchTerm = document.getElementById('users-admin-search')?.value?.trim().toLowerCase() || '';
            await loadUsersAdminData();
        } else {
            notify(data.message || 'Failed to approve registration request', 'error');
        }
    } catch (error) {
        console.error(error);
        notify('Failed to approve registration request', 'error');
    }
}

async function rejectRegistrationRequest(requestId) {
    const reason = prompt('Optional reason for rejection:', '') || '';

    try {
        const res = await fetch('api.php?action=reject_registration_request', {
            method: 'POST',
            body: JSON.stringify({ request_id: requestId, reason })
        });
        const data = await res.json();
        if (data.success) {
            notify('Registration request rejected');
            adminUsersSearchTerm = document.getElementById('users-admin-search')?.value?.trim().toLowerCase() || '';
            await loadUsersAdminData();
        } else {
            notify(data.message || 'Failed to reject registration request', 'error');
        }
    } catch (error) {
        console.error(error);
        notify('Failed to reject registration request', 'error');
    }
}

async function saveAdminUserRow(userId, rowEl) {
    const role = normalizeRole(rowEl.querySelector('[data-field="role"]')?.value || 'student');
    const facilitatorEnabled = rowEl.querySelector('[data-field="facilitator_enabled"]')?.checked || false;
    const departmentField = rowEl.querySelector('[data-field="department_id"]');
    const departmentId = (role === 'student' && !facilitatorEnabled && departmentField)
        ? (parseInt(departmentField.value, 10) || null)
        : null;
    const studentField = rowEl.querySelector('[data-field="student_number"]');
    const studentNumber = role === 'student' && studentField
        ? (studentField.value || '')
        : '';

    const payload = {
        id: userId,
        name: rowEl.querySelector('[data-field="name"]')?.value || '',
        email: rowEl.querySelector('[data-field="email"]')?.value || '',
        student_number: studentNumber,
        department_id: departmentId,
        role,
        facilitator_enabled: facilitatorEnabled
    };

    try {
        const res = await fetch('api.php?action=update_user_admin', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            notify('User updated successfully');
            adminUsersSearchTerm = document.getElementById('users-admin-search')?.value?.trim().toLowerCase() || '';
            await loadUsersAdminData();
        } else {
            notify(data.message || 'Failed to update user', 'error');
        }
    } catch (error) {
        console.error(error);
        notify('Failed to update user', 'error');
    }
}

async function deleteAdminUserRow(userId, userName) {
    const confirmed = confirm(`Delete ${userName}? This will remove the account and unlink related facilitator data.`);
    if (!confirmed) return;

    try {
        const res = await fetch('api.php?action=delete_user_admin', {
            method: 'POST',
            body: JSON.stringify({ id: userId })
        });
        const data = await res.json();
        if (data.success) {
            notify('User deleted successfully');
            adminUsersSearchTerm = document.getElementById('users-admin-search')?.value?.trim().toLowerCase() || '';
            await loadUsersAdminData();
        } else {
            notify(data.message || 'Failed to delete user', 'error');
        }
    } catch (error) {
        console.error(error);
        notify('Failed to delete user', 'error');
    }
}

function parseSpecialRequests(str) {
    const result = { name: 'N/A', email: 'N/A', reminder: 'N/A', notes: 'No notes provided', dept: 'N/A' };
    if (!str) return result;
    const parts = str.split(' | ');
    parts.forEach(p => {
        const [key, ...valParts] = p.split(': ');
        const val = valParts.join(': ').trim();
        if (key === 'Name') result.name = val;
        else if (key === 'Email') result.email = val;
        else if (key === 'Reminder') result.reminder = val;
        else if (key === 'Notes') result.notes = val;
        else if (key === 'Dept') result.dept = val;
    });
    return result;
}

function getAppointmentRequestData(app) {
    const legacy = parseSpecialRequests(app.special_requests);

    const reminderMinutes = Number(app.notification_minutes);
    const derivedReminder = Number.isFinite(reminderMinutes) ? `${Math.max(0, reminderMinutes)} minutes` : legacy.reminder;

    return {
        name: app.requester_name || legacy.name || app.student_name || 'N/A',
        email: app.requester_email || app.student_email || legacy.email || 'N/A',
        dept: app.requester_department || app.student_department || legacy.dept || 'N/A',
        notes: app.special_requests || legacy.notes || 'No notes provided',
        reminder: derivedReminder
    };
}

function parseReminderMinutes(reminderText) {
    if (!reminderText || reminderText === 'N/A') return 0;
    const match = String(reminderText).toLowerCase().match(/(\d+)\s*(minute|minutes|min|hour|hours|hr|hrs|day|days)?/);
    if (!match) return 0;

    const value = Number(match[1]);
    const unit = match[2] || 'minutes';

    if (unit.startsWith('day')) return value * 1440;
    if (unit.startsWith('hour') || unit === 'hr' || unit === 'hrs') return value * 60;
    return value;
}

function formatLeadTime(totalMinutes) {
    if (totalMinutes <= 0) return 'at start time';
    if (totalMinutes < 60) return `${totalMinutes}m before`;

    const days = Math.floor(totalMinutes / 1440);
    const hours = Math.floor((totalMinutes % 1440) / 60);
    const minutes = totalMinutes % 60;

    if (days > 0) {
        if (hours > 0) return `${days}d ${hours}h before`;
        return `${days}d before`;
    }

    if (minutes > 0) return `${hours}h ${minutes}m before`;
    return `${hours}h before`;
}

function formatTimeUntil(ts) {
    const diffMs = ts - Date.now();
    if (diffMs <= 0) return 'sending soon';

    const totalMinutes = Math.ceil(diffMs / 60000);
    if (totalMinutes < 60) return `in ${totalMinutes}m`;

    const days = Math.floor(totalMinutes / 1440);
    const hours = Math.floor((totalMinutes % 1440) / 60);
    const minutes = totalMinutes % 60;

    if (days > 0) {
        if (hours > 0) return `in ${days}d ${hours}h`;
        return `in ${days}d`;
    }

    if (minutes > 0) return `in ${hours}h ${minutes}m`;
    return `in ${hours}h`;
}

function formatReminderInfo(startDate, reminderText) {
    const startMs = new Date(startDate).getTime();
    if (!startMs) return 'Reminder unavailable';

    const reminderMinutes = parseReminderMinutes(reminderText);
    const notifyAt = startMs - (reminderMinutes * 60000);
    const notifyAtLabel = new Date(notifyAt).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
    });

    const leadTimeLabel = formatLeadTime(reminderMinutes);
    const etaLabel = formatTimeUntil(notifyAt);
    return `${leadTimeLabel} • ${notifyAtLabel} • ${etaLabel}`;
}

function getReminderMinutesForApp(app, requestData) {
    const direct = Number(app.notification_minutes);
    if (Number.isFinite(direct)) {
        return Math.max(0, direct);
    }
    return parseReminderMinutes(requestData?.reminder || 'N/A');
}

function ensureRequestFilterHandlers() {
    if (requestFilterHandlersBound) return;
    const ids = ['filter-requestor', 'filter-college', 'filter-facilitator', 'filter-status', 'filter-datetime', 'filter-date'];
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', applyRequestFiltersAndRender);
        }
    });

    const resetBtn = document.getElementById('reset-request-filters');
    if (resetBtn) {
        resetBtn.addEventListener('click', resetRequestFilters);
    }

    const todayBtn = document.getElementById('filter-date-today');
    if (todayBtn) {
        todayBtn.addEventListener('click', () => {
            const dateInput = document.getElementById('filter-date');
            if (!dateInput) return;

            dateInput.value = formatAdminLocalDate(new Date());
            applyRequestFiltersAndRender();
        });
    }

    requestFilterHandlersBound = true;
}

function resetRequestFilters() {
    const defaults = {
        'filter-requestor': 'all',
        'filter-college': 'all',
        'filter-facilitator': 'all',
        'filter-status': 'all',
        'filter-datetime': 'newest',
        'filter-date': ''
    };

    Object.keys(defaults).forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = defaults[id];
    });

    applyRequestFiltersAndRender();
}

function populateRequestFilterOptions(apps) {
    const requestorSelect = document.getElementById('filter-requestor');
    const collegeSelect = document.getElementById('filter-college');
    const facilitatorSelect = document.getElementById('filter-facilitator');

    if (!requestorSelect || !collegeSelect || !facilitatorSelect) return;

    const prevRequestor = requestorSelect.value || 'all';
    const prevCollege = collegeSelect.value || 'all';
    const prevFacilitator = facilitatorSelect.value || 'all';

    const requestors = new Set();
    const colleges = new Set();
    const facilitators = new Set();

    apps.forEach(app => {
        const requestData = getAppointmentRequestData(app);
        requestors.add(requestData.name !== 'N/A' ? requestData.name : (app.student_name || 'N/A'));
        colleges.add(requestData.dept !== 'N/A' ? requestData.dept : 'N/A');
        facilitators.add(app.facilitator_name || 'TBA');
    });

    const refill = (select, values) => {
        select.innerHTML = '<option value="all">All</option>';
        Array.from(values).sort((a, b) => a.localeCompare(b)).forEach(v => {
            const opt = document.createElement('option');
            opt.value = v;
            opt.textContent = v;
            select.appendChild(opt);
        });
    };

    refill(requestorSelect, requestors);
    refill(collegeSelect, colleges);
    refill(facilitatorSelect, facilitators);

    if (Array.from(requestors).includes(prevRequestor)) requestorSelect.value = prevRequestor;
    if (Array.from(colleges).includes(prevCollege)) collegeSelect.value = prevCollege;
    if (Array.from(facilitators).includes(prevFacilitator)) facilitatorSelect.value = prevFacilitator;
}

function applyRequestFiltersAndRender() {
    const grid = document.getElementById('requests-grid');
    const oldTbody = document.getElementById('requests-tbody');
    const container = grid || oldTbody;
    if (!container) return;
    const summary = document.getElementById('requests-summary');

    const requestor = document.getElementById('filter-requestor')?.value || 'all';
    const college = document.getElementById('filter-college')?.value || 'all';
    const facilitator = document.getElementById('filter-facilitator')?.value || 'all';
    const status = document.getElementById('filter-status')?.value || 'all';
    const dateSort = document.getElementById('filter-datetime')?.value || 'newest';
    const selectedDate = document.getElementById('filter-date')?.value || '';

    let filtered = requestAppointmentsCache.filter(app => {
        const requestData = getAppointmentRequestData(app);
        const appRequestor = requestData.name !== 'N/A' ? requestData.name : (app.student_name || 'N/A');
        const appCollege = requestData.dept !== 'N/A' ? requestData.dept : 'N/A';
        const appFacilitator = app.facilitator_name || 'TBA';

        if (requestor !== 'all' && appRequestor !== requestor) return false;
        if (college !== 'all' && appCollege !== college) return false;
        if (facilitator !== 'all' && appFacilitator !== facilitator) return false;
        if (status !== 'all' && String(app.booking_status).toLowerCase() !== String(status).toLowerCase()) return false;
        if (selectedDate && !String(app.date_time || '').startsWith(selectedDate)) return false;
        return true;
    });

    filtered.sort((a, b) => {
        const ta = new Date(a.date_time).getTime() || 0;
        const tb = new Date(b.date_time).getTime() || 0;
        return dateSort === 'oldest' ? ta - tb : tb - ta;
    });

    if (summary) {
        const total = requestAppointmentsCache.length;
        const shown = filtered.length;
        summary.textContent = `Showing ${shown} of ${total} appointment${total === 1 ? '' : 's'}`;
    }

    renderRequests(filtered, grid, oldTbody, container);
}

function renderRequests(apps, grid, oldTbody, container) {
    container.innerHTML = '';

    if (apps.length === 0) {
        container.innerHTML = '<div style="padding: 2rem; color: #64748b;">No active appointments found.</div>';
        return;
    }

    apps.forEach(app => {
        if (grid) {
            const card = document.createElement('div');
            card.className = 'app-card';

            const d = new Date(app.date_time);
            const dateStr = d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            const timeStr = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

            let endTimeStr = 'TBA';
            if (app.end_time) {
                const de = new Date(app.end_time);
                endTimeStr = de.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            }

            const requestData = getAppointmentRequestData(app);
            const reminderMinutes = getReminderMinutesForApp(app, requestData);
            const reminderInfo = formatReminderInfo(app.date_time, `${reminderMinutes} minutes`);
            const normalizedStatus = String(app.booking_status).trim().toUpperCase();
            const isClosedStatus = normalizedStatus === 'CANCELLED' || normalizedStatus === 'DECLINED';
            const closedLabel = normalizedStatus === 'DECLINED' ? 'DECLINED' : 'CANCELLED';

            let statusColor = '#eab308';
            if (normalizedStatus === 'CONFIRMED') statusColor = '#22c55e';
            if (normalizedStatus === 'COMPLETED') statusColor = '#2563eb';
            if (isClosedStatus) statusColor = '#ef4444';

            card.innerHTML = `
                <div class="app-card-type">${app.appointment_type}</div>
                <div class="app-card-status" style="color: ${statusColor};">
                    ${app.booking_status}
                </div>
                <div class="app-card-desc">(${app.topic})</div>
                
                <div class="app-card-detail">DATE: <span>${dateStr}</span></div>
                <div class="app-card-detail">TIME: <span>${timeStr} - ${endTimeStr}</span></div>
                <div class="app-card-detail">NAME: <span>${requestData.name}</span></div>
                <div class="app-card-detail">EMAIL: <span>${requestData.email}</span></div>
                <div class="app-card-detail">COLLEGE: <span>${requestData.dept}</span></div>
                <div class="app-card-detail">MODE OF APPOINTMENT: <span>${app.mode}</span></div>
                <div class="app-card-detail">VENUE/PLATFORM: <span>${app.venue}</span></div>
                <div class="app-card-detail">FACILITATOR: <span>${app.facilitator_name || 'TBA'}</span></div>
                ${isClosedStatus && app.cancellation_reason ? `<div class="app-card-detail">${closedLabel} REASON: <span>${app.cancellation_reason}</span></div>` : ''}
                ${isClosedStatus && app.cancelled_date_time ? `<div class="app-card-detail">${closedLabel} DATE: <span>${new Date(app.cancelled_date_time).toLocaleString()}</span></div>` : ''}
                
                <div class="app-card-detail" style="margin-top: 1rem;">NOTES: <span style="font-style: italic;">"${requestData.notes}"</span></div>
                <div class="app-card-reminder" title="Notification schedule">
                    <svg class="app-card-reminder-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path>
                        <path d="M9 17a3 3 0 0 0 6 0"></path>
                    </svg>
                    <span>${reminderInfo}</span>
                </div>
            `;

            const manageBtn = document.createElement('button');
            manageBtn.className = 'btn btn-outline btn-sm';
            manageBtn.textContent = 'Manage';
            manageBtn.onclick = () => editAppointment(app.session_id, app.booking_status, app.venue, app.facilitator_id, app.cancelled_date_time, app.cancelled_by, app.cancellation_reason);
            card.appendChild(manageBtn);

            grid.appendChild(card);
        } else if (oldTbody) {
            const tr = document.createElement('tr');
            const statusClass = app.booking_status === 'PENDING' ? 'pill-pending' : 'pill-confirmed';
            tr.innerHTML = `
                <td>
                    <div style="font-weight: 600;">${app.student_name}</div>
                    <div style="font-size: 0.75rem; color: #64748b;">Student</div>
                </td>
                <td>
                    <div style="font-weight: 600;">${app.appointment_type}</div>
                    <div style="font-size: 0.75rem; color: #64748b;">${app.topic}</div>
                </td>
                <td>${new Date(app.date_time).toLocaleString()}</td>
                <td>${app.facilitator_name || '<em style="color:#94a3b8">To Be Assigned</em>'}</td>
                <td><span class="status-pill ${statusClass}">${app.booking_status}</span></td>
                <td class="action-btns"></td>
            `;

            const btnContainer = tr.querySelector('.action-btns');
            const manageBtn = document.createElement('button');
            manageBtn.className = 'btn btn-outline btn-sm';
            manageBtn.textContent = 'Manage';
            manageBtn.onclick = () => editAppointment(app.session_id, app.booking_status, app.venue, app.facilitator_id, app.cancelled_date_time, app.cancelled_by, app.cancellation_reason);
            btnContainer.appendChild(manageBtn);

            if (app.booking_status === 'PENDING') {
                const acceptBtn = document.createElement('button');
                acceptBtn.className = 'btn btn-primary btn-sm';
                acceptBtn.textContent = 'Accept';
                acceptBtn.onclick = () => quickAccept(app.session_id);
                btnContainer.appendChild(acceptBtn);
            }

            oldTbody.appendChild(tr);
        }
    });
}

async function loadRequests() {
    const grid = document.getElementById('requests-grid');
    // Fallback if we're on the old UI table somehow
    const oldTbody = document.getElementById('requests-tbody');

    const container = grid || oldTbody;
    if (!container) return;

    container.innerHTML = '<div style="padding: 2rem; color: #64748b;">Loading requests...</div>';

    try {
        const res = await fetch('api.php?action=get_appointments');
        const data = await res.json();

        if (data.success) {
            requestAppointmentsCache = Array.isArray(data.appointments) ? data.appointments : [];
            ensureRequestFilterHandlers();
            populateRequestFilterOptions(requestAppointmentsCache);
            applyRequestFiltersAndRender();
        }
    } catch (e) {
        console.error(e);
    }
}

async function quickAccept(id) {
    try {
        const res = await fetch('api.php?action=update_appointment', {
            method: 'POST',
            body: JSON.stringify({ id, status: 'CONFIRMED' })
        });
        const data = await res.json();
        if (data.success) {
            notify('Appointment confirmed successfully');
            loadRequests();
        }
    } catch (e) { console.error(e); }
}

window.promptCancellationReason = function ({
    title = 'Cancel This Appointment?',
    message = 'You can optionally provide a cancellation reason before proceeding.',
    confirmText = 'Confirm Cancellation',
    cancelText = 'Go Back',
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

    const defaultConfirmText = confirmBtn.textContent;
    let loading = false;

    function setLoading(isLoading) {
        loading = Boolean(isLoading);
        reasonEl.disabled = loading;
        closeBtn.disabled = loading;
        confirmBtn.disabled = loading;
        confirmBtn.innerHTML = loading
            ? '<span class="prompt-spinner" aria-hidden="true"></span><span>Saving...</span>'
            : confirmText;
    }

    function closeModal() {
        cleanup();
    }

    return new Promise(resolve => {
        const onConfirm = () => {
            const val = reasonEl.value.trim();
            setLoading(true);
            resolve({ message: val, setLoading, close: closeModal });
        };

        const onClose = () => {
            cleanup();
            resolve(null);
        };

        const onBackdrop = (e) => {
            if (!loading && e.target === modal) onClose();
        };

        const onEsc = (e) => {
            if (!loading && e.key === 'Escape') onClose();
        };

        function cleanup() {
            modal.classList.remove('active');
            confirmBtn.removeEventListener('click', onConfirm);
            closeBtn.removeEventListener('click', onClose);
            modal.removeEventListener('click', onBackdrop);
            document.removeEventListener('keydown', onEsc);
            setLoading(false);
            confirmBtn.textContent = defaultConfirmText;
        }

        confirmBtn.addEventListener('click', onConfirm);
        closeBtn.addEventListener('click', onClose);
        modal.addEventListener('click', onBackdrop);
        document.addEventListener('keydown', onEsc);
        modal.classList.add('active');
        reasonEl.focus();
    });
};

async function loadSeminars() {
    const tbody = document.getElementById('seminars-tbody');
    if (!tbody) return;
    try {
        const res = await fetch('api.php?action=get_seminars');
        const data = await res.json();
        if (data.success) {
            tbody.innerHTML = '';
            data.seminars.forEach(s => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${s.title}</strong></td>
                    <td>${s.speaker}</td>
                    <td>${new Date(s.date_time).toLocaleString()}</td>
                    <td>${s.venue}</td>
                    <td class="action-btns"></td>
                `;

                const delBtn = document.createElement('button');
                delBtn.className = 'btn btn-sm btn-icon';
                delBtn.style.color = 'var(--danger)';
                delBtn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>`;
                delBtn.onclick = () => deleteSeminar(s.id);
                tr.querySelector('.action-btns').appendChild(delBtn);

                tbody.appendChild(tr);
            });
        }
    } catch (e) { }
}

async function deleteSeminar(id) {
    if (!confirm('Are you sure you want to remove this seminar?')) return;
    try {
        const res = await fetch('api.php?action=delete_seminar', {
            method: 'POST',
            body: JSON.stringify({ id })
        });
        if ((await res.json()).success) {
            notify('Seminar removed');
            loadSeminars();
        }
    } catch (e) { }
}

async function loadFacilitators(searchTerm = '') {
    const tbody = document.getElementById('facilitators-tbody');
    if (!tbody) return;
    try {
        const res = await fetch('api.php?action=get_facilitators');
        const data = await res.json();
        if (data.success) {
            tbody.innerHTML = '';
            const normalized = (searchTerm || '').trim().toLowerCase();
            const rows = data.facilitators.filter(f => {
                if (!normalized) return true;
                const fields = [f.name || '', f.position || '', f.departments || ''];
                return fields.join(' ').toLowerCase().includes(normalized);
            });

            rows.forEach(f => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${f.name}</strong></td>
                    <td>${f.departments || '<span style="opacity:0.5">N/A</span>'}</td>
                    <td>${f.position || '<span style="opacity:0.5">N/A</span>'}</td>
                    <td class="action-btns"></td>
                `;

                const btnContainer = tr.querySelector('.action-btns');

                const editBtn = document.createElement('button');
                editBtn.className = 'btn btn-outline btn-sm';
                editBtn.textContent = 'Edit Info';
                editBtn.onclick = () => openFacEdit(f.id, f.name, f.position, f.topic_ids || '', f.department_ids || '');
                btnContainer.appendChild(editBtn);

                const delBtn = document.createElement('button');
                delBtn.className = 'btn btn-sm btn-icon';
                delBtn.style.color = 'var(--danger)';
                delBtn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>`;
                delBtn.onclick = () => deleteFacilitator(f.id);
                btnContainer.appendChild(delBtn);

                tbody.appendChild(tr);
            });

            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="padding: 1rem; color: #94a3b8;">No facilitators match your search.</td></tr>';
            }
        }
    } catch (e) { }
}

async function loadTopicCatalog(searchTerm = '') {
    const tbody = document.getElementById('topics-tbody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="3" style="padding: 1rem; color: #94a3b8;">Loading topics...</td></tr>';

    try {
        const res = await fetch('api.php?action=get_topic_catalog');
        if (!res.ok) {
            tbody.innerHTML = '<tr><td colspan="3" style="padding: 1rem; color: #ef4444;">Failed to load topics.</td></tr>';
            return;
        }
        const data = await res.json();
        if (data.success && Array.isArray(data.topics)) {
            tbody.innerHTML = '';
            const normalized = (searchTerm || '').trim().toLowerCase();
            const rows = data.topics.filter(t => {
                if (!normalized) return true;
                const fields = [t.name || '', t.departments || '', t.facilitators || ''];
                return fields.join(' ').toLowerCase().includes(normalized);
            });

            rows.forEach(t => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${t.name}</strong></td>
                    <td>
                        <div>${t.departments || '<span style="opacity:0.5">No departments assigned</span>'}</div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Facilitators: ${t.facilitators || 'None'}</div>
                    </td>
                    <td class="action-btns"></td>
                `;

                const btnContainer = tr.querySelector('.action-btns');

                const editBtn = document.createElement('button');
                editBtn.className = 'btn btn-outline btn-sm';
                editBtn.textContent = 'Edit Topic';
                editBtn.onclick = () => openTopicEdit(t.id, t.name, t.department_ids || '', t.facilitator_ids || '');
                btnContainer.appendChild(editBtn);

                const delBtn = document.createElement('button');
                delBtn.className = 'btn btn-sm btn-icon';
                delBtn.style.color = 'var(--danger)';
                delBtn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>`;
                delBtn.onclick = () => deleteTopic(t.id, t.name);
                btnContainer.appendChild(delBtn);

                tbody.appendChild(tr);
            });

            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" style="padding: 1rem; color: #94a3b8;">No topics match your search.</td></tr>';
            }
        } else {
            tbody.innerHTML = '<tr><td colspan="3" style="padding: 1rem; color: #ef4444;">Topic payload is invalid.</td></tr>';
        }
    } catch (e) {
        console.error('Failed to load topic catalog:', e);
        tbody.innerHTML = '<tr><td colspan="3" style="padding: 1rem; color: #ef4444;">Error loading topics.</td></tr>';
    }
}

async function deleteTopic(id, name) {
    if (!confirm(`Delete topic "${name}"? This also removes its facilitator mappings.`)) return;
    try {
        const res = await fetch('api.php?action=delete_topic', {
            method: 'POST',
            body: JSON.stringify({ id })
        });
        if ((await res.json()).success) {
            notify('Topic removed');
            loadTopicCatalog();
            loadFacilitators();
            if (typeof refreshTopicChecklist === 'function') refreshTopicChecklist();
            if (typeof refreshTopicFacilitatorTable === 'function') refreshTopicFacilitatorTable();
        }
    } catch (e) { }
}

async function deleteFacilitator(id) {
    if (!confirm('Warning: Deleting a facilitator will also remove their associated appointments. Proceed?')) return;
    try {
        const res = await fetch('api.php?action=delete_facilitator', {
            method: 'POST',
            body: JSON.stringify({ id })
        });
        if ((await res.json()).success) {
            notify('Instructor removed');
            loadFacilitators();
            loadTopicCatalog();
            if (typeof refreshTopicFacilitatorTable === 'function') refreshTopicFacilitatorTable();
        }
    } catch (e) { }
}

function openSeminarModal() {
    const modal = document.getElementById('seminar-modal');
    if (modal) modal.classList.add('active');
}

function openFacilitatorModal() {
    const modal = document.getElementById('admin-facilitator-modal');
    if (modal) {
        document.getElementById('fac-modal-title').textContent = 'Register New Faculty Instructor';
        document.getElementById('fac-submit-btn').textContent = 'Save Faculty Profile';
        document.getElementById('fac-id').value = '';
        document.getElementById('admin-fac-form').reset();
        if (typeof resetFacilitatorPickerState === 'function') resetFacilitatorPickerState();
        if (typeof refreshTopicChecklist === 'function') refreshTopicChecklist();
        if (typeof refreshDeptChecklist === 'function') refreshDeptChecklist();
        modal.classList.add('active');
    }
}

function formatAdminLocalDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function parseAdminLocalDate(dateStr) {
    if (!dateStr) return new Date();
    const [year, month, day] = String(dateStr).split('-').map(Number);
    return new Date(year, month - 1, day);
}

function showAdminCalendarNotice(message) {
    let toast = document.getElementById('calendar-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'calendar-toast';
        toast.className = 'calendar-toast';
        document.body.appendChild(toast);
    }

    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(window.adminCalendarNoticeTimer);
    window.adminCalendarNoticeTimer = setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

let adminCalendarMonth = new Date().getMonth();
let adminCalendarYear = new Date().getFullYear();
let adminCalendarSelectedDate = formatAdminLocalDate(new Date());
let adminCalendarAppointments = [];
let adminCalendarSeminars = [];
let adminCalendarOffDays = [];
let adminCalendarUser = null;
let adminBookingSelectedFacId = null;

async function loadAdminCalendarContext() {
    try {
        const [userRes, appointmentsRes, seminarsRes, offDaysRes] = await Promise.all([
            fetch('api.php?action=get_user_info'),
            fetch('api.php?action=get_appointments'),
            fetch('api.php?action=get_seminars'),
            fetch('api.php?action=get_off_days')
        ]);

        const userData = await userRes.json();
        if (userData.success && String(userData.user?.role || '').toLowerCase() === 'admin') {
            adminCalendarUser = userData.user;
            fillAdminBookingRequestorFields();
        } else {
            adminCalendarUser = null;
        }

        const appointmentsData = await appointmentsRes.json();
        if (appointmentsData.success) {
            adminCalendarAppointments = Array.isArray(appointmentsData.appointments) ? appointmentsData.appointments : [];
        }

        const seminarsData = await seminarsRes.json();
        if (seminarsData.success) {
            adminCalendarSeminars = Array.isArray(seminarsData.seminars) ? seminarsData.seminars : [];
        }

        const offDaysData = await offDaysRes.json();
        if (offDaysData.success) {
            adminCalendarOffDays = Array.isArray(offDaysData.off_days) ? offDaysData.off_days : [];
        }

        renderAdminCalendar();
    } catch (error) {
        console.error('Failed to load admin calendar context:', error);
    }
}

function initAdminCalendar() {
    const prevBtn = document.getElementById('admin-calendar-prev');
    const nextBtn = document.getElementById('admin-calendar-next');
    const todayBtn = document.getElementById('admin-calendar-today');

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            adminCalendarMonth -= 1;
            if (adminCalendarMonth < 0) {
                adminCalendarMonth = 11;
                adminCalendarYear -= 1;
            }
            renderAdminCalendar();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            adminCalendarMonth += 1;
            if (adminCalendarMonth > 11) {
                adminCalendarMonth = 0;
                adminCalendarYear += 1;
            }
            renderAdminCalendar();
        });
    }

    if (todayBtn) {
        todayBtn.addEventListener('click', () => {
            const now = new Date();
            adminCalendarMonth = now.getMonth();
            adminCalendarYear = now.getFullYear();
            adminCalendarSelectedDate = formatAdminLocalDate(now);
            renderAdminCalendar();
        });
    }

    const actionClose = document.getElementById('admin-calendar-action-close');
    const actionCancel = document.getElementById('admin-calendar-action-cancel');
    const actionBook = document.getElementById('admin-calendar-action-book');
    const actionOffDay = document.getElementById('admin-calendar-action-offday-btn');
    const actionRemoveOffDay = document.getElementById('admin-calendar-action-remove-offday-btn');

    if (actionClose) actionClose.addEventListener('click', closeAdminCalendarActionModal);
    if (actionCancel) actionCancel.addEventListener('click', closeAdminCalendarActionModal);
    if (actionBook) actionBook.addEventListener('click', () => {
        const date = document.getElementById('admin-calendar-action-modal')?.dataset.date;
        if (date) openAdminBookingModal(date);
    });
    if (actionOffDay) actionOffDay.addEventListener('click', () => {
        const modal = document.getElementById('admin-calendar-action-modal');
        const date = modal?.dataset.date;
        if (date) openAdminOffDayModal(date);
    });

    if (actionRemoveOffDay) actionRemoveOffDay.addEventListener('click', async () => {
        const modal = document.getElementById('admin-calendar-action-modal');
        const date = modal?.dataset.date;
        if (!date) return;

        if (!confirm('Remove this off-day and make the date bookable again?')) {
            return;
        }

        try {
            const res = await fetch('api.php?action=delete_off_day', {
                method: 'POST',
                body: JSON.stringify({ date })
            });
            const data = await res.json();
            if (data.success) {
                notify('Off-day removed');
                closeAdminCalendarActionModal();
                closeAdminOffDayModal();
                await loadAdminCalendarContext();
            } else {
                notify(data.message || 'Failed to remove off-day', 'error');
            }
        } catch (error) {
            console.error(error);
            notify('Failed to remove off-day', 'error');
        }
    });

    const offDayClose = document.getElementById('admin-offday-close');
    const offDayCancel = document.getElementById('admin-offday-cancel');
    const offDaySave = document.getElementById('admin-offday-save');
    const offDayDelete = document.getElementById('admin-offday-delete');

    if (offDayClose) offDayClose.addEventListener('click', closeAdminOffDayModal);
    if (offDayCancel) offDayCancel.addEventListener('click', closeAdminOffDayModal);
    if (offDaySave) offDaySave.addEventListener('click', saveAdminOffDay);
    if (offDayDelete) offDayDelete.addEventListener('click', deleteAdminOffDay);

    const bookingClose = document.querySelector('#advanced-booking-modal .modal-close-new');
    if (bookingClose) bookingClose.addEventListener('click', closeAdvancedBooking);

    const bookingForm = document.getElementById('advanced-booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', submitAdminBooking);
    }

    const bookingType = document.getElementById('adv-booking-type');
    const bookingDept = document.getElementById('adv-dept-select');
    const bookingTopic = document.getElementById('adv-topic-select');
    const bookingStart = document.getElementById('booking-start-time');
    const bookingEnd = document.getElementById('booking-end-time');

    if (bookingType) bookingType.addEventListener('change', handleAdminBookingTypeChange);
    if (bookingDept) bookingDept.addEventListener('change', loadAdminTopicsForDepartment);
    if (bookingTopic) bookingTopic.addEventListener('change', loadAdminFacilitatorsForTopic);
    if (bookingStart) bookingStart.addEventListener('change', validateAdminBookingTime);
    if (bookingEnd) bookingEnd.addEventListener('change', validateAdminBookingTime);
}

function renderAdminCalendar() {
    const grid = document.getElementById('admin-calendar-grid');
    const monthTitle = document.getElementById('admin-calendar-month-year');
    if (!grid || !monthTitle) return;

    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    monthTitle.textContent = `${monthNames[adminCalendarMonth]} ${adminCalendarYear}`;

    grid.innerHTML = '';
    const firstDayOfMonth = new Date(adminCalendarYear, adminCalendarMonth, 1).getDay();
    const daysInMonth = new Date(adminCalendarYear, adminCalendarMonth + 1, 0).getDate();
    const prevMonthLastDay = new Date(adminCalendarYear, adminCalendarMonth, 0).getDate();
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const selectedDate = adminCalendarSelectedDate;

    const cells = [];
    for (let i = firstDayOfMonth - 1; i >= 0; i--) {
        const dayNum = prevMonthLastDay - i;
        const date = new Date(adminCalendarYear, adminCalendarMonth - 1, dayNum);
        const dateStr = formatAdminLocalDate(date);
        cells.push({ dayNum, dateStr, inactive: true });
    }

    for (let i = 1; i <= daysInMonth; i++) {
        const date = new Date(adminCalendarYear, adminCalendarMonth, i);
        const dateStr = formatAdminLocalDate(date);
        cells.push({ dayNum: i, dateStr, inactive: false });
    }

    const paddingNeeded = 42 - cells.length;
    for (let i = 1; i <= paddingNeeded; i++) {
        const date = new Date(adminCalendarYear, adminCalendarMonth + 1, i);
        const dateStr = formatAdminLocalDate(date);
        cells.push({ dayNum: i, dateStr, inactive: true });
    }

    cells.forEach(({ dayNum, dateStr, inactive }) => {
        const cell = document.createElement('div');
        const dateObj = parseAdminLocalDate(dateStr);
        const isToday = formatAdminLocalDate(today) === dateStr;
        const isSelected = selectedDate === dateStr;
        const isSunday = dateObj.getDay() === 0;
        const offDay = adminCalendarOffDays.find(item => item.date === dateStr);
        const appointmentCount = adminCalendarAppointments.filter(item => String(item.date_time || '').startsWith(dateStr)).length;
        const seminarCount = adminCalendarSeminars.filter(item => String(item.date_time || '').startsWith(dateStr)).length;

        cell.className = `calendar-cell ${inactive ? 'inactive' : ''} ${isToday ? 'today' : ''} ${isSelected ? 'selected' : ''} ${offDay ? 'off-day' : ''} ${isSunday ? 'sunday-closed' : ''}`;
        cell.innerHTML = `
            <span class="day-number">${dayNum}</span>
            <div class="day-content"></div>
        `;

        const content = cell.querySelector('.day-content');
        if (appointmentCount > 0) {
            const dot = document.createElement('div');
            dot.className = 'event-dot dot-booked';
            content.appendChild(dot);
        }
        if (seminarCount > 0) {
            const dot = document.createElement('div');
            dot.className = 'event-dot dot-seminar';
            content.appendChild(dot);
        }
        if (offDay) {
            const dot = document.createElement('div');
            dot.className = 'event-dot dot-offday';
            content.appendChild(dot);
            cell.title = offDay.description || 'Off-day';
        } else if (isSunday) {
            const dot = document.createElement('div');
            dot.className = 'event-dot dot-closed';
            content.appendChild(dot);
            cell.title = 'Library is closed on Sundays';
        }

        cell.addEventListener('click', () => {
            adminCalendarSelectedDate = dateStr;
            renderAdminCalendar();
            openAdminCalendarActionModal(dateStr);
        });

        grid.appendChild(cell);
    });
}

function openAdminCalendarActionModal(dateStr) {
    const modal = document.getElementById('admin-calendar-action-modal');
    const title = document.getElementById('admin-calendar-action-title');
    const dateEl = document.getElementById('admin-calendar-action-date');
    const offDayEl = document.getElementById('admin-calendar-action-offday');
    const actionOffDayBtn = document.getElementById('admin-calendar-action-offday-btn');
    const actionRemoveOffDay = document.getElementById('admin-calendar-action-remove-offday-btn');

    if (!modal || !dateEl) return;

    const dateObj = parseAdminLocalDate(dateStr);
    const prettyDate = dateObj.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    const offDay = adminCalendarOffDays.find(item => item.date === dateStr);

    modal.dataset.date = dateStr;
    if (title) title.textContent = 'Choose Action';
    dateEl.textContent = prettyDate;
    if (offDayEl) {
        if (offDay) {
            offDayEl.style.display = 'block';
            offDayEl.textContent = offDay.description ? `Off-day reason: ${offDay.description}` : 'This date is currently marked as an off-day.';
        } else {
            offDayEl.style.display = 'none';
            offDayEl.textContent = '';
        }
    }
    if (actionOffDayBtn) {
        actionOffDayBtn.textContent = offDay ? 'Update Off-Day' : 'Mark Off-Day';
    }
    if (actionRemoveOffDay) {
        actionRemoveOffDay.style.display = offDay ? 'inline-flex' : 'none';
    }

    modal.classList.add('active');
}

function closeAdminCalendarActionModal() {
    const modal = document.getElementById('admin-calendar-action-modal');
    if (modal) modal.classList.remove('active');
}

function openAdminOffDayModal(dateStr) {
    const modal = document.getElementById('admin-offday-modal');
    const dateEl = document.getElementById('admin-offday-date');
    const descEl = document.getElementById('admin-offday-description');
    const existing = adminCalendarOffDays.find(item => item.date === dateStr);

    if (!modal || !dateEl || !descEl) return;

    modal.dataset.date = dateStr;
    dateEl.textContent = parseAdminLocalDate(dateStr).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    descEl.value = existing?.description || '';
    modal.classList.add('active');
}

function closeAdminOffDayModal() {
    const modal = document.getElementById('admin-offday-modal');
    if (modal) modal.classList.remove('active');
}

async function saveAdminOffDay() {
    const modal = document.getElementById('admin-offday-modal');
    const date = modal?.dataset.date;
    const description = document.getElementById('admin-offday-description')?.value.trim() || '';
    if (!date) return;

    try {
        const res = await fetch('api.php?action=save_off_day', {
            method: 'POST',
            body: JSON.stringify({ date, description })
        });
        const data = await res.json();
        if (data.success) {
            notify('Off-day saved successfully');
            closeAdminOffDayModal();
            closeAdminCalendarActionModal();
            await loadAdminCalendarContext();
        } else {
            const detail = data.error ? ` (${data.error})` : '';
            notify((data.message || 'Failed to save off-day') + detail, 'error');
        }
    } catch (error) {
        console.error(error);
        notify(`Failed to save off-day (${error.message || 'unknown error'})`, 'error');
    }
}

async function deleteAdminOffDay() {
    const modal = document.getElementById('admin-offday-modal');
    const date = modal?.dataset.date;
    if (!date) return;

    try {
        const res = await fetch('api.php?action=delete_off_day', {
            method: 'POST',
            body: JSON.stringify({ date })
        });
        const data = await res.json();
        if (data.success) {
            notify('Off-day removed');
            closeAdminOffDayModal();
            closeAdminCalendarActionModal();
            await loadAdminCalendarContext();
        } else {
            const detail = data.error ? ` (${data.error})` : '';
            notify((data.message || 'Failed to remove off-day') + detail, 'error');
        }
    } catch (error) {
        console.error(error);
        notify(`Failed to remove off-day (${error.message || 'unknown error'})`, 'error');
    }
}

function fillAdminBookingRequestorFields() {
    if (!adminCalendarUser) return;
    const nameEl = document.getElementById('staff-req-name');
    const emailEl = document.getElementById('staff-req-email');
    const deptEl = document.getElementById('staff-req-dept');

    if (nameEl && !nameEl.value) nameEl.value = adminCalendarUser.name || '';
    if (emailEl && !emailEl.value) emailEl.value = adminCalendarUser.email || '';
    if (deptEl && !deptEl.value && adminCalendarUser.department_id) {
        deptEl.value = String(adminCalendarUser.department_id);
    }
}

async function initAdminBookingModal() {
    const modal = document.getElementById('advanced-booking-modal');
    if (!modal) return;

    try {
        const res = await fetch('api.php?action=get_departments');
        const data = await res.json();
        if (data.success) {
            const deptSelect = document.getElementById('adv-dept-select');
            const reqDeptSelect = document.getElementById('staff-req-dept');
            if (deptSelect) {
                deptSelect.innerHTML = '<option value="" disabled selected>Select a department...</option>';
                data.departments.forEach(dept => {
                    const opt = document.createElement('option');
                    opt.value = dept.id;
                    opt.textContent = dept.name;
                    deptSelect.appendChild(opt);
                });
            }
            if (reqDeptSelect) {
                reqDeptSelect.innerHTML = '<option value="" disabled selected>Select department...</option>';
                data.departments.forEach(dept => {
                    const opt = document.createElement('option');
                    opt.value = dept.id;
                    opt.textContent = dept.name;
                    reqDeptSelect.appendChild(opt);
                });
            }
            fillAdminBookingRequestorFields();
        }
    } catch (error) {
        console.error('Failed to load departments for booking modal:', error);
    }
}

function openAdminBookingModal(dateStr) {
    const modal = document.getElementById('advanced-booking-modal');
    const dateDisplay = document.getElementById('booking-date-display');
    if (!modal || !dateDisplay) return;

    adminCalendarSelectedDate = dateStr;
    const prettyDate = parseAdminLocalDate(dateStr).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    dateDisplay.textContent = prettyDate;
    const form = document.getElementById('advanced-booking-form');
    if (form) form.reset();
    fillAdminBookingRequestorFields();
    adminBookingSelectedFacId = null;
    handleAdminBookingTypeChange();
    modal.classList.add('active');
}

function closeAdvancedBooking() {
    const modal = document.getElementById('advanced-booking-modal');
    if (modal) modal.classList.remove('active');
}

async function loadAdminTopicsForDepartment() {
    const deptId = document.getElementById('adv-dept-select')?.value;
    const topicSelect = document.getElementById('adv-topic-select');
    if (!topicSelect) return;

    adminBookingSelectedFacId = null;
    if (!deptId) {
        topicSelect.innerHTML = '<option value="" disabled selected>Select a department first...</option>';
        return;
    }

    try {
        const res = await fetch(`api.php?action=get_topics&department_id=${encodeURIComponent(deptId)}`);
        const data = await res.json();
        topicSelect.innerHTML = '<option value="" disabled selected>Select a topic...</option>';
        if (data.success) {
            data.topics.forEach(topic => {
                const opt = document.createElement('option');
                opt.value = topic.id;
                opt.textContent = topic.name;
                topicSelect.appendChild(opt);
            });
        }
    } catch (error) {
        console.error('Failed to load admin topics:', error);
    }
}

async function loadAdminFacilitatorsForTopic() {
    const topicId = document.getElementById('adv-topic-select')?.value;
    const list = document.getElementById('modal-instructor-list');
    if (!list) return;

    if (!topicId) {
        list.innerHTML = '<div class="loader-container">Select a topic to view facilitators.</div>';
        return;
    }

    try {
        const res = await fetch(`api.php?action=get_facilitators&topic_id=${encodeURIComponent(topicId)}`);
        const data = await res.json();
        if (!data.success || !Array.isArray(data.facilitators) || data.facilitators.length === 0) {
            list.innerHTML = '<div class="loader-container">No facilitators available for the selected topic.</div>';
            return;
        }

        list.innerHTML = '';
        data.facilitators.forEach(facilitator => {
            const card = document.createElement('div');
            card.className = 'fac-card-new';
            card.innerHTML = `
                <div class="fac-avatar-new">${(facilitator.name || '?').charAt(0)}</div>
                <div class="fac-info-new">
                    <h5>${facilitator.name}</h5>
                    <p>${facilitator.position || 'Facilitator'}</p>
                    <p>${facilitator.departments || 'Department not listed'}</p>
                </div>
            `;

            card.addEventListener('click', () => {
                document.querySelectorAll('.fac-card-new').forEach(item => item.classList.remove('selected'));
                card.classList.add('selected');
                adminBookingSelectedFacId = facilitator.id;
            });

            if (adminBookingSelectedFacId && String(adminBookingSelectedFacId) === String(facilitator.id)) {
                card.classList.add('selected');
            }

            list.appendChild(card);
        });
    } catch (error) {
        console.error('Failed to load facilitators:', error);
        list.innerHTML = '<div class="loader-container">Unable to load facilitators.</div>';
    }
}

function handleAdminBookingTypeChange() {
    const typeSelect = document.getElementById('adv-booking-type');
    if (!typeSelect) return;

    const type = typeSelect.value;
    const deptSection = document.getElementById('dept-section');
    const topicSection = document.getElementById('topic-section');
    const instructorSection = document.getElementById('instructor-section');
    const timeSection = document.getElementById('time-selection-section');
    const standardTimeInputs = document.getElementById('standard-time-inputs');
    const wholeDayNotice = document.getElementById('whole-day-notice');

    if (type === 'Instructional Program') {
        if (deptSection) deptSection.style.display = 'block';
        if (topicSection) topicSection.style.display = 'block';
        if (instructorSection) instructorSection.style.display = 'block';
        if (timeSection) timeSection.style.display = 'block';
        if (standardTimeInputs) standardTimeInputs.style.display = 'block';
        if (wholeDayNotice) wholeDayNotice.style.display = 'none';
    } else if (type === 'Orientation') {
        if (deptSection) deptSection.style.display = 'none';
        if (topicSection) topicSection.style.display = 'none';
        if (instructorSection) instructorSection.style.display = 'none';
        if (timeSection) timeSection.style.display = 'block';
        if (standardTimeInputs) standardTimeInputs.style.display = 'block';
        if (wholeDayNotice) wholeDayNotice.style.display = 'none';
        adminBookingSelectedFacId = null;
    } else if (type === 'Seminar') {
        if (deptSection) deptSection.style.display = 'none';
        if (topicSection) topicSection.style.display = 'none';
        if (instructorSection) instructorSection.style.display = 'none';
        if (timeSection) timeSection.style.display = 'block';
        if (standardTimeInputs) standardTimeInputs.style.display = 'none';
        if (wholeDayNotice) wholeDayNotice.style.display = 'flex';
        const start = document.getElementById('booking-start-time');
        const end = document.getElementById('booking-end-time');
        if (start) start.value = '09:00';
        if (end) end.value = '20:00';
    } else {
        if (deptSection) deptSection.style.display = 'none';
        if (topicSection) topicSection.style.display = 'none';
        if (instructorSection) instructorSection.style.display = 'none';
        if (timeSection) timeSection.style.display = 'none';
        if (standardTimeInputs) standardTimeInputs.style.display = 'block';
        if (wholeDayNotice) wholeDayNotice.style.display = 'none';
        adminBookingSelectedFacId = null;
    }
}

function validateAdminBookingTime() {
    const type = document.getElementById('adv-booking-type')?.value;
    if (!type || type === 'Seminar') return true;

    const startTime = document.getElementById('booking-start-time')?.value;
    const endTime = document.getElementById('booking-end-time')?.value;
    const errorEl = document.getElementById('time-error-msg');
    if (!errorEl) return true;

    if (!startTime || !endTime) {
        errorEl.textContent = '';
        errorEl.style.display = 'none';
        return true;
    }

    const [startHour, startMinute] = startTime.split(':').map(Number);
    const [endHour, endMinute] = endTime.split(':').map(Number);
    const startMinutes = startHour * 60 + startMinute;
    const endMinutes = endHour * 60 + endMinute;

    if (startMinutes < 540 || endMinutes > 1200) {
        errorEl.textContent = 'Time selection is limited from 9:00 AM to 8:00 PM.';
        errorEl.style.display = 'block';
        return false;
    }

    if (endMinutes <= startMinutes) {
        errorEl.textContent = 'End time must be after start time.';
        errorEl.style.display = 'block';
        return false;
    }

    const diff = endMinutes - startMinutes;
    if (type === 'Instructional Program' || type === 'Orientation') {
        if (diff < 30) {
            errorEl.textContent = `${type} bookings must be at least 30 minutes.`;
            errorEl.style.display = 'block';
            return false;
        }
        if (diff > 240) {
            errorEl.textContent = `${type} bookings cannot exceed 4 hours.`;
            errorEl.style.display = 'block';
            return false;
        }
    }

    errorEl.textContent = '';
    errorEl.style.display = 'none';
    return true;
}

async function submitAdminBooking(event) {
    event.preventDefault();

    const type = document.getElementById('adv-booking-type')?.value;
    if (!type) {
        notify('Please select a booking type.', 'error');
        return;
    }

    if (type === 'Instructional Program' && !adminBookingSelectedFacId) {
        notify('Please select an instructor for the Instructional Program.', 'error');
        return;
    }

    const requestorName = document.getElementById('staff-req-name')?.value.trim() || '';
    const requestorEmail = document.getElementById('staff-req-email')?.value.trim() || '';
    const requestorDept = document.getElementById('staff-req-dept')?.value || '';
    if (!requestorName || !requestorEmail || !requestorDept) {
        notify('Requestor name, email, and department are required for admin bookings.', 'error');
        return;
    }

    if (!validateAdminBookingTime()) return;

    const bookingDate = adminCalendarSelectedDate;
    const startTime = document.getElementById('booking-start-time')?.value || '09:00';
    const endTime = document.getElementById('booking-end-time')?.value || '20:00';
    const payload = {
        type,
        facilitator_id: adminBookingSelectedFacId || 0,
        topic: type === 'Instructional Program' ? (document.getElementById('adv-topic-select')?.selectedOptions?.[0]?.textContent || 'Library Consultation') : type,
        date_time: `${bookingDate} ${startTime}:00`,
        end_time: `${bookingDate} ${endTime}:00`,
        mode: document.querySelector('input[name="book-mode"]:checked')?.value || 'Onsite',
        name: requestorName,
        email: requestorEmail,
        phone: document.getElementById('book-phone')?.value || '',
        department: requestorDept,
        notes: document.getElementById('book-notes')?.value || '',
        reminder: document.getElementById('book-reminder')?.value || '30',
        custom_requestor: {
            name: requestorName,
            email: requestorEmail,
            dept_id: requestorDept,
            dept_name: document.getElementById('staff-req-dept')?.selectedOptions?.[0]?.textContent || ''
        }
    };

    try {
        const res = await fetch('api.php?action=advanced_booking', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            notify('Appointment booked successfully');
            closeAdvancedBooking();
            await loadRequests();
            await loadAdminCalendarContext();
        } else {
            notify(data.message || 'Unable to create booking.', 'error');
        }
    } catch (error) {
        console.error(error);
        notify('Unable to create booking.', 'error');
    }
}
