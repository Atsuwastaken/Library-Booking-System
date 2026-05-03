document.addEventListener('DOMContentLoaded', () => {
    loadRequests();
    loadSeminars();
    loadFacilitators();
    loadTopicCatalog();
    loadUsersAdminData();
    loadAdminCalendarContext();
    loadFilterOptions();
    initAdminCalendar();
    initAdminBookingModal();
    initTabs();
    initExportLogs();
    initExportSessions();
    initUsersDirectoryUI();
    initAdminAppointmentsSubtabs();

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
            renderUsersAdminList();
        });
    }

    // Global modal close handler for elements with data-close-modal or clicking the backdrop
    document.addEventListener('click', (e) => {
        if (e.target.hasAttribute('data-close-modal') || e.target.closest('[data-close-modal]')) {
            const modal = e.target.closest('.admin-modal') || e.target.closest('.modal-overlay');
            if (modal) modal.classList.remove('active');
        } else if (e.target.classList.contains('modal-overlay')) {
            // Close when clicking the backdrop
            e.target.classList.remove('active');
        }
    });

    // Modal open handlers
    document.getElementById('open-add-general-modal')?.addEventListener('click', () => {
        document.getElementById('add-general-modal')?.classList.add('active');
    });
    document.getElementById('open-add-staff-modal')?.addEventListener('click', () => {
        document.getElementById('add-staff-modal')?.classList.add('active');
    });
    document.getElementById('open-add-admin-modal')?.addEventListener('click', () => {
        document.getElementById('add-admin-modal')?.classList.add('active');
    });
    document.getElementById('open-add-facilitator-user-modal')?.addEventListener('click', () => {
        if (typeof resetFacilitatorPickerState === 'function') resetFacilitatorPickerState();
        const modalTitle = document.getElementById('fac-modal-title');
        const submitBtn = document.getElementById('fac-submit-btn');
        const facIdInput = document.getElementById('fac-id');
        if (modalTitle) modalTitle.textContent = 'Register New Faculty Instructor';
        if (submitBtn) submitBtn.textContent = 'Save Faculty Profile';
        if (facIdInput) facIdInput.value = '';
        document.getElementById('admin-facilitator-modal')?.classList.add('active');
    });

    // User Directory Utility Buttons
    document.getElementById('export-users-csv-btn')?.addEventListener('click', handleExportUsersCsv);
    document.getElementById('import-users-csv-btn')?.addEventListener('click', handleImportUsersCsv);

    document.querySelectorAll('.select-all-users-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
            const pane = e.target.dataset.pane;
            const paneMap = {
                'general': 'users-admin-general-list',
                'staff': 'users-admin-staff-list',
                'facilitators': 'users-admin-facilitators-list',
                'admins': 'users-admin-admins-list'
            };
            const containerId = paneMap[pane];
            const container = document.getElementById(containerId);
            if (!container) return;
            const userCheckboxes = container.querySelectorAll('.user-checkbox');
            userCheckboxes.forEach(cb => {
                cb.checked = e.target.checked;
                if (cb.checked) {
                    selectedUserIds.add(String(cb.value));
                } else {
                    selectedUserIds.delete(String(cb.value));
                }
            });
            updateDeleteButtonState();
            updateSelectAllCheckboxState(containerId, pane);
        });
    });

    document.getElementById('delete-all-users-btn')?.addEventListener('click', () => {
        if (selectedUserIds.size > 0) {
            handleDeleteSelectedUsers();
        } else {
            handleDeleteAllUsers();
        }
    });

    // Multi-select Session Actions
    const selectAllSessionsCheckbox = document.getElementById('select-all-sessions');
    if (selectAllSessionsCheckbox) {
        selectAllSessionsCheckbox.addEventListener('change', handleSelectAllSessions);
    }
    document.getElementById('archive-selected-sessions-btn')?.addEventListener('click', handleArchiveSelectedSessions);
});

// --- State ---
let requestAppointmentsCache = [];
let requestFilterHandlersBound = false;
let adminUsersCache = [];
let adminRegistrationRequestsCache = [];
let adminDepartmentOptionsCache = [];
let adminUsersSearchTerm = '';
let selectedSessionIds = new Set();
let selectedUserIds = new Set();
let adminAppointmentsSubview = 'all';

// --- Utilities ---
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function normalizeText(value) {
    return String(value ?? '').trim().toLowerCase();
}

function normalizeRole(roleValue) {
    const value = normalizeText(roleValue);
    if (value === 'admin' || value === 'staff' || value === 'general') return value;
    return 'general';
}

function isTruthy(value) {
    if (typeof value === 'boolean') return value;
    if (typeof value === 'number') return value !== 0;
    const normalized = normalizeText(value);
    return normalized === '1' || normalized === 'true' || normalized === 'yes';
}

function matchesUsersSearch(values) {
    if (!adminUsersSearchTerm) return true;
    const search = adminUsersSearchTerm.toLowerCase();
    return values.some(v => (v || '').toString().toLowerCase().includes(search));
}

function notify(message, type = 'success') {
    const container = document.getElementById('admin-toasts');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type === 'error' ? 'error' : ''}`;
    toast.innerHTML = `<span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// --- Tabs & UI Init ---
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
            if (tab === 'users') loadUsersAdminData();
        });
    });
}

function initAdminAppointmentsSubtabs() {
    const subtabButtons = document.querySelectorAll('#admin-appointments-subtabs .appointment-subtab-btn');
    subtabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            setAdminAppointmentsSubview(btn.getAttribute('data-view'));
        });
    });
}

function setAdminAppointmentsSubview(view) {
    if (view !== 'archived') view = 'all';
    adminAppointmentsSubview = view;
    const subtabButtons = document.querySelectorAll('#admin-appointments-subtabs .appointment-subtab-btn');
    subtabButtons.forEach(btn => {
        const isActive = btn.getAttribute('data-view') === view;
        btn.classList.toggle('active', isActive);
        btn.classList.toggle('btn-primary', isActive);
        btn.classList.toggle('btn-outline', !isActive);
    });

    const archiveText = document.getElementById('archive-selected-text');
    const selectAllLabel = document.getElementById('select-all-label');
    if (view === 'archived') {
        if (archiveText) archiveText.textContent = 'Unarchive Selected';
        if (selectAllLabel) selectAllLabel.textContent = 'Select All for Unarchive';
    } else {
        if (archiveText) archiveText.textContent = 'Archive Selected';
        if (selectAllLabel) selectAllLabel.textContent = 'Select All';
    }

    if (view === 'archived') {
        selectedSessionIds.clear();
        document.querySelectorAll('.session-checkbox').forEach(cb => cb.checked = false);
        const selectAllCb = document.getElementById('select-all-sessions');
        if (selectAllCb) selectAllCb.checked = false;
        updateSessionSelectionUI();
    }
    loadRequests();
}

