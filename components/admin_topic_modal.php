<div class="modal-overlay admin-modal" id="admin-topic-modal">
    <div class="modal-content admin-modal-card admin-modal-lg">
        <div class="modal-header">
            <h3 id="topic-modal-title">Register New Topic</h3>
            <button class="btn-close" onclick="this.closest('.modal-overlay').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="admin-topic-form" onsubmit="handleTopicSubmit(event)">
                <input type="hidden" id="topic-id" value="">

                <div class="form-group">
                    <label>Topic Name</label>
                    <input type="text" id="topic-name" class="form-control" placeholder="e.g. Information Literacy" required>
                </div>

                <div class="form-group">
                    <label>Departments Covered</label>
                    <div class="combo-check" style="margin-top: 0.5rem; margin-bottom: 0.75rem;">
                        <button type="button" id="topic-dept-combo-btn" class="form-control combo-check-btn">Select departments...</button>
                        <div id="topic-dept-combo-panel" class="combo-check-panel"></div>
                    </div>
                    <div class="modal-table-scroll">
                        <table class="admin-table" style="font-size: 0.82rem;">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="topic-depts-selected-table">
                                <tr><td colspan="2" style="padding: 0.6rem 0.8rem; color: #94a3b8;">No departments selected.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="form-group">
                    <label>Managing Facilitators</label>
                    <div class="combo-check" style="margin-top: 0.5rem; margin-bottom: 0.75rem;">
                        <button type="button" id="topic-facilitator-combo-btn" class="form-control combo-check-btn">Select facilitators...</button>
                        <div id="topic-facilitator-combo-panel" class="combo-check-panel"></div>
                    </div>
                    <div class="modal-table-scroll">
                        <table class="admin-table" style="font-size: 0.82rem;">
                            <thead>
                                <tr>
                                    <th>Facilitator</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="topic-facilitators-selected-table">
                                <tr><td colspan="2" style="padding: 0.6rem 0.8rem; color: #94a3b8;">No facilitators selected.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="admin-modal-footer">
                    <button type="submit" id="topic-submit-btn" class="btn btn-primary" style="flex: 1;">Save Topic</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let topicModalDepartments = [];
let topicModalFacilitators = [];
let selectedTopicDeptIds = new Set();
let selectedTopicFacilitatorIds = new Set();

function closeTopicComboPanels() {
    const panels = ['topic-dept-combo-panel', 'topic-facilitator-combo-panel'];
    panels.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}

function bindTopicComboToggle(buttonId, panelId) {
    const btn = document.getElementById(buttonId);
    const panel = document.getElementById(panelId);
    if (!btn || !panel) return;

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = panel.style.display === 'block';
        closeTopicComboPanels();
        panel.style.display = isOpen ? 'none' : 'block';
    });

    panel.addEventListener('click', (e) => e.stopPropagation());
}

function updateTopicComboButtonLabels() {
    const deptBtn = document.getElementById('topic-dept-combo-btn');
    const facBtn = document.getElementById('topic-facilitator-combo-btn');

    if (deptBtn) {
        deptBtn.textContent = selectedTopicDeptIds.size > 0
            ? `${selectedTopicDeptIds.size} department${selectedTopicDeptIds.size > 1 ? 's' : ''} selected`
            : 'Select departments...';
    }

    if (facBtn) {
        facBtn.textContent = selectedTopicFacilitatorIds.size > 0
            ? `${selectedTopicFacilitatorIds.size} facilitator${selectedTopicFacilitatorIds.size > 1 ? 's' : ''} selected`
            : 'Select facilitators...';
    }
}

async function refreshTopicDepartmentChecklist() {
    try {
        const res = await fetch('api.php?action=get_departments');
        const data = await res.json();
        if (data.success) {
            topicModalDepartments = data.departments;
            renderTopicDepartmentChecklist();
            renderTopicDepartmentTable();
        }
    } catch (e) {}
}

async function refreshTopicFacilitatorTable() {
    try {
        const res = await fetch('api.php?action=get_facilitators');
        const data = await res.json();
        if (data.success) {
            topicModalFacilitators = data.facilitators;
            renderTopicFacilitatorChecklist();
            renderTopicFacilitatorTable();
        }
    } catch (e) {}
}

function renderTopicDepartmentChecklist() {
    const panel = document.getElementById('topic-dept-combo-panel');
    if (!panel) return;

    panel.innerHTML = '';
    if (!topicModalDepartments.length) {
        panel.innerHTML = '<div class="combo-check-empty">No departments available.</div>';
        updateTopicComboButtonLabels();
        return;
    }

    topicModalDepartments.forEach(d => {
        const row = document.createElement('label');
        row.className = 'combo-check-option';
        row.innerHTML = `<input type="checkbox" value="${d.id}" ${selectedTopicDeptIds.has(String(d.id)) ? 'checked' : ''}> <span>${d.name}</span>`;

        const checkbox = row.querySelector('input');
        checkbox.addEventListener('change', () => {
            if (checkbox.checked) selectedTopicDeptIds.add(String(d.id));
            else selectedTopicDeptIds.delete(String(d.id));
            renderTopicDepartmentTable();
            updateTopicComboButtonLabels();
        });

        panel.appendChild(row);
    });

    updateTopicComboButtonLabels();
}

