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

$role = strtolower((string) ($currentUser['role'] ?? ''));
$_SESSION['user_role'] = $role;
$_SESSION['facilitator_id'] = !empty($currentUser['facilitator_id']) ? (int) $currentUser['facilitator_id'] : null;

if ($role !== 'admin') {
    header('Location: index.php');
    exit;
}

$firstName = $firstName ?? '';
$studentEmail = $studentEmail ?? '';
$studentPhone = $studentPhone ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f8fafc; margin: 0; padding: 0; }
        .admin-layout { display: flex; flex-direction: column; min-height: 100vh; }
        
        /* Top Navigation Header */
        .admin-header {
            background: #ffffff;
            border-bottom: 2px solid #e2e8f0;
            padding: 1.5rem 2.5rem 0 2.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .admin-brand {
            font-size: 1.4rem;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-brand span {
            color: #0f172a;
        }
        
        .admin-nav {
            display: flex;
            gap: 2rem;
            overflow-x: auto;
        }
        
        .nav-link {
            padding: 0.8rem 0.5rem;
            color: #64748b;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .nav-link:hover { color: #1e293b; }
        .nav-link.active { color: #6366f1; border-bottom: 3px solid #6366f1; }
        
        /* Main Workspace */
        .admin-main { flex: 1; padding: 2.5rem; max-width: 1400px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .admin-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 2.5rem; }
        .admin-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .admin-table th { text-align: left; padding: 1.25rem 1rem; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 800; }
        .admin-table td { padding: 1.25rem 1rem; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-size: 0.9rem; }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tr:hover td { background: #f8fafc; }
        .action-btns { display: flex; gap: 0.75rem; align-items: center; }
        
        #admin-toasts {
            position: fixed;
            top: 2rem;
            right: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            z-index: 9999;
        }
        .toast {
            background: #0f172a;
            color: #fff;
            padding: 1rem 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: toastIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 600;
        }
        @keyframes toastIn { from { transform: translateX(100%) scale(0.5); opacity: 0; } to { transform: translateX(0) scale(1); opacity: 1; } }

        /* Admin Modal System */
        .admin-modal .modal-content {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 20px;
            box-shadow: 0 26px 60px rgba(15, 23, 42, 0.22);
            overflow: visible;
            backdrop-filter: blur(10px);
        }

        .admin-modal-card {
            width: min(94vw, 700px);
            max-height: 88vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .admin-modal-card.admin-modal-sm {
            width: min(92vw, 520px);
        }

        .admin-modal-card.admin-modal-md {
            width: min(92vw, 620px);
        }

        .admin-modal-card.admin-modal-lg {
            width: min(94vw, 760px);
        }

        .admin-modal .modal-header {
            margin-bottom: 0;
            border-bottom: 1px solid #dbe3ee;
            padding: 1.1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }

        .admin-modal .modal-header h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            color: #0f172a;
        }

        .admin-modal .btn-close {
            width: 32px;
            height: 32px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1.2rem;
            line-height: 1;
        }

        .admin-modal .btn-close:hover {
            background: #eef2ff;
            border-color: #c7d2fe;
            color: #4338ca;
        }

        .admin-modal .modal-body {
            padding: 1.25rem;
            overflow-y: auto;
            background: linear-gradient(180deg, rgba(248, 251, 255, 0.5) 0%, rgba(255, 255, 255, 0.95) 100%);
        }

        .admin-modal .form-group {
            margin-bottom: 1rem;
            gap: 0.4rem;
        }

        .admin-modal .form-group label {
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: #475569;
            text-transform: uppercase;
        }

        .admin-modal .form-control,
        .admin-modal .login-input {
            width: 100%;
            border: 1px solid #dbe3ee;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            padding: 0.65rem 0.75rem;
            font-family: inherit;
            font-size: 0.92rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .admin-modal .form-control:focus,
        .admin-modal .login-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.18);
            outline: none;
        }

        .admin-modal-footer {
            margin-top: 1.2rem;
            display: flex;
            gap: 0.8rem;
        }

        .admin-modal-footer .btn {
            justify-content: center;
        }

        .admin-main .calendar-cell.sunday-closed {
            pointer-events: auto;
            cursor: pointer;
        }

        .combo-check {
            position: relative;
        }

        .combo-check-btn {
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
        }

        .users-admin-search {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: 1px solid #dbe3ee;
            background: #f8fafc;
            border-radius: 14px;
            padding: 0.7rem 0.9rem;
            margin-bottom: 1rem;
        }

        .users-admin-search svg {
            color: #64748b;
            flex-shrink: 0;
        }

        .users-admin-search input {
            width: 100%;
            border: 0;
            background: transparent;
            color: #0f172a;
            font-family: inherit;
            font-size: 0.92rem;
            outline: none;
        }

        .users-input,
        .users-select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #fff;
            color: #0f172a;
            padding: 0.72rem 0.85rem;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .users-input::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }

        .users-input:hover,
        .users-select:hover {
            border-color: #94a3b8;
            background: #fcfdff;
        }

        .users-input:focus,
        .users-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.14);
        }

        .users-input[readonly] {
            background: #f8fafc;
            cursor: text;
        }

        .users-input-disabled {
            color: #64748b;
            background: #f8fafc;
            border-color: #dbe3ee;
        }

        .users-combobox-stack {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            min-width: 180px;
        }

        .users-combobox-label {
            font-size: 0.72rem;
            letter-spacing: 0.02em;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 800;
        }

        .users-combobox-note {
            font-size: 0.75rem;
            color: #64748b;
            line-height: 1.25;
        }

        .users-combobox {
            width: 100%;
            min-width: 140px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background-color: #fff;
            background-image: linear-gradient(45deg, transparent 50%, #64748b 50%), linear-gradient(135deg, #64748b 50%, transparent 50%);
            background-position: calc(100% - 18px) calc(1em + 2px), calc(100% - 12px) calc(1em + 2px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
            padding: 0.72rem 2.1rem 0.72rem 0.85rem;
            color: #0f172a;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            appearance: none;
            box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease, background-color 0.2s ease;
        }

        .users-combobox:hover {
            border-color: #94a3b8;
            background-color: #fcfdff;
        }

        .users-combobox:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.14);
            background-color: #fff;
        }

        .users-combobox[disabled] {
            background-color: #f8fafc;
            color: #64748b;
            opacity: 1;
            cursor: not-allowed;
        }

        .users-combobox-row {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .users-combobox-helper {
            font-size: 0.75rem;
            color: #64748b;
            line-height: 1.25;
        }

        .combo-check-btn::after {
            content: '▾';
            color: #64748b;
            margin-left: 0.5rem;
        }

        .combo-check-panel {
            display: none;
            position: absolute;
            top: calc(100% + 0.35rem);
            left: 0;
            right: 0;
            z-index: 20;
            max-height: 180px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #dbe3ee;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.14);
            padding: 0.45rem;
        }

        .combo-check-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.4rem;
            border-radius: 6px;
            cursor: pointer;
        }

        .combo-check-option:hover {
            background: #f8fafc;
        }

        .combo-check-empty {
            padding: 0.5rem;
            color: #94a3b8;
            font-size: 0.82rem;
        }

        .modal-table-scroll {
            max-height: 160px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
        }

        .modal-table-scroll .admin-table {
            margin: 0;
        }

        .modal-table-scroll .admin-table th {
            position: sticky;
            top: 0;
            background: #f8fafc;
            z-index: 1;
            padding-top: 0.8rem;
            padding-bottom: 0.8rem;
        }

        .modal-table-scroll .admin-table td {
            padding-top: 0.65rem;
            padding-bottom: 0.65rem;
        }

        @media (max-width: 640px) {
            .admin-modal .modal-body {
                padding: 0.95rem;
            }

            .admin-modal-footer {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div id="admin-toasts"></div>
    <div class="admin-layout">
        
        <header class="admin-header">
            <div class="admin-brand">
                ADMIN DASHBOARD
            </div>
            <nav class="admin-nav">
                <a href="index.php" class="nav-link">Staff Dashboard</a>
                <a href="#" class="nav-link" data-tab="calendar">Calendar</a>
                <a href="#" class="nav-link active" data-tab="requests">All Appointments</a>
                <a href="#" class="nav-link" data-tab="seminars">Seminars & Events</a>
                <a href="#" class="nav-link" data-tab="topics">Topics</a>
                <a href="#" class="nav-link" data-tab="facilitators">Facilitators</a>
                <a href="#" class="nav-link" data-tab="users">Users</a>
            </nav>
        </header>

        <main class="admin-main">
            <!-- Tab: Calendar -->
            <div id="tab-calendar" class="admin-tab-content" style="display: none;">
                <div class="calendar-card">
                    <div class="calendar-top">
                        <div class="calendar-title-group">
                            <h2 id="admin-calendar-month-year">Month Year</h2>
                            <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Click any date to book an appointment or mark it as an off-day.</p>
                        </div>
                        <div class="calendar-controls">
                            <button class="btn btn-outline btn-sm" id="admin-calendar-prev">Prev</button>
                            <button class="btn btn-outline btn-sm" id="admin-calendar-today">Today</button>
                            <button class="btn btn-outline btn-sm" id="admin-calendar-next">Next</button>
                        </div>
                    </div>
                    <div class="calendar-days-header">
                        <span>Sun</span>
                        <span>Mon</span>
                        <span>Tue</span>
                        <span>Wed</span>
                        <span>Thu</span>
                        <span>Fri</span>
                        <span>Sat</span>
                    </div>
                    <div id="admin-calendar-grid" class="calendar-grid-cells"></div>
                    <div class="calendar-legend" style="margin-top: 0.8rem; border-top: 1px solid var(--border); padding-top: 0.8rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <div class="legend-item" style="font-size: 0.75rem; gap: 0.4rem; display: flex; align-items: center;"><span class="dot dot-booked" style="width: 8px; height: 8px; background: var(--secondary); border-radius: 50%;"></span> Booked</div>
                        <div class="legend-item" style="font-size: 0.75rem; gap: 0.4rem; display: flex; align-items: center;"><span class="dot dot-offday" style="width: 8px; height: 8px; background: #f97316; border-radius: 50%;"></span> Off-Day</div>
                        <div class="legend-item" style="font-size: 0.75rem; gap: 0.4rem; display: flex; align-items: center;"><span class="dot dot-closed" style="width: 8px; height: 8px; background: #64748b; border-radius: 50%;"></span> Closed</div>
                    </div>
                </div>
            </div>

            <!-- Tab: Pending Requests -->
            <div id="tab-requests" class="admin-tab-content">
                <div class="appointments-head">
                    <h2>All Appointments</h2>
                    <p id="requests-summary">Showing all records</p>
                </div>
                <div class="filter-bar">
                    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; flex: 1;">
                        <div class="filter-group">
                            <label>Filter by<br>Requestor:</label>
                            <select class="filter-select" id="filter-requestor">
                                <option value="all">All</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Filter by<br>College:</label>
                            <select class="filter-select" id="filter-college">
                                <option value="all">All</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Filter by<br>Facilitator:</label>
                            <select class="filter-select" id="filter-facilitator">
                                <option value="all">All</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Filter by<br>Status:</label>
                            <select class="filter-select" id="filter-status">
                                <option value="all">All</option>
                                <option value="PENDING">PENDING</option>
                                <option value="CONFIRMED">CONFIRMED</option>
                                <option value="COMPLETED">COMPLETED</option>
                                <option value="CANCELLED">CANCELLED</option>
                                <option value="DECLINED">DECLINED</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Sort by<br>Date & Time:</label>
                            <select class="filter-select" id="filter-datetime">
                                <option value="newest">Newest First</option>
                                <option value="oldest">Oldest First</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Filter by<br>Date:</label>
                            <input class="filter-select" type="date" id="filter-date">
                            <button class="btn-reset-filters" id="filter-date-today" type="button">Today</button>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button class="btn-reset-filters" id="reset-request-filters" type="button">Reset Filters</button>
                        <button class="btn-export" id="export-logs-btn" type="button">Export Logs</button>
                    </div>
                </div>

                <div id="requests-grid" class="app-grid">
                    <!-- JS populated cards -->
                </div>
            </div>

            <!-- Tab: Seminars Management -->
            <div id="tab-seminars" class="admin-tab-content" style="display: none;">
                <div class="admin-card">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
                        <h3>Institutional Seminars</h3>
                        <button class="btn btn-primary btn-sm" onclick="openSeminarModal()">+ Add New Seminar</button>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Speaker</th>
                                <th>Schedule</th>
                                <th>Venue</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="seminars-tbody">
                            <!-- JS populated -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Topics Management -->
            <div id="tab-topics" class="admin-tab-content" style="display: none;">
                <div class="admin-card">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
                        <h3>Topic Catalog</h3>
                        <button class="btn btn-primary btn-sm" onclick="openTopicModal()">+ Add New Topic</button>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <input type="text" id="topics-search" class="form-control" placeholder="Search topic name or department coverage...">
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Topic</th>
                                <th>Department Coverage</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="topics-tbody">
                            <!-- JS populated -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Facilitators Management -->
            <div id="tab-facilitators" class="admin-tab-content" style="display: none;">
                <div class="admin-card">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
                        <h3>Faculty Directory</h3>
                        <button class="btn btn-primary btn-sm" onclick="openFacilitatorModal()">+ Add New Instructor</button>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <input type="text" id="facilitators-search" class="form-control" placeholder="Search facilitator, position, or department...">
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Department(s)</th>
                                <th>Position / Title</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="facilitators-tbody">
                            <!-- JS populated -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Users Management -->
            <div id="tab-users" class="admin-tab-content" style="display: none;">
                <div class="admin-card users-admin-shell">
                    <div class="users-admin-head">
                        <div>
                            <h3 style="margin: 0;">Pending Registration Requests</h3>
                            <p class="users-admin-subtitle">Review account requests and assign the proper role before approving access.</p>
                        </div>
                        <div class="users-admin-head-actions">
                            <div class="users-admin-counts">
                                <span class="users-admin-chip" id="pending-requests-count">0 pending</span>
                            </div>
                            <button class="btn btn-outline btn-sm" id="refresh-users-admin" type="button">Refresh</button>
                        </div>
                    </div>
                    <div class="users-admin-table-wrap">
                    <table class="admin-table users-admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Student No.</th>
                                <th>Department</th>
                                <th>Approval Settings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="registration-requests-tbody">
                            <tr><td colspan="6" style="padding: 1rem; color: #94a3b8;">Loading registration requests...</td></tr>
                        </tbody>
                    </table>
                    </div>
                </div>

                <div class="admin-card users-admin-shell">
                    <div class="users-admin-head">
                        <div>
                            <h3 style="margin: 0;">Users Directory</h3>
                            <p class="users-admin-subtitle">Maintain existing accounts, adjust privileges, and link facilitator profiles.</p>
                        </div>
                        <div class="users-admin-counts">
                            <span class="users-admin-chip" id="users-total-count">0 users</span>
                        </div>
                    </div>
                    <div class="users-admin-search">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" id="users-admin-search" placeholder="Search by name, email, student number, department, role, or facilitator status...">
                    </div>
                    <div class="users-directory-tabs" id="users-directory-tabs">
                        <button type="button" class="users-directory-tab active" data-users-pane="students">Students</button>
                        <button type="button" class="users-directory-tab" data-users-pane="staff">Staff</button>
                        <button type="button" class="users-directory-tab" data-users-pane="facilitators">Facilitators</button>
                        <button type="button" class="users-directory-tab" data-users-pane="admins">Admins</button>
                    </div>

                    <div class="users-directory-pane active" id="users-pane-students">
                        <div class="users-admin-table-wrap">
                            <table class="admin-table users-admin-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Student No.</th>
                                        <th>College</th>
                                        <th>Role</th>
                                        <th>Facilitator</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="users-admin-students-tbody">
                                    <tr><td colspan="7" style="padding: 1rem; color: #94a3b8;">Loading students...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="users-directory-pane" id="users-pane-staff">
                        <div class="users-pane-head">
                            <p class="users-pane-note">If a facilitator, the department info can be managed in the facilitator tab.</p>
                            <button class="btn btn-primary btn-sm" type="button" id="open-add-staff-modal">+ Add Staff</button>
                        </div>
                        <div class="users-admin-table-wrap">
                            <table class="admin-table users-admin-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Facilitator</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="users-admin-staff-tbody">
                                    <tr><td colspan="5" style="padding: 1rem; color: #94a3b8;">Loading staff...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="users-directory-pane" id="users-pane-facilitators">
                        <div class="users-pane-head">
                            <p class="users-pane-note">If a facilitator, the department info can be managed in the facilitator tab.</p>
                            <button class="btn btn-primary btn-sm" type="button" id="open-add-facilitator-user-modal">+ Add Facilitator</button>
                        </div>
                        <div class="users-admin-table-wrap">
                            <table class="admin-table users-admin-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Facilitator</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="users-admin-facilitators-tbody">
                                    <tr><td colspan="5" style="padding: 1rem; color: #94a3b8;">Loading facilitators...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="users-directory-pane" id="users-pane-admins">
                        <div class="users-pane-head" style="justify-content: flex-end;">
                            <button class="btn btn-primary btn-sm" type="button" id="open-add-admin-modal">+ Add Admin</button>
                        </div>
                        <div class="users-admin-table-wrap">
                            <table class="admin-table users-admin-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Facilitator</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="users-admin-admins-tbody">
                                    <tr><td colspan="5" style="padding: 1rem; color: #94a3b8;">Loading admins...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Appointment/Instructor Modal -->
    <?php include 'components/admin_edit_modal.php'; ?>

    <!-- Admin Calendar Action Modal -->
    <?php include 'components/admin_calendar_action_modal.php'; ?>

    <!-- Admin Off-Day Modal -->
    <?php include 'components/admin_offday_modal.php'; ?>

    <!-- Admin Booking Modal -->
    <?php include 'components/booking_modal.php'; ?>

    <!-- Cancellation Reason Modal -->
    <?php include 'components/cancel_reason_modal.php'; ?>
    
    <!-- Seminar Modal -->
    <?php include 'components/seminar_modal.php'; ?>

    <!-- Facilitator Modal -->
    <?php include 'components/admin_facilitator_modal.php'; ?>

    <!-- Topic Modal -->
    <?php include 'components/admin_topic_modal.php'; ?>

    <div class="modal-overlay admin-modal" id="add-staff-modal">
        <div class="modal-content admin-modal-card admin-modal-sm">
            <div class="modal-header">
                <h3>Add Staff Account</h3>
                <button class="btn-close" type="button" data-close-modal="add-staff-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="add-staff-form">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="admin-modal-footer">
                        <button class="btn btn-primary" type="submit" style="flex:1;">Create Staff Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-overlay admin-modal" id="add-facilitator-user-modal">
        <div class="modal-content admin-modal-card admin-modal-sm">
            <div class="modal-header">
                <h3>Add Facilitator Account</h3>
                <button class="btn-close" type="button" data-close-modal="add-facilitator-user-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="add-facilitator-user-form">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="admin-modal-footer">
                        <button class="btn btn-primary" type="submit" style="flex:1;">Create Facilitator Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-overlay admin-modal" id="add-admin-modal">
        <div class="modal-content admin-modal-card admin-modal-sm">
            <div class="modal-header">
                <h3>Add Admin Account</h3>
                <button class="btn-close" type="button" data-close-modal="add-admin-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="add-admin-form">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="admin-modal-footer">
                        <button class="btn btn-primary" type="submit" style="flex:1;">Create Admin Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/admin.js"></script>
</body>
</html>