function initUsersDirectoryUI() {
    const tabButtons = document.querySelectorAll('.users-directory-tab[data-users-pane]');
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetPane = button.getAttribute('data-users-pane');
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            document.querySelectorAll('.users-directory-pane').forEach(pane => pane.classList.remove('active'));
            const pane = document.getElementById(`users-pane-${targetPane}`);
            if (pane) pane.classList.add('active');
        });
    });

    // Form handlers for adding users
    const addGeneralForm = document.getElementById('add-general-form');
    if (addGeneralForm) {
        addGeneralForm.addEventListener('submit', (e) => {
            e.preventDefault();
            handleCreateAdminUser(e.currentTarget, {
                role: 'general',
                facilitatorEnabled: false,
                modalId: 'add-general-modal',
                successMessage: 'General account added successfully.'
            });
        });
        const userTypeSelect = document.getElementById('add-general-user-type');
        const deptSelect = document.getElementById('add-general-dept');
        const studentFields = document.getElementById('add-general-student-fields');
        const programSelect = document.getElementById('add-general-program');
        if (userTypeSelect && studentFields) {
            userTypeSelect.addEventListener('change', () => {
                studentFields.style.display = userTypeSelect.value === 'student' ? 'grid' : 'none';
            });
        }
        if (deptSelect && programSelect) {
            deptSelect.addEventListener('change', async () => {
                const deptId = deptSelect.value;
                const deptName = deptSelect.options[deptSelect.selectedIndex]?.text || '';
                const isBasicEd = ["Grade School", "Junior High School", "Preschool"].some(d => deptName.toLowerCase().includes(d.toLowerCase()));
                const programGroup = document.getElementById('add-general-program-group');
                const yearGroup = document.getElementById('add-general-year-level-group');
                const statusGroup = document.getElementById('add-general-status-group');

                if (programGroup) {
                    programGroup.style.display = isBasicEd ? 'none' : 'block';
                    if (isBasicEd) programSelect.value = '';
                }
                if (yearGroup) {
                    yearGroup.style.display = isBasicEd ? 'none' : 'block';
                    if (isBasicEd) document.getElementById('add-general-year-level').value = '';
                }
                if (statusGroup) {
                    statusGroup.style.display = isBasicEd ? 'none' : 'block';
                    if (isBasicEd) document.getElementById('add-general-status').value = '';
                }

                programSelect.innerHTML = '<option value="">Select Program</option>';
                if (!deptId) return;
                try {
                    const res = await fetch(`api.php?action=get_programs&department_id=${deptId}`);
                    const data = await res.json();
                    if (data.success) {
                        data.programs.forEach(p => {
                            const opt = document.createElement('option');
                            opt.value = p.id;
                            opt.textContent = p.name;
                            programSelect.appendChild(opt);
                        });
                    }
                } catch (e) { console.error(e); }
            });
        }
    }

    const addStaffForm = document.getElementById('add-staff-form');
    if (addStaffForm) {
        addStaffForm.addEventListener('submit', (e) => {
            e.preventDefault();
            handleCreateAdminUser(e.currentTarget, {
                role: 'staff',
                facilitatorEnabled: false,
                modalId: 'add-staff-modal',
                successMessage: 'Staff account added successfully.'
            });
        });
    }

    const addAdminForm = document.getElementById('add-admin-form');
    if (addAdminForm) {
        addAdminForm.addEventListener('submit', (e) => {
            e.preventDefault();
            handleCreateAdminUser(e.currentTarget, {
                role: 'admin',
                facilitatorEnabled: false,
                modalId: 'add-admin-modal',
                successMessage: 'Admin account added successfully.'
            });
        });
    }
}

// --- User Management Logic ---
async function loadUsersAdminData() {
    const listContainers = ['users-admin-general-list', 'users-admin-staff-list', 'users-admin-facilitators-list', 'users-admin-admins-list'];
    listContainers.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '<div style="padding: 20px; text-align: center; color: #94a3b8;">Loading...</div>';
    });
    try {
        const [usersRes, requestsRes, departmentsRes] = await Promise.all([
            fetch('api.php?action=get_users_admin'),
            fetch('api.php?action=get_registration_requests&status=PENDING'),
            fetch('api.php?action=get_departments')
        ]);
        const usersData = await usersRes.json();
        const requestsData = await requestsRes.json();
        const departmentsData = await departmentsRes.json();

        if (!usersData.success) {
            console.error('Failed to load users:', usersData.message);
            listContainers.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = `<div style="padding: 20px; text-align: center; color: #ef4444;">${usersData.message || 'Failed to load users.'}</div>`;
            });
            return;
        }

        adminUsersCache = usersData.success ? usersData.users : [];
        adminRegistrationRequestsCache = requestsData.success ? requestsData.requests : [];
        adminDepartmentOptionsCache = departmentsData.success ? departmentsData.departments : [];

        const usersCountEl = document.getElementById('users-total-count');
        if (usersCountEl) usersCountEl.textContent = `${adminUsersCache.length} users`;

        renderUsersAdminList();
        renderRegistrationRequestsTable();

        // Populate creation modal facilitator dropdowns
        document.querySelectorAll('.facilitator-link-dropdown').forEach(async sel => {
            sel.innerHTML = '<option value="">None (no facilitator privileges)</option>';
            try {
                const res = await fetch('api.php?action=get_facilitators');
                const data = await res.json();
                if (data.success) {
                    data.facilitators.forEach(f => {
                        const opt = document.createElement('option');
                        opt.value = f.id;
                        opt.textContent = f.name;
                        sel.appendChild(opt);
                    });
                }
            } catch (e) { }
        });
    } catch (error) {
        console.error('Failed to load users:', error);
    }
}

