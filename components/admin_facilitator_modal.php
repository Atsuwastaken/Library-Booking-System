<div class="modal-overlay admin-modal" id="admin-facilitator-modal">
    <div class="modal-content admin-modal-card admin-modal-lg">
        <div class="modal-header">
            <h3 id="fac-modal-title">Register New Faculty Instructor</h3>
            <button class="btn-close" onclick="this.closest('.modal-overlay').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="admin-fac-form" onsubmit="handleFacilitatorSubmit(event)">
                <input type="hidden" id="fac-id" value="">
                <div class="form-group">
                    <label>Instructor Name</label>
                    <input type="text" id="fac-name" class="form-control" placeholder="e.g. Dr. Alan Turing" required>
                </div>
                <div class="form-group">
                    <label>Position / Title</label>
                    <input type="text" id="fac-position" class="form-control" placeholder="e.g. Chief Librarian">
                </div>
                <div class="form-group">
                    <label>Assign Departments</label>
                    <div class="combo-check" style="margin-top: 0.5rem; margin-bottom: 0.75rem;">
                        <button type="button" id="fac-dept-combo-btn" class="form-control combo-check-btn">Select departments...</button>
                        <div id="fac-dept-combo-panel" class="combo-check-panel"></div>
                    </div>
                    <div class="modal-table-scroll">
                        <table class="admin-table" style="font-size: 0.82rem;">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="fac-depts-selected-table">
                                <tr><td colspan="2" style="padding: 0.6rem 0.8rem; color: #94a3b8;">No departments selected.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="form-group">
                    <label>Specializations / Topics</label>
                    <div class="combo-check" style="margin-top: 0.5rem; margin-bottom: 0.75rem;">
                        <button type="button" id="fac-topic-combo-btn" class="form-control combo-check-btn">Select topics...</button>
                        <div id="fac-topic-combo-panel" class="combo-check-panel"></div>
                    </div>
                    <div class="modal-table-scroll">
                        <table class="admin-table" style="font-size: 0.82rem;">
                            <thead>
                                <tr>
                                    <th>Topic</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="fac-topics-selected-table">
                                <tr><td colspan="2" style="padding: 0.6rem 0.8rem; color: #94a3b8;">No topics selected.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="admin-modal-footer">
                    <button type="submit" id="fac-submit-btn" class="btn btn-primary" style="flex: 1;">Save Faculty Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let facModalTopics = [];
let facModalDepartments = [];
let selectedFacTopicIds = new Set();
let selectedFacDeptIds = new Set();

function closeFacComboPanels() {
    const panels = ['fac-dept-combo-panel', 'fac-topic-combo-panel'];
    panels.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}

function bindFacComboToggle(buttonId, panelId) {
    const btn = document.getElementById(buttonId);
    const panel = document.getElementById(panelId);
    if (!btn || !panel) return;

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = panel.style.display === 'block';
        closeFacComboPanels();
        panel.style.display = isOpen ? 'none' : 'block';
    });

    panel.addEventListener('click', (e) => e.stopPropagation());
}

function updateFacComboButtonLabels() {
    const deptBtn = document.getElementById('fac-dept-combo-btn');
    const topicBtn = document.getElementById('fac-topic-combo-btn');

    if (deptBtn) {
        deptBtn.textContent = selectedFacDeptIds.size > 0
            ? `${selectedFacDeptIds.size} department${selectedFacDeptIds.size > 1 ? 's' : ''} selected`
            : 'Select departments...';
    }

    if (topicBtn) {
        topicBtn.textContent = selectedFacTopicIds.size > 0
            ? `${selectedFacTopicIds.size} topic${selectedFacTopicIds.size > 1 ? 's' : ''} selected`
            : 'Select topics...';
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([refreshTopicChecklist(), refreshDeptChecklist()]);

    bindFacComboToggle('fac-dept-combo-btn', 'fac-dept-combo-panel');
    bindFacComboToggle('fac-topic-combo-btn', 'fac-topic-combo-panel');
    document.addEventListener('click', () => closeFacComboPanels());
});

async function refreshTopicChecklist() {
    try {
        const res = await fetch('api.php?action=get_topics');
        const data = await res.json();
        if (data.success) {
            facModalTopics = data.topics;
            renderFacTopicChecklist();
            renderFacTopicTable();
        }
    } catch (e) {}
}

async function refreshDeptChecklist() {
    try {
        const res = await fetch('api.php?action=get_departments');
        const data = await res.json();
        if (data.success) {
            facModalDepartments = data.departments;
            renderFacDeptChecklist();
            renderFacDeptTable();
        }
    } catch (e) {}
}

function renderFacTopicChecklist() {
    const panel = document.getElementById('fac-topic-combo-panel');
    if (!panel) return;

    panel.innerHTML = '';
    if (!facModalTopics.length) {
        panel.innerHTML = '<div class="combo-check-empty">No topics available.</div>';
        updateFacComboButtonLabels();
        return;
    }

    facModalTopics.forEach(t => {
        const row = document.createElement('label');
        row.className = 'combo-check-option';
        row.innerHTML = `<input type="checkbox" value="${t.id}" ${selectedFacTopicIds.has(String(t.id)) ? 'checked' : ''}> <span>${t.name}</span>`;

        const checkbox = row.querySelector('input');
        checkbox.addEventListener('change', () => {
            if (checkbox.checked) selectedFacTopicIds.add(String(t.id));
            else selectedFacTopicIds.delete(String(t.id));
            renderFacTopicTable();
            updateFacComboButtonLabels();
        });

        panel.appendChild(row);
    });

    updateFacComboButtonLabels();
}

