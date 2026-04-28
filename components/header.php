<header>
    <div class="logo">
        <img src="img/auf-logo.png" alt="AUF Logo"
            style="height: 44px; margin-right: 14px; background: white; padding: 4px; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
        Library Booking System
    </div>
    <div class="header-nav">
        <nav class="main-nav">
            <a href="#" class="active">Browse</a>
            <a href="#" id="view-facilitators-btn">Facilitators</a>
            <?php if (!empty($isAdminUser)): ?>
                <a href="admin.php" class="admin-return-link">Admin Dashboard</a>
            <?php endif; ?>
        </nav>
        <div class="avatar" id="avatar-btn" style="cursor: pointer;" title="Open account menu">
            <?= htmlspecialchars($studentInitials) ?>
        </div>
    </div>
</header>
