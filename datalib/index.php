<?php
session_start();
if (!isset($_SESSION['student_email'])) {
    header('Location: login.php');
    exit;
}

// Extract first name dynamically from email prefix (e.g. text before @ and split by .)
$emailPrefix = explode('@', $_SESSION['student_email'])[0];
$firstName = ucfirst(strtolower(explode('.', $emailPrefix)[0]));

$studentInitials = strtoupper(substr($firstName, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Booking System</title>
    <!-- Modern Typography: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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
            <p><?= htmlspecialchars($_SESSION['student_email']) ?></p>
        </div>
        <div class="sidebar-links">
            <a href="logout.php" class="btn btn-outline"
                style="width: 100%; justify-content: center; color: var(--danger); border-color: rgba(207, 34, 46, 0.3);">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Sign Out
            </a>
        </div>
    </div>

    <main class="dashboard">
        <div class="calendar-hero">
            <div class="calendar-card">
                <div class="calendar-top">
                    <h2 id="calendar-month-year">Month Year</h2>
                    <div class="calendar-controls">
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
            </div>
            <div class="calendar-legend">
                <div class="legend-item"><span class="dot dot-available"></span> Available</div>
                <div class="legend-item"><span class="dot dot-booked"></span> Fully Booked</div>
                <div class="legend-item"><span class="dot dot-closed"></span> Library Closed</div>
            </div>
        </div>

        <div class="section-title">
            <h3 id="selected-date-label">Available Sessions</h3>
        </div>

        <div id="sessions-grid" class="sessions-grid" style="margin-top: 1rem;">
            <div class="loader-container">Select a date on the calendar to view appointments.</div>
        </div>
    </main>

    <!-- Booking Context Modal Flow -->
    <div class="modal-overlay" id="checkout-modal">
        <div class="modal-content checkout-box">
            <div class="modal-header">
                <h2>Confirm Booking</h2>
                <div class="lock-indicator">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <span>Time slot temporarily reserved</span>
                </div>
            </div>

            <div class="session-brief">
                <div class="brief-item">
                    <span>Topic</span>
                    <strong id="modal-topic"></strong>
                </div>
                <div class="brief-item">
                    <span>Facilitator</span>
                    <strong id="modal-facilitator"></strong>
                </div>
                <div class="brief-item">
                    <span>Schedule</span>
                    <strong id="modal-datetime"></strong>
                </div>
                <div class="brief-item">
                    <span>Mode</span>
                    <strong id="modal-mode" class="badge"></strong>
                </div>
            </div>

            <form id="confirm-booking-form">
                <input type="hidden" id="modal-session-id">

                <div class="form-group">
                    <label>Select Subject/Topic</label>
                    <select id="modal-topic-select" class="login-input" style="padding: 0.8rem;">
                        <option value="">Default (Session Topic)</option>
                        <option value="Mathematics">Mathematics</option>
                        <option value="Science & Technology">Science & Technology</option>
                        <option value="Computer Science">Computer Science</option>
                        <option value="Literature & Arts">Literature & Arts</option>
                        <option value="History">History</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Special Requests (Optional)</label>
                    <textarea id="special-requests" rows="3"
                        placeholder="Any specific requests or questions?"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-muted" id="btn-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn-confirm">Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>

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