function renderUsersAdminList() {
    const filteredUsers = adminUsersCache.filter(user => matchesUsersSearch([
        user.name, user.email, user.student_number, user.department_name, user.role, user.facilitator_name
    ]));

    const generals = filteredUsers.filter(user => normalizeRole(user.role) === 'general');
    const staff = filteredUsers.filter(user => normalizeRole(user.role) === 'staff');
    const facilitators = filteredUsers.filter(user => isTruthy(user.is_facilitator));
    const admins = filteredUsers.filter(user => normalizeRole(user.role) === 'admin');

    renderUserList(generals, 'users-admin-general-list', 'general');
    renderUserList(staff, 'users-admin-staff-list', 'staff');
    renderUserList(facilitators, 'users-admin-facilitators-list', 'facilitator');
    renderUserList(admins, 'users-admin-admins-list', 'admin');
}

function renderUserList(users, containerId, listRole) {
    const container = document.getElementById(containerId);
    if (!container) return;
    if (!users.length) {
        container.innerHTML = '<div style="padding: 20px; text-align: center; color: #94a3b8;">No users found.</div>';
        return;
    }
    container.innerHTML = '';
    users.forEach(user => {
        const item = document.createElement('div');
        item.className = 'user-compact-item';
        let subInfo = listRole === 'general' ? `${user.user_type || 'N/A'} • ${user.department_name || 'No Dept'}` : (listRole === 'facilitator' ? `Instructor Profile: ${user.facilitator_name || 'None'}` : 'System Account');

        const isLinkedFacilitator = isTruthy(user.is_facilitator);
        const facIndicator = isLinkedFacilitator ? `<span class="role-badge facilitator-badge" style="background:#eef2ff; color:#6366f1; border:1px solid #e0e7ff;">Facilitator</span>` : '';

        item.innerHTML = `
            <input type="checkbox" class="user-checkbox" value="${user.id}" ${selectedUserIds.has(String(user.id)) ? 'checked' : ''}>
            <div class="user-info" style="flex:1;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <strong>${escapeHtml(user.name)}</strong>
                    ${facIndicator}
                </div>
                <span>${escapeHtml(user.email)}</span>
                <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                    <span class="role-badge">${user.role}</span>
                    <span style="font-size:0.75rem; color:#94a3b8;">${escapeHtml(subInfo)}</span>
                </div>
            </div>
            <div class="action-btns">
                <button class="btn btn-outline btn-sm btn-edit-user">Edit User</button>
                <button class="btn btn-danger btn-sm users-delete-btn">Delete</button>
            </div>
        `;
        const checkbox = item.querySelector('.user-checkbox');
        checkbox.addEventListener('change', () => {
            if (checkbox.checked) {
                selectedUserIds.add(String(user.id));
            } else {
                selectedUserIds.delete(String(user.id));
            }
            updateDeleteButtonState();
            updateSelectAllCheckboxState(containerId, listRole);
        });
        item.querySelector('.btn-edit-user').addEventListener('click', () => openEditUserModal(user));
        item.querySelector('.users-delete-btn').addEventListener('click', () => deleteAdminUser(user.id, user.name));
        container.appendChild(item);
    });
}

function updateDeleteButtonState() {
    const count = selectedUserIds.size;
    const btnText = document.getElementById('delete-btn-text');
    const countSpan = document.getElementById('selected-user-count');
    const deleteBtn = document.getElementById('delete-all-users-btn');
    if (!deleteBtn) return;
    if (count > 0) {
        if (btnText) btnText.textContent = 'Delete Selected';
        if (countSpan) countSpan.textContent = count;
        deleteBtn.classList.add('has-selection');
    } else {
        if (btnText) btnText.textContent = 'Delete All Users';
        if (countSpan) countSpan.textContent = '0';
        deleteBtn.classList.remove('has-selection');
    }
}

function updateSelectAllCheckboxState(containerId, listRole) {
    const paneMap = {
        'users-admin-general-list': 'general',
        'users-admin-staff-list': 'staff',
        'users-admin-facilitators-list': 'facilitators',
        'users-admin-admins-list': 'admins'
    };
    const pane = paneMap[containerId];
    if (!pane) return;
    const selectAllCheckbox = document.querySelector(`.select-all-users-checkbox[data-pane="${pane}"]`);
    if (!selectAllCheckbox) return;
    const container = document.getElementById(containerId);
    if (!container) return;
    const allCheckboxes = container.querySelectorAll('.user-checkbox');
    const checkedCount = container.querySelectorAll('.user-checkbox:checked').length;
    selectAllCheckbox.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
    selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < allCheckboxes.length;
}