function renderFacDeptChecklist() {
    const panel = document.getElementById('fac-dept-combo-panel');
    if (!panel) return;

    panel.innerHTML = '';
    if (!facModalDepartments.length) {
        panel.innerHTML = '<div class="combo-check-empty">No departments available.</div>';
        updateFacComboButtonLabels();
        return;
    }

    facModalDepartments.forEach(d => {
        const row = document.createElement('label');
        row.className = 'combo-check-option';
        row.innerHTML = `<input type="checkbox" value="${d.id}" ${selectedFacDeptIds.has(String(d.id)) ? 'checked' : ''}> <span>${d.name}</span>`;

        const checkbox = row.querySelector('input');
        checkbox.addEventListener('change', () => {
            if (checkbox.checked) selectedFacDeptIds.add(String(d.id));
            else selectedFacDeptIds.delete(String(d.id));
            renderFacDeptTable();
            updateFacComboButtonLabels();
        });

        panel.appendChild(row);
    });

    updateFacComboButtonLabels();
}

function renderFacTopicTable() {
    const tbody = document.getElementById('fac-topics-selected-table');
    if (!tbody) return;
    tbody.innerHTML = '';
    const selected = facModalTopics.filter(t => selectedFacTopicIds.has(String(t.id)));
    if (!selected.length) {
        tbody.innerHTML = '<tr><td colspan="2" style="padding: 0.6rem 0.8rem; color: #94a3b8;">No topics selected.</td></tr>';
        return;
    }
    selected.forEach(t => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${t.name}</td><td style="text-align:right;"><button type="button" class="btn btn-outline btn-sm" style="padding:0.15rem 0.5rem;">Remove</button></td>`;
        tr.querySelector('button').addEventListener('click', () => {
            selectedFacTopicIds.delete(String(t.id));
            renderFacTopicChecklist();
            renderFacTopicTable();
        });
        tbody.appendChild(tr);
    });
}

function renderFacDeptTable() {
    const tbody = document.getElementById('fac-depts-selected-table');
    if (!tbody) return;
    tbody.innerHTML = '';
    const selected = facModalDepartments.filter(d => selectedFacDeptIds.has(String(d.id)));
    if (!selected.length) {
        tbody.innerHTML = '<tr><td colspan="2" style="padding: 0.6rem 0.8rem; color: #94a3b8;">No departments selected.</td></tr>';
        return;
    }
    selected.forEach(d => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${d.name}</td><td style="text-align:right;"><button type="button" class="btn btn-outline btn-sm" style="padding:0.15rem 0.5rem;">Remove</button></td>`;
        tr.querySelector('button').addEventListener('click', () => {
            selectedFacDeptIds.delete(String(d.id));
            renderFacDeptChecklist();
            renderFacDeptTable();
        });
        tbody.appendChild(tr);
    });
}

async function handleFacilitatorSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('fac-id').value;
    const topicIds = Array.from(selectedFacTopicIds);
    const deptIds = Array.from(selectedFacDeptIds);
    
    const payload = {
        name: document.getElementById('fac-name').value,
        position: document.getElementById('fac-position').value,
        topic_ids: topicIds,
        department_ids: deptIds
    };

    if (id) payload.id = id;
    
    const action = id ? 'update_facilitator' : 'add_facilitator';
    
    try {
        const res = await fetch(`api.php?action=${action}`, {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        if ((await res.json()).success) {
            document.getElementById('admin-facilitator-modal').classList.remove('active');
            if (typeof loadFacilitators === 'function') loadFacilitators();
            if (typeof loadTopicCatalog === 'function') loadTopicCatalog();
            e.target.reset();
            document.getElementById('fac-id').value = '';
            selectedFacTopicIds = new Set();
            selectedFacDeptIds = new Set();
            renderFacTopicChecklist();
            renderFacDeptChecklist();
            renderFacTopicTable();
            renderFacDeptTable();
        }
    } catch (e) {}
}

function openFacEdit(id, name, position, topicIdsPiped, deptIdsPiped) {
    document.getElementById('fac-modal-title').textContent = 'Update Faculty Profile';
    document.getElementById('fac-submit-btn').textContent = 'Confirm Updates';
    document.getElementById('fac-id').value = id;
    document.getElementById('fac-name').value = name;
    document.getElementById('fac-position').value = position || '';
    
    selectedFacTopicIds = new Set((topicIdsPiped || '').toString().split(',').map(v => v.trim()).filter(Boolean));
    selectedFacDeptIds = new Set((deptIdsPiped || '').toString().split(',').map(v => v.trim()).filter(Boolean));
    renderFacTopicChecklist();
    renderFacDeptChecklist();
    renderFacTopicTable();
    renderFacDeptTable();
    
    document.getElementById('admin-facilitator-modal').classList.add('active');
}

function resetFacilitatorPickerState() {
    selectedFacTopicIds = new Set();
    selectedFacDeptIds = new Set();
    renderFacTopicChecklist();
    renderFacDeptChecklist();
    renderFacTopicTable();
    renderFacDeptTable();
}
</script>