function renderTopicFacilitatorChecklist() {
    const panel = document.getElementById('topic-facilitator-combo-panel');
    if (!panel) return;

    panel.innerHTML = '';
    if (!topicModalFacilitators.length) {
        panel.innerHTML = '<div class="combo-check-empty">No facilitators available.</div>';
        updateTopicComboButtonLabels();
        return;
    }

    topicModalFacilitators.forEach(f => {
        const row = document.createElement('label');
        row.className = 'combo-check-option';
        row.innerHTML = `<input type="checkbox" value="${f.id}" ${selectedTopicFacilitatorIds.has(String(f.id)) ? 'checked' : ''}> <span>${f.name}</span>`;

        const checkbox = row.querySelector('input');
        checkbox.addEventListener('change', () => {
            if (checkbox.checked) selectedTopicFacilitatorIds.add(String(f.id));
            else selectedTopicFacilitatorIds.delete(String(f.id));
            renderTopicFacilitatorTable();
            updateTopicComboButtonLabels();
        });

        panel.appendChild(row);
    });

    updateTopicComboButtonLabels();
}

function renderTopicDepartmentTable() {
    const tbody = document.getElementById('topic-depts-selected-table');
    if (!tbody) return;
    tbody.innerHTML = '';
    const selected = topicModalDepartments.filter(d => selectedTopicDeptIds.has(String(d.id)));
    if (!selected.length) {
        tbody.innerHTML = '<tr><td colspan="2" style="padding: 0.6rem 0.8rem; color: #94a3b8;">No departments selected.</td></tr>';
        return;
    }
    selected.forEach(d => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${d.name}</td><td style="text-align:right;"><button type="button" class="btn btn-outline btn-sm" style="padding:0.15rem 0.5rem;">Remove</button></td>`;
        tr.querySelector('button').addEventListener('click', () => {
            selectedTopicDeptIds.delete(String(d.id));
            renderTopicDepartmentChecklist();
            renderTopicDepartmentTable();
        });
        tbody.appendChild(tr);
    });
}

function renderTopicFacilitatorTable() {
    const tbody = document.getElementById('topic-facilitators-selected-table');
    if (!tbody) return;
    tbody.innerHTML = '';
    const selected = topicModalFacilitators.filter(f => selectedTopicFacilitatorIds.has(String(f.id)));
    if (!selected.length) {
        tbody.innerHTML = '<tr><td colspan="2" style="padding: 0.6rem 0.8rem; color: #94a3b8;">No facilitators selected.</td></tr>';
        return;
    }
    selected.forEach(f => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${f.name}</td><td style="text-align:right;"><button type="button" class="btn btn-outline btn-sm" style="padding:0.15rem 0.5rem;">Remove</button></td>`;
        tr.querySelector('button').addEventListener('click', () => {
            selectedTopicFacilitatorIds.delete(String(f.id));
            renderTopicFacilitatorChecklist();
            renderTopicFacilitatorTable();
        });
        tbody.appendChild(tr);
    });
}

async function handleTopicSubmit(e) {
    e.preventDefault();

    const id = document.getElementById('topic-id').value;
    const payload = {
        name: document.getElementById('topic-name').value,
        department_ids: Array.from(selectedTopicDeptIds),
        facilitator_ids: Array.from(selectedTopicFacilitatorIds)
    };

    if (id) payload.id = id;

    const action = id ? 'update_topic' : 'add_topic';

    try {
        const res = await fetch(`api.php?action=${action}`, {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('admin-topic-modal').classList.remove('active');
            document.getElementById('admin-topic-form').reset();
            document.getElementById('topic-id').value = '';
            selectedTopicDeptIds = new Set();
            selectedTopicFacilitatorIds = new Set();
            renderTopicDepartmentChecklist();
            renderTopicFacilitatorChecklist();
            renderTopicDepartmentTable();
            renderTopicFacilitatorTable();
            if (typeof loadTopicCatalog === 'function') loadTopicCatalog();
            if (typeof refreshTopicChecklist === 'function') refreshTopicChecklist();
            if (typeof loadFacilitators === 'function') loadFacilitators();
        }
    } catch (e) {}
}

function openTopicEdit(id, name, departmentIdsPiped, facilitatorIdsPiped) {
    document.getElementById('topic-modal-title').textContent = 'Update Topic';
    document.getElementById('topic-submit-btn').textContent = 'Confirm Updates';
    document.getElementById('topic-id').value = id;
    document.getElementById('topic-name').value = name || '';

    selectedTopicDeptIds = new Set((departmentIdsPiped || '').toString().split(',').map(v => v.trim()).filter(Boolean));
    selectedTopicFacilitatorIds = new Set((facilitatorIdsPiped || '').toString().split(',').map(v => v.trim()).filter(Boolean));
    renderTopicDepartmentChecklist();
    renderTopicFacilitatorChecklist();
    renderTopicDepartmentTable();
    renderTopicFacilitatorTable();

    document.getElementById('admin-topic-modal').classList.add('active');
}

function openTopicModal() {
    document.getElementById('topic-modal-title').textContent = 'Register New Topic';
    document.getElementById('topic-submit-btn').textContent = 'Save Topic';
    document.getElementById('topic-id').value = '';
    document.getElementById('admin-topic-form').reset();
    selectedTopicDeptIds = new Set();
    selectedTopicFacilitatorIds = new Set();
    refreshTopicDepartmentChecklist();
    refreshTopicFacilitatorTable();
    renderTopicDepartmentChecklist();
    renderTopicFacilitatorChecklist();
    renderTopicDepartmentTable();
    renderTopicFacilitatorTable();
    document.getElementById('admin-topic-modal').classList.add('active');
}

document.addEventListener('DOMContentLoaded', () => {
    refreshTopicDepartmentChecklist();
    refreshTopicFacilitatorTable();
    bindTopicComboToggle('topic-dept-combo-btn', 'topic-dept-combo-panel');
    bindTopicComboToggle('topic-facilitator-combo-btn', 'topic-facilitator-combo-panel');

    document.addEventListener('click', () => closeTopicComboPanels());
});
</script>