function renderRegistrationRequestsTable() {
    const tbody = document.getElementById('registration-requests-tbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    const filteredRequests = adminRegistrationRequestsCache.filter(req => matchesUsersSearch([
        req.name, req.email, req.student_number, req.department_name
    ]));
    if (!filteredRequests.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="padding: 1rem; color: #94a3b8;">No pending requests.</td></tr>';
        return;
    }
    filteredRequests.forEach(req => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escapeHtml(req.name)}</td>
            <td>${escapeHtml(req.email)}</td>
            <td>${escapeHtml(req.student_number)}</td>
            <td>${escapeHtml(req.department_name)}</td>
            <td>
                <select class="form-control users-combobox" data-field="role">
                    <option value="general" ${req.requested_role === 'general' ? 'selected' : ''}>general</option>
                    <option value="staff" ${req.requested_role === 'staff' ? 'selected' : ''}>staff</option>
                    <option value="admin" ${req.requested_role === 'admin' ? 'selected' : ''}>admin</option>
                </select>
            </td>
            <td>
                <div class="action-btns">
                    <button class="btn btn-primary btn-sm btn-approve-reg">Approve Request</button>
                    <button class="btn btn-outline btn-sm btn-reject-reg">Reject</button>
                </div>
            </td>
        `;
        tr.querySelector('.btn-approve-reg').addEventListener('click', () => approveRegistrationRequest(req.id, tr, req));
        tr.querySelector('.btn-reject-reg').addEventListener('click', () => rejectRegistrationRequest(req.id));
        tbody.appendChild(tr);
    });
}

// --- Actions ---
async function handleCreateAdminUser(formEl, config) {
    const formData = new FormData(formEl);
    const payload = Object.fromEntries(formData.entries());
    payload.role = config.role;
    payload.facilitator_enabled = config.facilitatorEnabled;
    try {
        const res = await fetch('api.php?action=add_user_admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById(config.modalId)?.classList.remove('active');
            formEl.reset();
            notify(config.successMessage);
            await loadUsersAdminData();
        } else { notify(data.message || 'Error', 'error'); }
    } catch (e) { notify('Error', 'error'); }
}

async function approveRegistrationRequest(requestId, rowEl, originalReq) {
    const role = rowEl.querySelector('[data-field="role"]')?.value || 'general';
    try {
        const res = await fetch('api.php?action=approve_registration_request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: requestId, role, ...originalReq })
        });
        if ((await res.json()).success) {
            notify('Approved');
            await loadUsersAdminData();
        }
    } catch (e) { notify('Error', 'error'); }
}

async function rejectRegistrationRequest(requestId) {
    const reason = prompt('Rejection reason:', '') || '';
    try {
        const res = await fetch('api.php?action=reject_registration_request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: requestId, reason })
        });
        if ((await res.json()).success) {
            notify('Rejected');
            await loadUsersAdminData();
        }
    } catch (e) { }
}

async function deleteAdminUser(userId, userName) {
    if (!confirm(`Delete ${userName}?`)) return;
    try {
        const res = await fetch('api.php?action=delete_user_admin', {
            method: 'POST',
            body: JSON.stringify({ id: userId })
        });
        if ((await res.json()).success) {
            notify('Deleted');
            await loadUsersAdminData();
        }
    } catch (e) { }
}

async function handleDeleteSelectedUsers() {
    if (selectedUserIds.size === 0) return;
    if (!confirm(`Delete ${selectedUserIds.size} selected users?`)) return;
    if (prompt('Type "DELETE" to confirm:') !== 'DELETE') return;
    try {
        const res = await fetch('api.php?action=delete_all_users_admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: Array.from(selectedUserIds) })
        });
        const data = await res.json();
        if (data.success) {
            notify(`Deleted ${selectedUserIds.size} users`);
            selectedUserIds.clear();
            updateUserSelectionUI();
            await loadUsersAdminData();
        } else {
            notify(data.message || 'Deletion failed', 'error');
        }
    } catch (e) {
        notify('Deletion request failed', 'error');
    }
}

function updateUserSelectionUI() {
    const btn = document.getElementById('delete-selected-users-btn');
    const countSpan = document.getElementById('selected-user-count');
    const selectAllCheckbox = document.getElementById('select-all-users');

    if (btn && countSpan) {
        if (selectedUserIds.size > 0) {
            btn.style.display = 'inline-block';
            countSpan.textContent = selectedUserIds.size;
        } else {
            btn.style.display = 'none';
        }
    }

    if (selectAllCheckbox) {
        const totalVisibleCheckboxes = document.querySelectorAll('.user-checkbox').length;
        if (totalVisibleCheckboxes > 0) {
            selectAllCheckbox.checked = (selectedUserIds.size >= totalVisibleCheckboxes);
        } else {
            selectAllCheckbox.checked = false;
        }
    }
}

async function handleExportUsersCsv() { window.location.href = 'api.php?action=export_users_csv'; }

async function handleImportUsersCsv() {
    const input = document.createElement('input');
    input.type = 'file'; input.accept = '.csv';
    input.onchange = async (e) => {
        const fd = new FormData(); fd.append('csv_file', e.target.files[0]);
        try {
            const res = await fetch('api.php?action=import_users_csv', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) { notify('Imported'); await loadUsersAdminData(); }
            else { notify(data.message || 'Import failed', 'error'); }
        } catch (e) { notify('Import failed', 'error'); }
    };
    input.click();
}

// --- Edit User Modal ---
async function openEditUserModal(user) {
    const modal = document.getElementById('edit-user-modal');
    const form = document.getElementById('edit-user-form');
    if (!modal || !form) return;
    form.reset();
    document.getElementById('edit-user-id').value = user.id;
    document.getElementById('edit-user-name').value = user.name || '';
    document.getElementById('edit-user-email').value = user.email || '';
    document.getElementById('edit-user-role').value = normalizeRole(user.role);
    document.getElementById('edit-user-student-number').value = user.student_number || '';
    document.getElementById('edit-user-type').value = user.user_type || 'non-student';
    document.getElementById('edit-user-department').value = user.department_id || '';
    document.getElementById('edit-user-year-level').value = user.year_level || '';
    document.getElementById('edit-user-status').value = user.enrollment_status || '';


    // Populate facilitator link dropdown
    const facSelect = document.getElementById('edit-user-facilitator');
    if (facSelect) {
        facSelect.innerHTML = '<option value="">None (no facilitator privileges)</option>';
        try {
            const res = await fetch('api.php?action=get_facilitators');
            const data = await res.json();
            if (data.success) {
                data.facilitators.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.id;
                    opt.textContent = f.name;
                    if (String(f.id) === String(user.facilitator_id)) opt.selected = true;
                    facSelect.appendChild(opt);
                });
            }
        } catch (e) { }
    }

    const deptSelect = document.getElementById('edit-user-department');
    const progSelect = document.getElementById('edit-user-program');
    const typeSelect = document.getElementById('edit-user-type');

    const updateProgs = async () => {
        const isStudent = typeSelect.value === 'student';
        const deptName = deptSelect.options[deptSelect.selectedIndex]?.text || '';
        const isBasicEd = ["Grade School", "Junior High School", "Preschool"].some(d => deptName.toLowerCase().includes(d.toLowerCase()));
        const programGroup = document.getElementById('edit-user-program-group');
        const yearGroup = document.getElementById('edit-user-year-level-group');
        const statusGroup = document.getElementById('edit-user-status-group');

        if (programGroup) {
            programGroup.style.display = isBasicEd ? 'none' : 'block';
            if (isBasicEd) progSelect.value = '';
        }
        if (yearGroup) {
            yearGroup.style.display = isBasicEd ? 'none' : 'block';
            if (isBasicEd) document.getElementById('edit-user-year-level').value = '';
        }
        if (statusGroup) {
            statusGroup.style.display = isBasicEd ? 'none' : 'block';
            if (isBasicEd) document.getElementById('edit-user-status').value = '';
        }

        progSelect.innerHTML = '<option value="">N/A</option>';
        if (!deptSelect.value || !isStudent) return;
        try {
            const res = await fetch(`api.php?action=get_programs&department_id=${deptSelect.value}`);
            const data = await res.json();
            if (data.success) {
                data.programs.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id; opt.textContent = p.name;
                    if (String(p.id) === String(user.course_program)) opt.selected = true;
                    progSelect.appendChild(opt);
                });
            }
        } catch (e) { }
    };
    deptSelect.onchange = updateProgs;
    typeSelect.onchange = updateProgs;
    updateProgs();
    modal.classList.add('active');
}

document.getElementById('edit-user-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = Object.fromEntries(new FormData(e.currentTarget).entries());
    const pass = document.getElementById('edit-user-password').value;
    if (pass) payload.password = pass;
    try {
        const res = await fetch('api.php?action=update_user_admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        if ((await res.json()).success) {
            notify('Updated');
            document.getElementById('edit-user-modal').classList.remove('active');
            await loadUsersAdminData();
        }
    } catch (e) { }
});

// --- Other Handlers ---
async function loadRequests() {
    const grid = document.getElementById('requests-grid');
    if (!grid) return;

    // Build filter query string
    const filters = {
        requestor: document.getElementById('filter-requestor')?.value || 'all',
        college: document.getElementById('filter-college')?.value || 'all',
        facilitator: document.getElementById('filter-facilitator')?.value || 'all',
        status: document.getElementById('filter-status')?.value || 'all',
        datetime: document.getElementById('filter-datetime')?.value || 'newest',
        date: document.getElementById('filter-date')?.value || '',
        include_archived: adminAppointmentsSubview === 'archived'
    };
    const params = new URLSearchParams(filters);

    try {
        const res = await fetch('api.php?action=get_appointments&' + params.toString());
        const data = await res.json();
        if (data.success) {
            requestAppointmentsCache = data.appointments;
            const summary = document.getElementById('requests-summary');
            if (summary) {
                summary.textContent = `Found ${data.appointments.length} appointment record${data.appointments.length !== 1 ? 's' : ''}`;
            }
            grid.innerHTML = data.appointments.map((app, index) => {
                const startTime = new Date(app.date_time);
                const endTime = app.end_time ? new Date(app.end_time) : null;
                const timeStr = endTime
                    ? `${startTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} - ${endTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`
                    : startTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                const notes = (app.special_requests || '').trim();

                const isArchived = !!app.archived_at;
                const statusBadgeHTML = isArchived
                    ? `<div class="app-card-status ${app.booking_status.toLowerCase()}">${app.booking_status} <span class="archived-tag">(Archived)</span></div>`
                    : `<div class="app-card-status ${app.booking_status.toLowerCase()}">${app.booking_status}</div>`;

                return `
                    <div class="app-card" onclick="openAppointmentDetails(${index})">
                        <div class="app-card-header" style="display: flex; align-items: center; gap: 0.5rem; justify-content: flex-start;">
                            <input type="checkbox" class="session-checkbox" value="${app.session_id}" onclick="event.stopPropagation();">
                            <div class="app-card-type" style="margin-right: auto;">${app.appointment_type}</div>
                            ${statusBadgeHTML}
                        </div>
                        <div class="app-card-topic" title="${app.topic}">${app.topic}</div>
                        
                        <div class="app-card-info">
                            <div class="info-item">
                                <strong>Requestor</strong>
                                <span class="info-value">${app.student_name}</span>
                            </div>
                            <div class="info-item">
                                <strong>Department</strong>
                                <span class="info-value">${app.student_department || 'N/A'}</span>
                            </div>
                            <div class="info-item">
                                <strong>Instructor</strong>
                                <span class="info-value">${app.facilitator_name || 'TBA'}</span>
                            </div>
                            <div class="info-item">
                                <strong>Schedule</strong>
                                <span class="info-value">${startTime.toLocaleDateString()} (${timeStr})</span>
                            </div>
                            <div class="info-item">
                                <strong>Venue</strong>
                                <span class="info-value">${app.venue || 'TBA'} (${app.mode || 'Onsite'})</span>
                            </div>
                            <div class="info-item">
                                <strong>Notes</strong>
                                <span class="info-value">${notes ? `"${notes}"` : '<span style="color:#94a3b8; font-style:italic;">None</span>'}</span>
                            </div>
                        </div>

                        <div class="app-card-actions">
                            <button class="btn btn-primary btn-sm" style="width: 100%;" onclick="event.stopPropagation(); openAppointmentDetails(${index})">Manage Appointment</button>
                        </div>
                    </div>
                `;
            }).join('');

            // Bind checkbox events
            document.querySelectorAll('.session-checkbox').forEach(cb => {
                cb.checked = selectedSessionIds.has(cb.value);
                cb.addEventListener('change', (e) => {
                    if (e.target.checked) selectedSessionIds.add(cb.value);
                    else selectedSessionIds.delete(cb.value);
                    updateSessionSelectionUI();
                });
            });
            updateSessionSelectionUI();
        }
    } catch (error) {
        console.error('Failed to load appointments:', error);
        grid.innerHTML = '<p class="text-center text-muted">Failed to load appointments. Please try again.</p>';
    }

    // Bind filter handlers (prevent double-binding)
    if (!window.requestFilterHandlersBound) {
        ['filter-requestor', 'filter-college', 'filter-facilitator', 'filter-status', 'filter-datetime', 'filter-date'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => loadRequests());
        });
        document.getElementById('reset-request-filters')?.addEventListener('click', () => {
            ['filter-requestor', 'filter-college', 'filter-facilitator', 'filter-status', 'filter-date'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = (id === 'filter-date' ? '' : 'all');
            });
            const dt = document.getElementById('filter-datetime');
            if (dt) dt.value = 'newest';
            loadRequests();
        });
        document.getElementById('filter-date-today')?.addEventListener('click', () => {
            const today = new Date().toISOString().split('T')[0];
            const el = document.getElementById('filter-date');
            if (el) el.value = today;
            const dt = document.getElementById('filter-datetime');
            if (dt) dt.value = 'newest';
            loadRequests();
        });
        window.requestFilterHandlersBound = true;
    }
}

function handleSelectAllSessions(e) {
    const checkboxes = document.querySelectorAll('.session-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = e.target.checked;
        if (e.target.checked) selectedSessionIds.add(cb.value);
        else selectedSessionIds.delete(cb.value);
    });
    updateSessionSelectionUI();
}

function updateSessionSelectionUI() {
    const btn = document.getElementById('archive-selected-sessions-btn');
    const countSpan = document.getElementById('selected-session-count');
    const selectAllContainer = document.getElementById('select-all-sessions-container');
    const selectAllCheckbox = document.getElementById('select-all-sessions');
    const totalVisible = document.querySelectorAll('.session-checkbox').length;

    if (totalVisible > 0) {
        if (selectAllContainer) selectAllContainer.style.display = 'inline-flex';
    } else {
        if (selectAllContainer) selectAllContainer.style.display = 'none';
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.checked = (totalVisible > 0 && selectedSessionIds.size >= totalVisible);
    }

    if (btn && countSpan) {
        if (selectedSessionIds.size > 0) {
            btn.style.display = 'inline-block';
            countSpan.textContent = selectedSessionIds.size;
        } else {
            btn.style.display = 'none';
        }
    }
}

async function handleArchiveSelectedSessions() {
    if (selectedSessionIds.size === 0) return;

    const isUnarchiving = adminAppointmentsSubview === 'archived';
    const actionText = isUnarchiving ? 'Unarchive' : 'Archive';
    const promptText = isUnarchiving
        ? `Unarchive ${selectedSessionIds.size} selected sessions? They will be restored to the active view.`
        : `Archive ${selectedSessionIds.size} selected sessions? They will be hidden from the default view.`;

    if (!confirm(promptText)) return;

    try {
        const actionEndpoint = isUnarchiving ? 'bulk_unarchive_sessions' : 'bulk_archive_sessions';
        const res = await fetch(`api.php?action=${actionEndpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: Array.from(selectedSessionIds) })
        });
        const data = await res.json();
        if (data.success) {
            notify(`${actionText}d ${selectedSessionIds.size} sessions`);
            selectedSessionIds.clear();
            updateSessionSelectionUI();
            await loadRequests();
        } else {
            notify(data.message || `${actionText} failed`, 'error');
        }
    } catch (e) {
        notify(`${actionText} request failed`, 'error');
    }
}


