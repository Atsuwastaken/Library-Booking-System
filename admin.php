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
        body {
            background: #f8fafc;
            margin: 0;
            padding: 0;
        }

        .admin-layout {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

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

        .nav-link:hover {
            color: #1e293b;
        }

        .nav-link.active {
            color: #6366f1;
            border-bottom: 3px solid #6366f1;
        }

        /* Main Workspace */
        .admin-main {
            flex: 1;
            padding: 2.5rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        .admin-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            margin-bottom: 2.5rem;
        }

        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .admin-table th {
            text-align: left;
            padding: 1.25rem 1rem;
            border-bottom: 2px solid #f1f5f9;
            color: #64748b;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 800;
        }

        .admin-table td {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            font-size: 0.9rem;
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table tr:hover td {
            background: #f8fafc;
        }

        .action-btns {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: toastIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 600;
        }

        @keyframes toastIn {
            from {
                transform: translateX(100%) scale(0.5);
                opacity: 0;
            }

            to {
                transform: translateX(0) scale(1);
                opacity: 1;
            }
        }

        /* Admin Modal System — use pointer-events pattern (not display:none)
           so that opacity transitions work correctly */
        .admin-modal,
        .modal-overlay {
            position: fixed;
            inset: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .admin-modal.active,
        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .admin-modal .modal-content,
        .modal-overlay .modal-content {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 20px;
            box-shadow: 0 26px 60px rgba(15, 23, 42, 0.22);
            overflow: hidden;
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

        .admin-modal form {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
        }

        .admin-modal .modal-body {
            padding: 1.5rem 1.25rem;
            overflow-y: auto;
            background: transparent;
            flex: 1;
            min-height: 0;
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
            flex-wrap: wrap;
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

        .facilitator-indicator-badge {
            display: inline-flex;
            align-items: center;
            background: #eff6ff;
            color: #2563eb;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            border: 1px solid #dbeafe;
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

        /* Admin-only card sub-components (not in style.css) */
        .app-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.25rem;
        }

        .admin-main .app-card-type {
            font-size: 0.7rem;
            font-weight: 800;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #eef2ff;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
        }

        .app-card-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 0.75rem 0;
            border-top: 1px solid #f1f5f9;
        }

        .info-item {
            font-size: 0.82rem;
            color: #64748b;
            display: flex;
            gap: 0.5rem;
            align-items: baseline;
        }

        .info-item strong {
            color: #475569;
            font-weight: 700;
            min-width: 85px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .info-value {
            color: #1e293b;
            flex: 1;
        }

        .app-card-notes {
            font-size: 0.8rem;
            color: #64748b;
            background: #f8fafc;
            padding: 0.75rem;
            border-radius: 8px;
            font-style: italic;
            border-left: 3px solid #e2e8f0;
            margin-top: 0.25rem;
        }

        .app-card-actions {
            margin-top: auto;
            padding-top: 0.5rem;
        }

        /* Prompt spinner for async save buttons */
        .prompt-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spinAnim 0.6s linear infinite;
            vertical-align: middle;
            margin-right: 0.3rem;
        }

        @keyframes spinAnim {
            to {
                transform: rotate(360deg);
            }
        }

        /* Unified Action Buttons Style */
        .action-btns .btn {
            padding: 0.4rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s;
        }

        .action-btns .btn-outline {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
        }

        .action-btns .btn-outline:hover {
            border-color: #6366f1;
            color: #6366f1;
            background: #f5f3ff;
        }

        .action-btns .btn-danger {
            background: #fff;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .action-btns .btn-danger:hover {
            background: #fef2f2;
            border-color: #f87171;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.08);
        }

        @media (max-width: 640px) {
            .admin-modal .modal-body {
                padding: 0.95rem;
            }

            .admin-modal-footer {
                flex-direction: column;
            }
        }

        /* Admin Calendar Styles */
        .calendar-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .calendar-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .calendar-title-group h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
        }

        .calendar-controls {
            display: flex;
            gap: 0.5rem;
        }

        .calendar-days-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: 700;
            color: #64748b;
            font-size: 0.8rem;
            text-transform: uppercase;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #f1f5f9;
            border: 1px solid #f1f5f9;
            margin-top: 1px;
        }

        .calendar-day {
            background: #ffffff;
            min-height: 100px;
            padding: 0.5rem;
            transition: all 0.2s;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .calendar-day:hover {
            background: #f8fafc;
        }

        .calendar-day.today {
            background: #f0f9ff;
        }

        .calendar-day.today .day-num {
            background: #6366f1;
            color: #fff;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }

        .calendar-day.off-day {
            background: #fff7ed;
        }

        .day-num {
            font-size: 0.9rem;
            font-weight: 700;
            color: #475569;
        }

        .day-content {
            display: flex;
            flex-direction: column;
            gap: 2px;
            overflow: hidden;
        }

        .off-day-label {
            font-size: 0.7rem;
            background: #ffedd5;
            color: #9a3412;
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .calendar-app-tag {
            font-size: 0.65rem;
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .calendar-app-tag.pending {
            background: #fef9c3;
            color: #854d0e;
        }

        .calendar-app-tag.confirmed {
            background: #dcfce7;
            color: #166534;
        }

        .calendar-app-tag.completed {
            background: #f0f9ff;
            color: #075985;
        }

        .calendar-app-tag.cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .calendar-app-tag.declined {
            background: #f1f5f9;
            color: #475569;
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
                <!-- <a href="#" class="nav-link" data-tab="seminars">Seminars & Events</a> -->
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
                            <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Click any date to book an
                                appointment or mark it as an off-day.</p>
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
                    <div class="calendar-legend"
                        style="margin-top: 0.8rem; border-top: 1px solid var(--border); padding-top: 0.8rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <div class="legend-item"
                            style="font-size: 0.75rem; gap: 0.4rem; display: flex; align-items: center;"><span
                                class="dot dot-booked"
                                style="width: 8px; height: 8px; background: var(--secondary); border-radius: 50%;"></span>
                            Booked</div>
                        <div class="legend-item"
                            style="font-size: 0.75rem; gap: 0.4rem; display: flex; align-items: center;"><span
                                class="dot dot-offday"
                                style="width: 8px; height: 8px; background: #f97316; border-radius: 50%;"></span>
                            Off-Day</div>
                        <div class="legend-item"
                            style="font-size: 0.75rem; gap: 0.4rem; display: flex; align-items: center;"><span
                                class="dot dot-closed"
                                style="width: 8px; height: 8px; background: #64748b; border-radius: 50%;"></span> Closed
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Pending Requests -->
            <div id="tab-requests" class="admin-tab-content">
                <div class="appointments-head">
                    <h2>All Appointments</h2>
                    <p id="requests-summary">Showing all records</p>
                    <div class="appointments-subtabs" id="admin-appointments-subtabs"
                        style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                        <button class="btn btn-primary btn-sm appointment-subtab-btn active"
                            data-view="all">All</button>
                        <button class="btn btn-outline btn-sm appointment-subtab-btn"
                            data-view="archived">Archived</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <div class="filter-controls-group">
                        <div class="filter-group">
                            <label>Requestor</label>
                            <select class="filter-select" id="filter-requestor">
                                <option value="all">All Requestors</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Department</label>
                            <select class="filter-select" id="filter-department">
                                <option value="all">All Departments</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Instructor</label>
                            <select class="filter-select" id="filter-facilitator">
                                <option value="all">All Instructors</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Status</label>
                            <select class="filter-select" id="filter-status">
                                <option value="all">All Statuses</option>
                                <option value="PENDING">Pending</option>
                                <option value="CONFIRMED">Confirmed</option>
                                <option value="COMPLETED">Completed</option>
                                <option value="CANCELLED">Cancelled</option>
                                <option value="DECLINED">Declined</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select class="filter-select" id="filter-datetime">
                                <option value="newest">Newest Created</option>
                                <option value="oldest">Oldest Created</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Date</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <input class="filter-select" type="date" id="filter-date">
                                <button class="btn btn-outline btn-sm" id="filter-date-today" type="button"
                                    style="padding: 0 0.75rem;">Today</button>
                            </div>
                        </div>
                    </div>
                    <div class="filter-actions"
                        style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <div>
                            <button class="btn btn-outline btn-sm" id="reset-request-filters" type="button">Reset
                                Filters</button>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <label class="checkbox-container" style="margin-right: 10px; display: none;"
                                id="select-all-sessions-container">
                                <input type="checkbox" id="select-all-sessions"> <span id="select-all-label">Select
                                    All</span>
                            </label>
                            <button class="btn btn-outline-danger btn-sm" id="archive-selected-sessions-btn"
                                type="button" style="display:none;"><span id="archive-selected-text">Archive
                                    Selected</span> (<span id="selected-session-count">0</span>)</button>
                            <button class="btn btn-primary btn-sm" id="export-logs-btn" type="button">Export
                                Logs</button>
                            <button class="btn btn-primary btn-sm" id="export-sessions-btn" type="button">Export
                                Sessions</button>
                        </div>
                    </div>
                </div>

                <div id="requests-grid" class="app-grid">
                    <!-- JS populated cards -->
                </div>
            </div>

            <!-- Tab: Seminars Management (Hidden)
            <div id="tab-seminars" class="admin-tab-content" style="display: none;">
                ...
            </div> -->

            <!-- Tab: Topics Management -->
            <div id="tab-topics" class="admin-tab-content" style="display: none;">
                <div class="admin-card">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
                        <h3>Topic Catalog</h3>
                        <button class="btn btn-primary btn-sm" onclick="openTopicModal()">+ Add New Topic</button>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <input type="text" id="topics-search" class="form-control"
                            placeholder="Search topic name or department coverage...">
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
                        <button class="btn btn-primary btn-sm" onclick="openFacilitatorModal()">+ Add New
                            Instructor</button>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <input type="text" id="facilitators-search" class="form-control"
                            placeholder="Search facilitator, position, or department...">
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
                            <h3 style="margin: 0;">Users Directory</h3>
                            <p class="users-admin-subtitle">Maintain existing accounts, adjust privileges, and link
                                facilitator profiles.</p>
                        </div>
                        <div class="users-admin-counts">
                            <span class="users-admin-chip" id="users-total-count">0 users</span>
                        </div>
                    </div>
                    <div class="users-header"
                        style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding: 0 20px;">
                        <input type="text" id="users-admin-search" class="form-control" placeholder="Search users..."
                            style="width:300px;">
                        <div style="display:flex; gap:10px; align-items:center;">
                            <button id="export-users-csv-btn" class="btn btn-secondary btn-sm">Export Users
                                (CSV)</button>
                            <button id="import-users-csv-btn" class="btn btn-secondary btn-sm">Import Users
                                (CSV)</button>
                            <button id="delete-all-users-btn" class="btn btn-danger btn-sm">
                                <span id="delete-btn-text">Delete All Users</span> (<span
                                    id="selected-user-count">0</span>)
                            </button>
                        </div>
                    </div>
                    <div class="users-directory-tabs" id="users-directory-tabs">
                        <button type="button" class="users-directory-tab active"
                            data-users-pane="general">General</button>
                        <button type="button" class="users-directory-tab" data-users-pane="staff">Staff</button>
                        <button type="button" class="users-directory-tab"
                            data-users-pane="facilitators">Facilitators</button>
                        <button type="button" class="users-directory-tab" data-users-pane="admins">Admins</button>
                    </div>

                    <div class="users-directory-pane active" id="users-pane-general">
                        <div class="users-pane-head" style="display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" class="select-all-users-checkbox" data-pane="general">
                            <p class="users-pane-note" style="margin:0;">General users include students and non-students
                                who can book appointments.</p>
                            <button class="btn btn-primary btn-sm" type="button" id="open-add-general-modal">+ Add
                                General User</button>
                        </div>
                        <div class="users-compact-list" id="users-admin-general-list">
                            <div style="padding: 20px; text-align: center; color: #94a3b8;">Loading general users...
                            </div>
                        </div>
                    </div>

                    <div class="users-directory-pane" id="users-pane-staff">
                        <div class="users-pane-head" style="display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" class="select-all-users-checkbox" data-pane="staff">
                            <p class="users-pane-note" style="margin:0;">Staff members can manage departments and
                                appointments.</p>
                            <button class="btn btn-primary btn-sm" type="button" id="open-add-staff-modal">+ Add
                                Staff</button>
                        </div>
                        <div class="users-compact-list" id="users-admin-staff-list">
                            <div style="padding: 20px; text-align: center; color: #94a3b8;">Loading staff...</div>
                        </div>
                    </div>

                    <div class="users-directory-pane" id="users-pane-facilitators">
                        <div class="users-pane-head" style="display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" class="select-all-users-checkbox" data-pane="facilitators">
                            <p class="users-pane-note" style="margin:0;">Facilitators are linked to specific topics and
                                departments. Link any user to a facilitator via the <strong>Edit User</strong> panel.
                            </p>
                        </div>
                        <div class="users-compact-list" id="users-admin-facilitators-list">
                            <div style="padding: 20px; text-align: center; color: #94a3b8;">Loading facilitators...
                            </div>
                        </div>
                    </div>

                    <div class="users-directory-pane" id="users-pane-admins">
                        <div class="users-pane-head"
                            style="display:flex; align-items:center; gap:10px; justify-content: flex-end;">
                            <input type="checkbox" class="select-all-users-checkbox" data-pane="admins">
                            <button class="btn btn-primary btn-sm" type="button" id="open-add-admin-modal">+ Add
                                Admin</button>
                        </div>
                        <div class="users-compact-list" id="users-admin-admins-list">
                            <div style="padding: 20px; text-align: center; color: #94a3b8;">Loading admins...</div>
                        </div>
                    </div>
                </div>

                <!-- Pending Registration Requests Section -->
                <div class="admin-card" style="margin-top: 2rem;">
                    <h3>Pending Registration Requests</h3>
                    <div class="users-admin-table-wrap">
                        <table class="admin-table users-admin-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Student No.</th>
                                    <th>Department</th>
                                    <th>Role & Details</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="registration-requests-tbody">
                                <tr>
                                    <td colspan="6" style="padding: 1rem; color: #94a3b8;">Loading registration
                                        requests...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal-overlay admin-modal" id="edit-user-modal">
        <div class="modal-content admin-modal-card admin-modal-md">
            <div class="modal-header">
                <h3>Edit User Profile</h3>
                <button class="btn-close" type="button" data-close-modal>&times;</button>
            </div>
            <form id="edit-user-form">
                <div class="modal-body">
                    <input type="hidden" id="edit-user-id" name="id">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" id="edit-user-name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="edit-user-email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select id="edit-user-role" name="role" class="form-control">
                                <option value="general">General</option>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Link to Facilitator</label>
                            <select id="edit-user-facilitator" name="facilitator_id" class="form-control">
                                <option value="">None (no facilitator privileges)</option>
                                <!-- Populated via JS -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Student Number</label>
                            <input type="text" id="edit-user-student-number" name="student_number" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>User Type</label>
                            <select id="edit-user-type" name="user_type" class="form-control">
                                <option value="student">Student</option>
                                <option value="non-student">Non-Student</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <select id="edit-user-department" name="department_id" class="form-control">
                                <option value="">N/A</option>
                                <?php foreach ($service->getDepartments() as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" id="edit-user-program-group">
                            <label>Program</label>
                            <select id="edit-user-program" name="course_program" class="form-control">
                                <option value="">N/A</option>
                            </select>
                        </div>
                        <div class="form-group" id="edit-user-year-level-group">
                            <label>Year Level</label>
                            <select id="edit-user-year-level" name="year_level" class="form-control">
                                <option value="">N/A</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="5th Year">5th Year</option>
                            </select>
                        </div>
                        <div class="form-group" id="edit-user-status-group">
                            <label>Status</label>
                            <select id="edit-user-status" name="enrollment_status" class="form-control">
                                <option value="">N/A</option>
                                <option value="Regular">Regular</option>
                                <option value="Irregular">Irregular</option>
                            </select>
                        </div>

                        <div class="form-group"
                            style="grid-column: span 2; margin-top: 10px; border-top: 1px solid #eee; padding-top: 15px;">
                            <label style="color: #64748b; font-size: 0.8rem;">Security</label>
                            <label>New Password (leave blank to keep current)</label>
                            <input type="password" id="edit-user-password" name="password" class="form-control"
                                autocomplete="new-password">
                        </div>
                    </div> <!-- End of grid -->
                </div> <!-- End of modal-body -->
                <div class="admin-modal-footer">
                    <button class="btn btn-secondary" type="button" data-close-modal>Cancel</button>
                    <button class="btn btn-primary" type="submit" style="min-width: 120px;">Save Changes</button>
                </div>
            </form>
        </div> <!-- End of modal-content -->
    </div> <!-- End of modal-overlay -->

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

    <div class="modal-overlay admin-modal" id="add-general-modal">
        <div class="modal-content admin-modal-card admin-modal-md">
            <div class="modal-header">
                <h3>Add General Account</h3>
                <button class="btn-close" type="button" data-close-modal="add-general-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="add-general-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>Student Number</label>
                            <input type="text" class="form-control" name="student_number">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Department</label>
                            <select class="form-control" name="department_id" id="add-general-dept">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= (int) $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>User Type</label>
                            <select class="form-control" name="user_type" id="add-general-user-type">
                                <option value="student">Student</option>
                                <option value="non-student" selected>Non-Student</option>
                            </select>
                        </div>
                    </div>
                    <div id="add-general-student-fields"
                        style="display: none; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                        <div class="form-group" id="add-general-program-group">
                            <label>Program</label>
                            <select class="form-control" name="course_program" id="add-general-program">
                                <option value="">Select Program</option>
                            </select>
                        </div>
                        <div class="form-group" id="add-general-year-level-group">
                            <label>Year Level</label>
                            <select class="form-control" name="year_level" id="add-general-year-level">
                                <option value="">Select Year</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="5th Year">5th Year</option>
                            </select>
                        </div>
                        <div class="form-group" id="add-general-status-group">
                            <label>Status</label>
                            <select class="form-control" name="enrollment_status" id="add-general-status">
                                <option value="Regular">Regular</option>
                                <option value="Irregular">Irregular</option>
                            </select>
                        </div>

                    </div>
                    <div class="admin-modal-footer">
                        <button class="btn btn-primary" type="submit" style="flex:1;">Create General Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                    <div class="form-group">
                        <label>Link to Facilitator</label>
                        <select class="form-control facilitator-link-dropdown" name="facilitator_id">
                            <option value="">None (no facilitator privileges)</option>
                        </select>
                    </div>
                    <div class="admin-modal-footer">
                        <button class="btn btn-primary" type="submit" style="flex:1;">Create Staff Account</button>
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
                    <div class="form-group">
                        <label>Link to Facilitator</label>
                        <select class="form-control facilitator-link-dropdown" name="facilitator_id">
                            <option value="">None (no facilitator privileges)</option>
                        </select>
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