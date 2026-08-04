<div class="modal-overlay admin-modal" id="admin-facilitator-modal">
    <div class="modal-content admin-modal-card admin-modal-lg">
        <div class="modal-header">
            <h3 id="fac-modal-title">Register New Faculty Instructor</h3>
            <button class="btn-close" type="button" data-close-modal>&times;</button>
        </div>
        <div class="modal-body">
            <form id="admin-fac-form">
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