async function loadFilterOptions() {
    try {
        const [deptsRes, facsRes, usersRes] = await Promise.all([
            fetch('api.php?action=get_departments'),
            fetch('api.php?action=get_facilitators'),
            fetch('api.php?action=get_users_admin')
        ]);

        const deptsData = await deptsRes.json();
        const facsData = await facsRes.json();
        const usersData = await usersRes.json();

        if (deptsData.success) {
            const select = document.getElementById('filter-college');
            if (select) {
                deptsData.departments.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.name;
                    select.appendChild(opt);
                });
            }
        }

        if (facsData.success) {
            const select = document.getElementById('filter-facilitator');
            if (select) {
                facsData.facilitators.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.id;
                    opt.textContent = f.name;
                    select.appendChild(opt);
                });
            }
        }

        if (usersData.success) {
            const select = document.getElementById('filter-requestor');
            if (select) {
                // Only show users who have names
                const requestors = usersData.users.filter(u => u.name);
                requestors.forEach(u => {
                    const opt = document.createElement('option');
                    opt.value = u.name;
                    opt.textContent = u.name;
                    select.appendChild(opt);
                });
            }
        }
    } catch (e) {
        console.error('Failed to load filter options:', e);
    }
}

async function loadSeminars() {
    const tbody = document.getElementById('seminars-tbody');
    if (tbody) {
        try {
            const res = await fetch('api.php?action=get_seminars');
            const data = await res.json();
            if (data.success) {
                tbody.innerHTML = data.seminars.map(s => {
                    const speakerDisplay = s.facilitator_name || s.speaker || 'N/A';
                    const facIdArg = s.facilitator_id ? s.facilitator_id : 'null';
                    return `
                    <tr>
                        <td>${s.title}</td>
                        <td>${speakerDisplay}${s.facilitator_name ? ' <span style="font-size:0.72rem;color:#6366f1;font-weight:600;">(Facilitator)</span>' : ''}</td>
                        <td>${new Date(s.date_time).toLocaleString()}</td>
                        <td>${s.venue || 'N/A'}</td>
                        <td>
                            <div class="action-btns">
                                <button class="btn btn-outline btn-sm" onclick="editSeminar(${s.id}, '${s.title.replace(/'/g, "\\'")}', '${(s.speaker || '').replace(/'/g, "\\'")}', '${s.date_time}', '${(s.venue || '').replace(/'/g, "\\'")}', ${facIdArg})">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteSeminar(${s.id})">Delete</button>
                            </div>
                        </td>
                    </tr>
                `}).join('');
            }
        } catch (e) {
            console.error('Failed to load seminars:', e);
        }
    }
}


