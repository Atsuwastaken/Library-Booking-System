<!-- Admin Access Modal -->
<div class="modal-overlay" id="admin-login-modal">
    <div class="modal-content" style="max-width: 400px; text-align: center; padding: 2.5rem;">
        <div class="modal-header" style="justify-content: center; margin-bottom: 2rem;">
            <div class="fac-avatar" style="background: var(--danger);"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></div>
            <h2 style="margin-top: 1rem;">Facilitator Access</h2>
        </div>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Please enter the management password to access instructor configurations.</p>
        
        <form id="admin-login-form">
            <div class="form-group">
                <input type="password" id="admin-password" class="login-input" placeholder="Management Password" aria-label="Management Password" required style="text-align: center; font-size: 1.2rem; letter-spacing: 0.2em;">
            </div>
            <div id="admin-error" style="color: var(--danger); font-size: 0.85rem; margin-top: -1rem; margin-bottom: 1rem; display: none;">Invalid Management Credentials.</div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1rem;">Verify Identity</button>
            <button type="button" class="btn btn-muted" onclick="document.getElementById('admin-login-modal').classList.remove('active')" style="margin-top: 1rem; font-size: 0.9rem;">Cancel Access Request</button>
        </form>
    </div>
</div>
