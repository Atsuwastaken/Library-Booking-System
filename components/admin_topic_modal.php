<div class="modal-overlay admin-modal" id="admin-topic-modal">
    <div class="modal-content admin-modal-card admin-modal-lg">
        <div class="modal-header">
            <h3 id="topic-modal-title">Register New Topic</h3>
            <button class="btn-close" type="button" data-close-modal>&times;</button>
        </div>
        <div class="modal-body">
            <form id="admin-topic-form">
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