async function loadFacilitators(searchTerm = '') {
    const tbody = document.getElementById('facilitators-tbody');
    if (!tbody) return;
    try {
        const res = await fetch('api.php?action=get_facilitators');
        const data = await res.json();
        if (data.success) {
            const search = searchTerm.toLowerCase();
            const filtered = data.facilitators.filter(f =>
                f.name.toLowerCase().includes(search) ||
                (f.departments || '').toLowerCase().includes(search) ||
                (f.position || '').toLowerCase().includes(search)
            );

            tbody.innerHTML = filtered.map(f => `
                <tr>
                    <td>${f.name}</td>
                    <td>${f.departments || 'N/A'}</td>
                    <td>${f.position || 'N/A'}</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-outline btn-sm" onclick="openFacEdit(${f.id}, '${f.name.replace(/'/g, "\\'")}', '${(f.position || '').replace(/'/g, "\\'")}', '${f.topic_ids || ''}', '${f.department_ids || ''}')">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteFacilitator(${f.id})">Delete</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
    } catch (e) {
        console.error('Failed to load facilitators:', e);
    }
}

async function loadTopicCatalog(searchTerm = '') {
    const tbody = document.getElementById('topics-tbody');
    if (!tbody) return;
    try {
        const res = await fetch('api.php?action=get_topic_catalog');
        const data = await res.json();
        if (data.success) {
            const search = searchTerm.toLowerCase();
            const filtered = data.topics.filter(t =>
                t.name.toLowerCase().includes(search) ||
                (t.departments || '').toLowerCase().includes(search)
            );

            tbody.innerHTML = filtered.map(t => `
                <tr>
                    <td>${t.name}</td>
                    <td>${t.departments || 'N/A'}</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-outline btn-sm" onclick="openTopicEdit(${t.id}, '${t.name.replace(/'/g, "\\'")}', '${t.department_ids || ''}', '${t.facilitator_ids || ''}')">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteTopic(${t.id})">Delete</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
    } catch (e) {
        console.error('Failed to load topics:', e);
    }
}

function initExportLogs() {
    document.getElementById('export-logs-btn')?.addEventListener('click', () => window.location.href = 'api.php?action=export_session_logs_csv');
}

function initExportSessions() {
    document.getElementById('export-sessions-btn')?.addEventListener('click', () => window.location.href = 'api.php?action=export_sessions_csv');
}

// Stubs for complex features
let currentAdminCalendarDate = new Date();
let adminOffDays = [];
let adminCalendarAppointments = [];

async function loadAdminCalendarContext() {
    try {
        const [offDaysRes, appsRes] = await Promise.all([
            fetch('api.php?action=get_off_days'),
            fetch('api.php?action=get_appointments&status=all')
        ]);

        const offDaysData = await offDaysRes.json();
        const appsData = await appsRes.json();

        if (offDaysData.success) adminOffDays = offDaysData.off_days;
        if (appsData.success) adminCalendarAppointments = appsData.appointments;

        renderAdminCalendar();
    } catch (e) {
        console.error('Failed to load calendar context:', e);
    }
}

function initAdminCalendar() {
    document.getElementById('admin-calendar-prev')?.addEventListener('click', () => {
        currentAdminCalendarDate.setMonth(currentAdminCalendarDate.getMonth() - 1);
        renderAdminCalendar();
    });
    document.getElementById('admin-calendar-next')?.addEventListener('click', () => {
        currentAdminCalendarDate.setMonth(currentAdminCalendarDate.getMonth() + 1);
        renderAdminCalendar();
    });
    document.getElementById('admin-calendar-today')?.addEventListener('click', () => {
        currentAdminCalendarDate = new Date();
        renderAdminCalendar();
    });
    loadAdminCalendarContext();
}

function renderAdminCalendar() {
    const grid = document.getElementById('admin-calendar-grid');
    const monthYear = document.getElementById('admin-calendar-month-year');
    if (!grid || !monthYear) return;

    const year = currentAdminCalendarDate.getFullYear();
    const month = currentAdminCalendarDate.getMonth();

    monthYear.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentAdminCalendarDate);

    grid.innerHTML = '';

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Empty cells for padding
    for (let i = 0; i < firstDay; i++) {
        grid.appendChild(document.createElement('div'));
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const cell = document.createElement('div');
        cell.className = 'calendar-day';

        const dayApps = adminCalendarAppointments.filter(app => app.date_time.startsWith(dateStr));
        const offDay = adminOffDays.find(od => od.date === dateStr);

        if (offDay) cell.classList.add('off-day');
        if (new Date().toISOString().split('T')[0] === dateStr) cell.classList.add('today');

        cell.innerHTML = `
            <div class="day-num">${day}</div>
            <div class="day-content">
                ${offDay ? `<div class="off-day-label">${offDay.description}</div>` : ''}
                ${dayApps.map(app => `
                    <div class="calendar-app-tag ${app.booking_status.toLowerCase()}" title="${app.topic}">${app.appointment_type}</div>
                `).join('')}
            </div>
        `;

        cell.onclick = () => openDayOptionsModal(dateStr, offDay);
        grid.appendChild(cell);
    }
}

