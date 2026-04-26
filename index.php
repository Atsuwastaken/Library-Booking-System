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

<body>
    <!-- Main Header Component -->
    <?php include 'components/header.php'; ?>

    <!-- User Account Sidebar -->
    <div class="user-sidebar" id="user-sidebar">
        <div class="sidebar-header">
            <div class="avatar-large"><?= htmlspecialchars($studentInitials) ?></div>
            <h3><?= htmlspecialchars($firstName) ?></h3>
            <p><?= htmlspecialchars($studentEmail) ?></p>
            <a href="logout.php" class="btn btn-outline btn-sm" style="margin-top: 1rem; width: 100%; justify-content: center;">Sign Out</a>
        </div>
    </div>

    <main class="dashboard">
        <div class="tabs-nav">
            <button class="tab-btn active" data-tab="explore">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Explore & Book
            </button>
            <button class="tab-btn" data-tab="appointments">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
                My Appointments
            </button>
            <button class="tab-btn" data-tab="facilitators">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Our Facilitators
            </button>
            <?php if ($isFacilitator): ?>
            <button class="tab-btn" data-tab="my-sessions" style="border-left: 2px solid #e2e8f0; margin-left: auto;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                My Sessions
            </button>
            <?php endif; ?>
        </div>

        <div class="tab-content">
            <!-- Explore Tab Pane -->
            <div class="tab-pane active" id="explore-pane">
                    <div class="calendar-column" style="max-width: 100%;">
                        <div class="calendar-card">
                            <div class="calendar-top">
                                <div class="calendar-title-group">
                                    <h2 id="calendar-month-year">Month Year</h2>
                                    <p id="selected-date-label" style="font-size: 0.85rem; color: #64748b; font-weight: 500; margin-top: 0.2rem;">Showing schedule for October 24, 2023</p>
                                </div>
                                <div class="calendar-controls">
                                    <button class="btn btn-outline btn-sm" id="toggle-view-btn" title="Toggle Week/Month View">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 13 12 18 17 13"></polyline><polyline points="7 6 12 11 17 6"></polyline></svg>
                                        <span>View</span>
                                    </button>
                                    <button class="btn btn-outline btn-sm" id="prev-month">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <polyline points="15 18 9 12 15 6"></polyline>
                                        </svg>
                                    </button>
                                    <button class="btn btn-primary btn-sm" id="today-btn">Today</button>
                                    <button class="btn btn-outline btn-sm" id="next-month">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="calendar-days-header">
                                <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                            </div>
                            <div id="calendar-grid" class="calendar-grid-cells">
                                <!-- Populate via JS -->
                            </div>
                            
                            <div class="calendar-legend" style="margin-top: 0.8rem; border-top: 1px solid var(--border); padding-top: 0.8rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                                <div class="legend-item" style="font-size: 0.75rem; gap: 0.4rem; display: flex; align-items: center;"><span class="dot dot-available" style="width: 8px; height: 8px; background: var(--success); border-radius: 50%;"></span> Available</div>
                                <div class="legend-item" style="font-size: 0.75rem; gap: 0.4rem; display: flex; align-items: center;"><span class="dot dot-booked" style="width: 8px; height: 8px; background: var(--danger); border-radius: 50%;"></span> Booked</div>
                                <div class="legend-item" style="font-size: 0.75rem; gap: 0.4rem; display: flex; align-items: center;"><span class="dot dot-closed" style="width: 8px; height: 8px; background: #64748b; border-radius: 50%;"></span> Closed</div>
                                <div class="legend-item" style="font-size: 0.75rem; gap: 0.4rem; display: flex; align-items: center;"><span class="tag" style="font-size: 0.65rem; background: #f3e8ff; color: #9333ea; padding: 2px 4px; border-radius: 3px; border: 1px solid #e9d5ff;">Seminar</span> Institutional Seminar</div>
                              </div>
                        </div>

                        <!-- Today's Schedule Timeline -->
                        <div class="timeline-card">
                            <div class="timeline-header">
                                <h3>Today's Schedule Timeline</h3>
                                <span class="current-date-pill" id="timeline-date">Friday, April 17, 2026</span>
                            </div>
                            <div class="timeline-container">
                                <div class="timeline-axis">
                                    <span class="axis-label" style="left: 0;">8 AM</span>
                                    <span class="axis-label" style="left: 44.44%; transform: translateX(-50%);">12 PM</span>
                                    <span class="axis-label" style="left: 100%; transform: translateX(-100%);">5 PM</span>
                                </div>
                                <div class="timeline-track" id="today-timeline-track">
                                    <div class="timeline-line"></div>
                                    <div class="timeline-ticks">
                                        <div class="tick"></div><div class="tick"></div><div class="tick"></div>
                                        <div class="tick"></div><div class="tick"></div><div class="tick"></div>
                                        <div class="tick"></div><div class="tick"></div><div class="tick"></div>
                                        <div class="tick"></div>
                                    </div>
                                    <div id="timeline-events-container">
                                        <!-- Events placed here via JS -->
                                    </div>
                                </div>
                                <div class="timeline-confirmed-section" id="timeline-confirmed-section">
                                    <div class="timeline-confirmed-label">Confirmed Appointments</div>
                                    <div class="timeline-confirmed-bars" id="timeline-confirmed-bars">
                                        <div class="timeline-empty">No confirmed appointments for today.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NEW: Upcoming Seminars Section -->
                    <div class="dashboard-card" style="margin-top: 2rem;">
                        <div class="card-header">
                            <div class="card-title">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                <h3>Upcoming Seminars & Orientation</h3>
                            </div>
                        </div>
                        <div id="seminars-list" class="seminars-horizontal-grid">
                            <div class="loader-container">Fetching seminars...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments Tab Pane -->
            <div class="tab-pane" id="appointments-pane">
                <div class="section-title">
                    <h3>My Scheduled Appointments</h3>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem;">Manage and view your upcoming library sessions.</p>
                </div>

                <div class="appointments-subtabs" style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" id="appointments-subtab-active" class="btn btn-primary btn-sm appointment-subtab-btn active" data-view="active">My Appointments</button>
                    <button type="button" id="appointments-subtab-cancelled" class="btn btn-outline btn-sm appointment-subtab-btn" data-view="cancelled">Cancelled/Declined Appointments</button>
                    <button type="button" id="appointments-subtab-completed" class="btn btn-outline btn-sm appointment-subtab-btn" data-view="completed">Completed Appointments</button>
                </div>
                
                <div id="my-appointments-grid" class="sessions-grid" style="margin-top: 2rem;">
                    <div class="loader-container">
                        <div style="margin-bottom: 1rem; opacity: 0.5;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
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
                            <input type="text" id="fac-directory-search" placeholder="Search by name or subject...">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </div>
                    </div>
                </div>

                <!-- Controls for Admin -->
                <div id="admin-facilitator-controls" style="display: none; margin-bottom: 2rem;">
                     <button class="btn btn-primary" onclick="showFacilitatorForm()">+ Add New Instructor</button>
                </div>

                <h4 style="margin: 2rem 0 1rem; font-weight: 500; font-size: 1.1rem; color: #333;">Pick a Facilitator</h4>
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
        </div>
    </main>


    <!-- Notification Modal Validation -->
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

    <!-- Cancellation Reason Modal -->
    <?php include 'components/cancel_reason_modal.php'; ?>

    <!-- Change Instructor Modal -->
    <?php include 'components/change_instructor_modal.php'; ?>

    <!-- Advanced Booking Modal -->
    <?php include 'components/booking_modal.php'; ?>

    <!-- Facilitator Admin Access -->
    <?php include 'components/admin_login_modal.php'; ?>
    
    <!-- Facilitators Modal -->
    <?php include 'components/facilitators_modal.php'; ?>

    <footer class="app-footer">
        <div class="footer-grid">
            <div class="footer-section">
                <h4>Opening Hours</h4>
                <ul>
                    <li><span>Monday - Friday</span> <strong>7:30 AM - 7:00 PM</strong></li>
                    <li><span>Saturday</span> <strong>8:00 AM - 5:00 PM</strong></li>
                    <li><span>Sunday</span> <strong style="color: var(--danger)">Closed</strong></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Location</h4>
                <p>AUF Main Library<br>Angeles City, Pampanga<br>Philippines</p>
            </div>
            <div class="footer-section">
                <h4>Contact Us</h4>
                <p>Email: library@auf.edu.ph<br>Phone: (045) 625-2888 local 712</p>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?= date('Y') ?> Angeles University Foundation Library. All Rights Reserved.
        </div>
    </footer>

    <script src="js/app.js"></script>
</body>

</html>