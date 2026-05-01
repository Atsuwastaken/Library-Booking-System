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
    <div class="app-shell">
        <!-- ===== LEFT SIDEBAR ===== -->
        <aside class="panel-left">
            <!-- Brand / Logo -->
            <div class="pl-brand">
                <img src="img/auf-logo.png" alt="AUF Logo">
                <div>
                    <strong>Library Booking</strong>
                    <span>Learning & Research</span>
                </div>
            </div>

            <!-- Account Section -->
            <div class="pl-account">
                <div class="pl-avatar">
                    <?= htmlspecialchars($studentInitials) ?>
                </div>
                <div class="pl-user">
                    <strong><?= htmlspecialchars($firstName) ?></strong>
                    <span><?= htmlspecialchars($studentEmail) ?></span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="pl-nav">
                <button class="pl-nav-btn active tab-btn" data-tab="explore">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    Dashboard
                </button>
                <button class="pl-nav-btn tab-btn" data-tab="appointments">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                    My Appointments
                </button>
                <button class="pl-nav-btn tab-btn" data-tab="facilitators">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Facilitators
                </button>
                <?php if ($isFacilitator): ?>
                    <button class="pl-nav-btn tab-btn" data-tab="my-sessions">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20"></path>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        My Sessions
                    </button>
                <?php endif; ?>
                <button class="pl-nav-btn tab-btn" data-tab="profile">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    My Profile
                </button>
            </nav>

            <!-- Recent Appointments List -->
            <div class="pl-bookings">
                <h4>Recent Appointments</h4>
                <div id="sidebar-bookings-list">
                    <div class="pl-booking-empty">Loading...</div>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="pl-bottom">
                <?php if ($isAdminUser): ?>
                    <a href="admin.php" class="pl-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        Admin Panel
                    </a>
                <?php endif; ?>
                <a href="logout.php" class="pl-link pl-signout">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Sign Out
                </a>
            </div>
        </aside>

        <!-- ===== MAIN CONTENT ===== -->
        <main class="panel-right">
            <div class="tab-content">
                <!-- Explore Tab Pane -->
                <div class="tab-pane active" id="explore-pane">
                    <div style="display: flex; flex-direction: column; gap: 2rem;">
                        <div class="dashboard-header">
                            <div>
                                <h1>Welcome back, <?= htmlspecialchars($firstName) ?>!</h1>
                                <p>Plan your next library session or discover upcoming seminars.</p>
                            </div>
                        </div>

                        <!-- 3-Column Dashboard Layout -->
                        <div class="explore-dashboard-grid" style="display: grid; grid-template-columns: 340px 1fr 340px; gap: 2rem; align-items: flex-start;">
                            
                            <!-- Column 1: Mini Calendar -->
                            <div class="dashboard-widget-wrapper">
                                <div class="mini-calendar-card">
                                    <div class="mini-calendar-header">
                                        <button class="mini-nav-btn" id="mini-prev-month">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <polyline points="15 18 9 12 15 6"></polyline>
                                            </svg>
                                        </button>
                                        <div class="mini-calendar-date" id="mini-current-date">Month Year</div>
                                        <button class="mini-nav-btn" id="mini-next-month">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <polyline points="9 18 15 12 9 6"></polyline>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="mini-calendar-grid" id="mini-calendar-grid">
                                        <!-- Populate via JS -->
                                    </div>
                                </div>
                            </div>

                            <!-- Column 2: Library Events -->
                            <div class="dashboard-widget-wrapper">
                                <div class="dashboard-widget" style="background: white; padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); height: 100%;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                                        <div style="background: #eff6ff; color: #2563eb; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 style="font-size: 1.1rem; font-weight: 700; color: #1e293b;">Upcoming Events</h3>
                                            <p style="font-size: 0.8rem; color: #64748b;">Join our latest library seminars</p>
                                        </div>
                                    </div>
                                    <div id="seminars-list" style="display: flex; flex-direction: column; gap: 1rem;">
                                        <div style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1.5rem 0;">Fetching events...</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 3: Instructors -->
                            <div class="dashboard-widget-wrapper">
                                <div class="dashboard-widget" style="background: white; padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); height: 100%;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                                        <div style="background: #f0fdf4; color: #16a34a; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 style="font-size: 1.1rem; font-weight: 700; color: #1e293b;">Our Faculty</h3>
                                            <p style="font-size: 0.8rem; color: #64748b;">Connect with our instructors</p>
                                        </div>
                                    </div>
                                    <div id="mini-instructors-list" style="display: flex; flex-direction: column; gap: 1rem;">
                                        <div style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1.5rem 0;">Loading instructors...</div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Appointments Tab Pane -->
                <div class="tab-pane" id="appointments-pane">
                    <div class="appointments-header-panel">
                        <div class="section-title" style="margin-bottom: 0;">
                            <h3>My Scheduled Appointments</h3>
                            <p style="color: var(--text-secondary); margin-top: 0.5rem;">Manage and view your upcoming library sessions.</p>
                        </div>
                        <div class="appointments-subtabs">
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
                    </div>
                    <div id="my-appointments-grid" class="sessions-grid" style="padding: 2rem;">
                        <div class="loader-container">Fetching your appointments...</div>
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
                                <input type="text" id="fac-directory-search" placeholder="Search by name or subject...">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div id="main-facilitators-list" class="facilitator-directory-grid">
                        <div class="loader-container">Fetching our faculty...</div>
                    </div>
                </div>

                <!-- My Sessions (Facilitator Role) -->
                <?php if ($isFacilitator): ?>
                    <div class="tab-pane" id="my-sessions-pane">
                        <div class="section-title">
                            <h3>Confirmed Sessions for My Facilitation</h3>
                            <p style="color: var(--text-secondary); margin-top: 0.5rem;">Manage and view sessions where you are the primary instructor.</p>
                        </div>
                        <div id="my-sessions-grid" class="sessions-grid" style="margin-top: 2rem;">
                            <div class="loader-container">Fetching your sessions...</div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Profile Tab Pane -->
                <div class="tab-pane" id="profile-pane">
                    <div class="profile-header-panel">
                        <div class="section-title" style="margin-bottom: 2rem; padding: 2rem 2rem 0;">
                            <h3>My Account Profile</h3>
                            <p style="color: var(--text-secondary); margin-top: 0.5rem;">Update your personal information and security settings.</p>
                        </div>
                    </div>
                    
                    <div class="profile-container" style="max-width: 900px; margin: 0 auto; padding: 0 2rem 4rem;">
                        <form id="profile-update-form" class="profile-form-grid">
                            <div class="profile-section-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="profile-section card">
                                    <h4 class="profile-section-title">General Information</h4>
                                    <div class="field">
                                        <label>Full Name</label>
                                        <input type="text" id="prof-name" name="name" required>
                                    </div>
                                    <div class="field">
                                        <label>Email Address</label>
                                        <input type="email" id="prof-email" name="email" required>
                                    </div>
                                    <div class="field">
                                        <label>Department / College</label>
                                        <select id="prof-department" name="department_id" required>
                                            <option value="">Select department</option>
                                            <!-- Populated via JS -->
                                        </select>
                                    </div>
                                </div>

                                <div class="profile-section card" id="prof-student-section" style="display: none;">
                                    <h4 class="profile-section-title">Academic Details</h4>
                                    <div class="field">
                                        <label>Student Number</label>
                                        <input type="text" id="prof-student-number" name="student_number">
                                    </div>
                                    <div class="field">
                                        <label>Year Level</label>
                                        <select id="prof-year-level" name="year_level">
                                            <option value="">Select year</option>
                                            <option value="1st Year">1st Year</option>
                                            <option value="2nd Year">2nd Year</option>
                                            <option value="3rd Year">3rd Year</option>
                                            <option value="4th Year">4th Year</option>
                                            <option value="5th Year">5th Year</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label>Course / Program</label>
                                        <select id="prof-program" name="course_program">
                                            <option value="">Select program</option>
                                            <!-- Populated via JS -->
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label>Course / Major</label>
                                        <input type="text" id="prof-course" name="course" placeholder="e.g. Software Engineering">
                                    </div>
                                    <div class="field">
                                        <label>Enrollment Status</label>
                                        <select id="prof-enrollment-status" name="enrollment_status">
                                            <option value="Regular">Regular</option>
                                            <option value="Irregular">Irregular</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label>Enrollment Type</label>
                                        <select id="prof-enrollment-type" name="enrollment_type">
                                            <option value="">Select type</option>
                                            <option value="New">New Student</option>
                                            <option value="Returning">Returning Student</option>
                                            <option value="Transferee">Transferee</option>
                                            <option value="Cross-Enrollee">Cross-Enrollee</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="profile-section card" style="margin-bottom: 1.5rem;">
                                <h4 class="profile-section-title">Security & Password</h4>
                                <p class="helper-text" style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.25rem;">Leave password fields blank if you don't want to change your current password.</p>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="field">
                                        <label>Current Password</label>
                                        <input type="password" id="prof-current-password" placeholder="Verification required">
                                    </div>
                                    <div class="field">
                                        <label>New Password</label>
                                        <input type="password" id="prof-new-password">
                                    </div>
                                    <div class="field">
                                        <label>Confirm New Password</label>
                                        <input type="password" id="prof-confirm-password">
                                    </div>
                                </div>
                            </div>

                            <div class="profile-footer card" style="display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 2rem;">
                                <div class="system-info" style="display: flex; gap: 2rem;">
                                    <div>
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Account Role</label>
                                        <span id="prof-role-badge" class="pl-badge">General</span>
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">User Type</label>
                                        <span id="prof-type-text" style="font-weight: 600; color: var(--text-main);">Student</span>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" id="btn-save-profile" style="padding: 0.8rem 2.5rem;">Save Changes</button>
                            </div>
                        </form>
                        <div id="profile-status" style="margin-top: 1.5rem; text-align: center; border-radius: 12px; padding: 1.25rem; display: none; font-weight: 500;"></div>
                    </div>
                </div>
            </div>
        </main>
    </div><!-- /.app-shell -->

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
        // Load appointments into sidebar (pl-bookings)
        (async function () {
            const el = document.getElementById('sidebar-bookings-list');
            if (!el) return;
            try {
                const r = await fetch('api.php?action=my_appointments');
                const d = await r.json();
                const list = d.appointments || d.data || [];
                if (!list.length) { 
                    el.innerHTML = '<div class="pl-booking-empty">No appointments yet.</div>'; 
                    return; 
                }
                el.innerHTML = list.slice(0, 8).map(a => {
                    const s = (a.status || 'pending').toLowerCase();
                    const statusClass = s === 'confirmed' ? 'pl-badge-confirmed' : (s === 'cancelled' || s === 'declined') ? 'pl-badge-cancelled' : 'pl-badge-pending';
                    const label = s.charAt(0).toUpperCase() + s.slice(1);
                    return `
                    <div class="pl-booking-row">
                        <div class="pl-booking-info">
                            <strong>${a.topic || a.session_type || 'Booking'}</strong>
                            <span>${a.date || a.appointment_date || ''}</span>
                        </div>
                        <span class="pl-badge ${statusClass}">${label}</span>
                    </div>`;
                }).join('');
            } catch (e) { 
                el.innerHTML = '<div class="pl-booking-empty" style="color: #f87171;">Load error.</div>'; 
            }
        })();
    </script>
</body>

</html>