async function openDayOptionsModal(date, existingOffDay) {
    const actionModal = document.getElementById('admin-calendar-action-modal');
    const offDayModal = document.getElementById('admin-offday-modal');
    if (!actionModal || !offDayModal) return;

    const actionDateEl = document.getElementById('admin-calendar-action-date');
    const actionOffDayEl = document.getElementById('admin-calendar-action-offday');
    const removeBtn = document.getElementById('admin-calendar-action-remove-offday-btn');

    if (actionDateEl) actionDateEl.textContent = `Selection: ${date}`;

    if (existingOffDay) {
        if (actionOffDayEl) {
            actionOffDayEl.style.display = 'block';
            actionOffDayEl.textContent = `Off-Day: ${existingOffDay.description}`;
        }
        if (removeBtn) removeBtn.style.display = 'block';
    } else {
        if (actionOffDayEl) actionOffDayEl.style.display = 'none';
        if (removeBtn) removeBtn.style.display = 'none';
    }

    // Set up action modal button listeners
    const bookBtn = document.getElementById('admin-calendar-action-book');
    const offDayBtn = document.getElementById('admin-calendar-action-offday-btn');
    const closeBtn = document.getElementById('admin-calendar-action-cancel');
    const closeTop = document.getElementById('admin-calendar-action-close');

    const cleanup = () => {
        actionModal.classList.remove('active');
        bookBtn?.removeEventListener('click', onBook);
        offDayBtn?.removeEventListener('click', onOffDay);
        removeBtn?.removeEventListener('click', onRemove);
        closeBtn?.removeEventListener('click', cleanup);
        closeTop?.removeEventListener('click', cleanup);
    };

    const onBook = () => {
        cleanup();
        if (typeof openAdminBookingModal === 'function') {
            openAdminBookingModal(date);
        } else {
            notify('Booking feature coming soon', 'info');
        }
    };

    const onOffDay = () => {
        cleanup();
        openOffDayEditor(date, existingOffDay);
    };

    const onRemove = async () => {
        if (confirm(`Remove off-day for ${date}?`)) {
            cleanup();
            try {
                const res = await fetch('api.php?action=delete_off_day', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ date })
                });
                if ((await res.json()).success) {
                    notify('Off-day removed');
                    loadAdminCalendarContext();
                }
            } catch (e) { notify('Error', 'error'); }
        }
    };

    bookBtn?.addEventListener('click', onBook);
    offDayBtn?.addEventListener('click', onOffDay);
    removeBtn?.addEventListener('click', onRemove);
    closeBtn?.addEventListener('click', cleanup);
    closeTop?.addEventListener('click', cleanup);

    actionModal.classList.add('active');
}

