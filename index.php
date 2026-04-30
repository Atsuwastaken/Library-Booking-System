<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/packages/core/BookingService.php';
$service = new BookingService();
$currentUser = $service->getUserInfo((int) $_SESSION['user_id']);

if (!$currentUser) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Prevent Edge "Content unavailable. Resource was not cached" error
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("X-Content-Type-Options: nosniff");

$firstName = $currentUser['name'] ?? 'User';
$studentInitials = strtoupper(substr(trim($firstName), 0, 1));
$studentEmail = $currentUser['email'] ?? 'No email';
$isFacilitator = !empty($currentUser['facilitator_id']);
$isAdminUser = strtolower((string) ($currentUser['role'] ?? '')) === 'admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Booking System</title>
    <!-- Modern Typography: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="layout-fixed">
    <!-- Bold gradient background -->
    <div class="app-bg"></div>
    <div class="app-bg-accent"></div>

    <!-- Main App Card -->
    <div class="app-card">
        <!-- Top Header -->

        <!-- Content: Left Info Panel + Right Main Area -->
        <div class="content-split">

            <!-- ===== LEFT COLUMN WIDGETS ===== -->
            <aside class="left-widgets"
                style="width: 320px; min-width: 320px; padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem; background: #f8f9fc; border-right: 1px solid var(--border); overflow-y: auto;">

                <!-- Account Widget -->
                <div class="dashboard-widget"
                    style="background: white; padding: 2rem 1.5rem 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow-sm);">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div class="ip-avatar"
                            style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #4f6ef7, #7c9dff); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem;">
                            <?= htmlspecialchars($studentInitials) ?>
                        </div>
                        <div>
                            <strong
                                style="display: block; font-size: 1.1rem; color: var(--text-primary);"><?= htmlspecialchars($firstName) ?></strong>
                            <span
                                style="color: var(--text-secondary); font-size: 0.85rem;"><?= htmlspecialchars($studentEmail) ?></span>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <?php if ($isAdminUser): ?>
                            <a href="admin.php" class="btn btn-outline btn-sm"
                                style="width: 100%; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                </svg>
                                Admin Panel
                            </a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-muted btn-sm"
                            style="width: 100%; justify-content: center; color: var(--danger);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Sign Out
                        </a>
                    </div>
                </div>

                <!-- Navigation Widget -->
                <div class="dashboard-widget"
                    style="background: white; padding: 2rem 1.25rem 1.25rem; border-radius: var(--radius); box-shadow: var(--shadow-sm);">
                    <div
                        style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.75rem; letter-spacing: 0.05em;">
                        Navigation</div>
                    <nav class="tabs-nav" style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <button class="tab-btn active" data-tab="explore"
                            style="display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.75rem; border: none; background: transparent; border-radius: 6px; width: 100%; text-align: left; cursor: pointer; color: var(--text-primary); font-weight: 600;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            Calendar
                        </button>
                        <button class="tab-btn" data-tab="appointments"
                            style="display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.75rem; border: none; background: transparent; border-radius: 6px; width: 100%; text-align: left; cursor: pointer; color: var(--text-secondary); font-weight: 500;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <polyline points="17 11 19 13 23 9"></polyline>
                            </svg>
                            My Appointments
                        </button>
                        <button class="tab-btn" data-tab="facilitators"
                            style="display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.75rem; border: none; background: transparent; border-radius: 6px; width: 100%; text-align: left; cursor: pointer; color: var(--text-secondary); font-weight: 500;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            Facilitators
                        </button>
                        <?php if ($isFacilitator): ?>
                            <button class="tab-btn" data-tab="my-sessions"
                                style="display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.75rem; border: none; background: transparent; border-radius: 6px; width: 100%; text-align: left; cursor: pointer; color: var(--text-secondary); font-weight: 500;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2v20"></path>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                My Sessions
                            </button>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- My Appts Widget -->
                <div class="dashboard-widget"
                    style="background: white; padding: 2rem 1.25rem 1.25rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); flex: 1; display: flex; flex-direction: column;">
                    <div
                        style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.75rem; letter-spacing: 0.05em;">
                        My Appointments</div>
                    <div id="sidebar-bookings-list"
                        style="display: flex; flex-direction: column; gap: 0.5rem; overflow-y: auto; flex: 1;">
                        <div
                            style="color: var(--text-secondary); font-size: 0.85rem; padding: 1rem 0; text-align: center;">
                            Loading appointments...</div>
                    </div>
                </div>
            </aside>

            <!-- ===== RIGHT: MAIN CONTENT ===== -->
            <main class="main-content">
                <div class="tab-content">
                    <!-- Explore Tab Pane -->
                    <div class="tab-pane active" id="explore-pane">
                        <div class="calendar-column"
                            style="max-width: 100%; display: flex; align-items: flex-start; justify-content: flex-start; padding: 2.5rem; gap: 2.5rem; min-height: 80vh;">
                            <!-- COLUMN 1: CALENDAR & EVENTS -->
                            <div style="display: flex; flex-direction: column; gap: 2.5rem; width: 340px;">
                                <!-- Minimal Mini Calendar Widget -->
                                <div class="mini-calendar-card" style="padding: 1.5rem; width: 100%; max-width: 100%;">
                                    <div class="mini-calendar-header">
                                        <button class="mini-nav-btn" id="mini-prev-month">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2.5">
                                                <polyline points="15 18 9 12 15 6"></polyline>
                                            </svg>
                                        </button>
                                        <div class="mini-calendar-date" id="mini-current-date">Month Year</div>
                                        <button class="mini-nav-btn" id="mini-next-month">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2.5">
                                                <polyline points="9 18 15 12 9 6"></polyline>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="mini-calendar-grid" id="mini-calendar-grid">
                                        <!-- Populate via JS -->
                                    </div>
                                </div>

                                <!-- Library Events Widget -->
                                <div class="dashboard-widget"
                                    style="background: white; padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); width: 100%; margin-top: 0;">
                                    <div
                                        style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                                        <div
                                            style="background: #eff6ff; color: #2563eb; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                                <path
                                                    d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 style="font-size: 1.1rem; font-weight: 700; color: #1e293b;">Library
                                                Events</h3>
                                            <p style="font-size: 0.8rem; color: #64748b;">Join our latest seminars</p>
                                        </div>
                                    </div>
                                    <div id="seminars-list" style="display: flex; flex-direction: column; gap: 1rem;">
                                        <div
                                            style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1.5rem 0;">
                                            Fetching events...</div>
                                    </div>
                                </div>
                            </div>

                            <!-- COLUMN 2: OUR INSTRUCTORS -->
                            <div class="dashboard-widget"
                                style="background: white; padding: 2.5rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); flex: 1; max-width: 400px; min-height: 600px;">
                                <div style="display: flex; align-items: right; gap: 0.75rem; margin-bottom: 2rem;">
                                    <div
                                        style="background: #f0fdf4; color: #16a34a; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 style="font-size: 1.25rem; font-weight: 700; color: #1e293b;">Our
                                            Instructors</h3>
                                        <p style="font-size: 0.9rem; color: #64748b;">Connect with our staff</p>
                                    </div>
                                </div>
                                <div id="mini-instructors-list"
                                    style="display: flex; flex-direction: column; gap: 1.25rem;">
                                    <div
                                        style="color: #94a3b8; font-size: 0.9rem; text-align: center; padding: 3rem 0;">
                                        Loading instructors...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Appointments Tab Pane -->
                    <div class="tab-pane" id="appointments-pane">
                        <div class="section-title">
                            <h3>My Scheduled Appointments</h3>
                            <p style="color: var(--text-secondary); margin-top: 0.5rem;">Manage and view your upcoming
                                library sessions.</p>
                        </div>
                        <div class="appointments-subtabs"
                            style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button type="button" id="appointments-subtab-active"
                                class="btn btn-primary btn-sm appointment-subtab-btn active" data-view="active">My
                                Appointments</button>
                            <button type="button" id="appointments-subtab-cancelled"
                                class="btn btn-outline btn-sm appointment-subtab-btn"
                                data-view="cancelled">Cancelled/Declined</button>
                            <button type="button" id="appointments-subtab-completed"
                                class="btn btn-outline btn-sm appointment-subtab-btn"
                                data-view="completed">Completed</button>
                        </div>
                        <div id="my-appointments-grid" class="sessions-grid" style="margin-top: 2rem;">
                            <div class="loader-container">
                                <div style="margin-bottom: 1rem; opacity: 0.5;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                </div>
                                You don't have any appointments scheduled yet.
                            </div>
                        </div>
                    </div>

                    <!-- Facilitators Tab Pane -->
                    <div class="tab-pane" id="facilitators-pane">
                        <div class="facilitator-top-panel">
                            <div class="panel-info">
                                <h3>Library Faculty Directory</h3>
                                <p>Search or select a facilitator to view availability and expertise</p>
                            </div>
                            <div class="panel-actions">
                                <div class="search-box-new">
                                    <input type="text" id="fac-directory-search"
                                        placeholder="Search by name or subject...">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div id="admin-facilitator-controls" style="display: none; margin-bottom: 2rem;">
                            <button class="btn btn-primary" onclick="showFacilitatorForm()">+ Add New
                                Instructor</button>
                        </div>
                        <h4 style="margin: 2rem 0 1rem; font-weight: 500; font-size: 1.1rem; color: #333;">Pick a
                            Facilitator</h4>
                        <div id="main-facilitators-list" class="facilitator-directory-grid">
                            <div class="loader-container">Fetching our faculty...</div>
                        </div>
                    </div>

                    <!-- My Sessions (Facilitator Role) -->
                    <?php if ($isFacilitator): ?>
                        <div class="tab-pane" id="my-sessions-pane">
                            <div class="section-title">
                                <h3>Confirmed Sessions for My Facilitation</h3>
                                <p style="color: var(--text-secondary); margin-top: 0.5rem;">Manage and view sessions where
                                    you are the primary instructor.</p>
                            </div>
                            <div id="my-sessions-grid" class="sessions-grid" style="margin-top: 2rem;">
                                <div class="loader-container">Fetching your sessions...</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>

        </div><!-- /.content-split -->
    </div><!-- /.app-card -->

    <!-- Modals -->
    <div class="modal-overlay" id="success-modal">
        <div class="modal-content success-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none"
                stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="success-icon">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <h2>Booking Successful!</h2>
            <p>Your reservation has been confirmed. A confirmation email with details has been sent to your address.</p>
            <button class="btn btn-primary" id="btn-close-success" style="margin-top: 1.5rem;">Close</button>
        </div>
    </div>
    <?php include 'components/cancel_reason_modal.php'; ?>
    <?php include 'components/change_instructor_modal.php'; ?>
    <?php include 'components/booking_modal.php'; ?>
    <?php include 'components/admin_login_modal.php'; ?>
    <?php include 'components/facilitators_modal.php'; ?>

    <script src="js/app.js"></script>
    <script>
        // Load appointments into left info panel
        (async function () {
            const el = document.getElementById('sidebar-bookings-list');
            if (!el) return;
            try {
                const r = await fetch('api.php?action=my_appointments');
                const d = await r.json();
                const list = d.appointments || d.data || [];
                if (!list.length) { el.innerHTML = '<div style="color: var(--text-secondary); font-size: 0.85rem; padding: 1rem 0; text-align: center;">No appointments yet.</div>'; return; }
                el.innerHTML = list.slice(0, 10).map(a => {
                    const s = (a.status || 'pending').toLowerCase();
                    const cls = s === 'confirmed' ? 'success' : (s === 'cancelled' || s === 'declined') ? 'danger' : 'warning';
                    const label = s.charAt(0).toUpperCase() + s.slice(1);
                    return `<div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f8f9fc; border-radius: 8px; border: 1px solid var(--border);">
                    <div style="display: flex; flex-direction: column;">
                        <strong style="color: var(--text-primary); font-size: 0.85rem;">${a.topic || a.session_type || 'Booking'}</strong>
                        <span style="color: var(--text-secondary); font-size: 0.75rem;">${a.date || a.appointment_date || ''}</span>
                    </div>
                    <span style="font-size: 0.65rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 99px; background: var(--${cls}); color: white;">${label}</span>
                </div>`;
                }).join('');
            } catch (e) { el.innerHTML = '<div style="color: var(--danger); font-size: 0.85rem; padding: 1rem 0; text-align: center;">Could not load.</div>'; }
        })();
    </script>
</body>

</html>