function openOffDayEditor(date, existingOffDay) {
    const modal = document.getElementById('admin-offday-modal');
    const dateEl = document.getElementById('admin-offday-date');
    const descInput = document.getElementById('admin-offday-description');
    const saveBtn = document.getElementById('admin-offday-save');
    const deleteBtn = document.getElementById('admin-offday-delete');
    const cancelBtn = document.getElementById('admin-offday-cancel');
    const closeTop = document.getElementById('admin-offday-close');

    if (dateEl) dateEl.textContent = `Date: ${date}`;
    if (descInput) descInput.value = existingOffDay ? existingOffDay.description : '';
    if (deleteBtn) deleteBtn.style.display = existingOffDay ? 'block' : 'none';

    const cleanup = () => {
        modal.classList.remove('active');
        saveBtn?.removeEventListener('click', onSave);
        deleteBtn?.removeEventListener('click', onDelete);
        cancelBtn?.removeEventListener('click', cleanup);
        closeTop?.removeEventListener('click', cleanup);
    };

    const onSave = async () => {
        const description = descInput.value.trim();
        if (!description) {
            notify('Please enter a description', 'error');
            return;
        }
        cleanup();
        try {
            const res = await fetch('api.php?action=save_off_day', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ date, description })
            });
            if ((await res.json()).success) {
                notify('Off-day saved');
                loadAdminCalendarContext();
            }
        } catch (e) { notify('Error', 'error'); }
    };

    const onDelete = async () => {
        if (confirm('Clear this off-day?')) {
            cleanup();
            try {
                const res = await fetch('api.php?action=delete_off_day', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ date })
                });
                if ((await res.json()).success) {
                    notify('Off-day removed');
                    loadAdminCalendarContext();
                }
            } catch (e) { notify('Error', 'error'); }
        }
    };

    saveBtn?.addEventListener('click', onSave);
    deleteBtn?.addEventListener('click', onDelete);
    cancelBtn?.addEventListener('click', cleanup);
    closeTop?.addEventListener('click', cleanup);

    modal.classList.add('active');
}


function openAdminBookingModal(date) {
    const modal = document.getElementById('advanced-booking-modal');
    const dateDisplay = document.getElementById('booking-date-display');
    if (!modal) return;

    if (dateDisplay) dateDisplay.textContent = date;

    // In admin context, we might want to pre-fill or adjust the form
    // For now, just show it.
    modal.classList.add('active');
}

window.closeAdvancedBooking = function () {
    const modal = document.getElementById('advanced-booking-modal');
    if (modal) modal.classList.remove('active');
};

function initAdminBookingModal() {
    // Global close for the booking modal if it has a backdrop
    const modal = document.getElementById('advanced-booking-modal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeAdvancedBooking();
        });
    }
}


// Action Handlers for Topics and Facilitators
window.openTopicModal = function () {
    const modal = document.getElementById('admin-topic-modal');
    if (!modal) return;
    document.getElementById('topic-modal-title').textContent = 'Register New Topic';
    document.getElementById('topic-submit-btn').textContent = 'Save Topic';
    document.getElementById('topic-id').value = '';
    const form = document.getElementById('admin-topic-form');
    if (form) form.reset();

    if (typeof refreshTopicDepartmentChecklist === 'function') refreshTopicDepartmentChecklist();
    if (typeof refreshTopicFacilitatorTable === 'function') refreshTopicFacilitatorTable();

    modal.classList.add('active');
};

window.openFacilitatorModal = function () {
    const modal = document.getElementById('admin-facilitator-modal');
    if (!modal) return;
    document.getElementById('fac-modal-title').textContent = 'Register New Faculty Instructor';
    document.getElementById('fac-submit-btn').textContent = 'Save Faculty Profile';
    document.getElementById('fac-id').value = '';
    const form = document.getElementById('admin-fac-form');
    if (form) form.reset();

    if (typeof resetFacilitatorPickerState === 'function') resetFacilitatorPickerState();

    modal.classList.add('active');
};

window.deleteTopic = async function (id) {
    if (confirm('Are you sure you want to delete this topic?')) {
        try {
            const res = await fetch('api.php?action=delete_topic', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            if ((await res.json()).success) {
                notify('Topic deleted');
                loadTopicCatalog();
            }
        } catch (e) {
            notify('Failed to delete topic', 'error');
        }
    }
};

window.deleteFacilitator = async function (id) {
    if (confirm('Are you sure you want to delete this instructor?')) {
        try {
            const res = await fetch('api.php?action=delete_facilitator', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            if ((await res.json()).success) {
                notify('Facilitator deleted');
                loadFacilitators();
            }
        } catch (e) {
            notify('Failed to delete facilitator', 'error');
        }
    }
};

// Seminar Handlers
window.openSeminarModal = function () {
    const modal = document.getElementById('admin-seminar-modal');
    if (!modal) return;

    document.getElementById('seminar-modal-title').textContent = 'Create New Seminar';
    document.getElementById('seminar-submit-btn').textContent = 'Create Seminar';
    document.getElementById('seminar-id').value = '';
    const form = document.getElementById('admin-seminar-form');
    if (form) form.reset();

    if (typeof loadSeminarFacilitators === 'function') loadSeminarFacilitators();
    modal.classList.add('active');
};

window.editSeminar = async function (id, title, speaker, dateTime, venue, facilitatorId) {
    const modal = document.getElementById('admin-seminar-modal');
    if (!modal) return;

    document.getElementById('seminar-modal-title').textContent = 'Edit Seminar Event';
    document.getElementById('seminar-submit-btn').textContent = 'Update Seminar';
    document.getElementById('seminar-id').value = id;

    document.getElementById('seminar-title').value = title;
    document.getElementById('seminar-datetime').value = dateTime.replace(' ', 'T').substring(0, 16);
    document.getElementById('seminar-venue').value = venue || '';

    if (typeof loadSeminarFacilitators === 'function') {
        await loadSeminarFacilitators(facilitatorId);
    }

    // If no facilitator is linked, populate the guest speaker field
    const facSel = document.getElementById('seminar-facilitator-select');
    if (!facilitatorId || !facSel?.value) {
        document.getElementById('seminar-speaker').value = speaker || '';
    }

    modal.classList.add('active');
};

window.deleteSeminar = async function (id) {
    if (confirm('Are you sure you want to delete this seminar?')) {
        try {
            const res = await fetch('api.php?action=delete_seminar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            if ((await res.json()).success) {
                notify('Seminar deleted');
                loadSeminars();
            }
        } catch (e) {
            notify('Failed to delete seminar', 'error');
        }
    }
};
window.openAppointmentDetails = function (index) {
    const app = requestAppointmentsCache[index];
    if (!app) return;

    const summaryData = {
        name: app.student_name || '',
        email: app.student_email || '',
        topic: app.topic || '',
        type: app.appointment_type || '',
        mode: app.mode || '',
        date: new Date(app.date_time).toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' }),
        time: `${new Date(app.date_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} - ${app.end_time ? new Date(app.end_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'TBA'}`,
        archived_at: app.archived_at
    };

    if (typeof editAppointment === 'function') {
        editAppointment(
            app.session_id,
            app.booking_status,
            app.venue,
            app.facilitator_id,
            app.cancelled_date_time,
            app.cancelled_by,
            app.cancellation_reason,
            summaryData
        );
    } else {
        console.error('editAppointment function not found');
    }
};
