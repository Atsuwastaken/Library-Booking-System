<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    $sessionRole = strtolower((string) ($_SESSION['user_role'] ?? ''));
    if ($sessionRole === 'admin') {
        header('Location: admin.php');
        exit;
    }

    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/packages/core/BookingService.php';

$service = new BookingService();
$departments = $service->getDepartments();

$errorMessage = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($emailValue === '' || $password === '') {
        $errorMessage = 'Enter both email and password.';
    } else {
        $user = $service->authenticateUser($emailValue, $password);

        if ($user) {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_role'] = strtolower((string) ($user['role'] ?? 'student'));
            $_SESSION['facilitator_id'] = !empty($user['facilitator_id']) ? (int) $user['facilitator_id'] : null;

            if ($_SESSION['user_role'] === 'admin') {
                header('Location: admin.php');
                exit;
            }

            header('Location: index.php');
            exit;
        }

        $errorMessage = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Library Booking System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous">
    <style>
        :root {
            --bg: #f8fafc;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --border: #e2e8f0;
            --danger: #dc2626;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at 10% 20%, #dbeafe 0%, #f8fafc 40%, #f1f5f9 100%);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 1.2rem 4rem 1.2rem 1.2rem;
            color: var(--text);
        }

        .auth-shell {
            width: min(460px, 100%);
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
            padding: 2rem;
        }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.2rem;
        }

        .auth-brand img {
            width: 46px;
            height: 46px;
            object-fit: cover;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid var(--border);
            padding: 4px;
        }

        h1 {
            margin: 0;
            font-size: 1.35rem;
            letter-spacing: 0.01em;
        }

        .auth-subtitle {
            margin: 0.3rem 0 1.4rem;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .field {
            margin-bottom: 1rem;
        }

        .field label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .field input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.75rem 0.85rem;
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .field input:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn-submit {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 0.8rem 1rem;
            font-size: 0.95rem;
            font-weight: 700;
            background: var(--primary);
            color: #fff;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-submit:hover { background: var(--primary-dark); }

        .error-box {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: var(--danger);
            border-radius: 10px;
            padding: 0.7rem 0.8rem;
            font-size: 0.86rem;
            margin-bottom: 1rem;
        }

        .hint {
            margin-top: 1rem;
            font-size: 0.8rem;
            color: var(--muted);
            text-align: center;
        }

        .divider {
            margin: 1.2rem 0;
            border-top: 1px solid var(--border);
            position: relative;
        }

        .divider span {
            position: absolute;
            top: -0.55rem;
            left: 50%;
            transform: translateX(-50%);
            padding: 0 0.45rem;
            background: #fff;
            color: var(--muted);
            font-size: 0.75rem;
        }

        .btn-link {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.7rem 0.9rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1e293b;
            background: #fff;
            cursor: pointer;
        }

        .register-panel {
            display: none;
        }

        .register-status {
            margin-top: 0.6rem;
            font-size: 0.82rem;
            color: var(--muted);
        }

        .register-status.error {
            color: var(--danger);
        }

        .register-status.success {
            color: #15803d;
        }

        .register-disclaimer {
            margin: 0 0 0.9rem;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1e3a8a;
            border-radius: 10px;
            padding: 0.65rem 0.75rem;
            font-size: 0.8rem;
            line-height: 1.35;
        }

        /* Registration Modal Overlay */
        .register-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .register-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .register-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 0;
        }

        .register-modal {
            position: relative;
            z-index: 1;
            width: min(560px, 100%);
            max-height: 90vh;
            overflow-y: auto;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.18), 0 8px 20px rgba(15, 23, 42, 0.08);
            padding: 2rem;
            transform: translateY(20px) scale(0.97);
            transition: transform 0.3s ease;
        }

        .register-overlay.active .register-modal {
            transform: translateY(0) scale(1);
        }

        .register-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.2rem;
        }

        .register-modal-header h2 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.01em;
        }

        .register-modal-header p {
            margin: 0.2rem 0 0;
            color: var(--muted);
            font-size: 0.85rem;
        }

        .register-close-btn {
            background: none;
            border: 1px solid var(--border);
            border-radius: 8px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--muted);
            font-size: 1.2rem;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .register-close-btn:hover {
            background: #f1f5f9;
            color: var(--text);
            border-color: #94a3b8;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .field select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.75rem 0.85rem;
            font-family: inherit;
            font-size: 0.95rem;
            background: #fff;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            color: var(--text);
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.85rem center;
            padding-right: 2.5rem;
        }

        .field select:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .form-section-label {
            font-size: 0.72rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin: 0.6rem 0 0.5rem;
            padding-top: 0.6rem;
            border-top: 1px solid #f1f5f9;
        }

        .form-section-label:first-of-type {
            border-top: none;
            padding-top: 0;
            margin-top: 0;
        }

        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .register-modal {
                padding: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <div class="auth-brand">
            <img src="img/auf-logo.png" alt="AUF Logo">
            <h1>Library Booking Login</h1>
        </div>

        <p class="auth-subtitle">Sign in to access your dashboard and appointments.</p>

        <?php if ($errorMessage !== ''): ?>
            <div class="error-box"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" novalidate>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= htmlspecialchars($emailValue) ?>" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>

            <button class="btn-submit" type="submit">Sign In</button>
        </form>

        <div class="divider"><span>or</span></div>

        <button class="btn-link" id="toggle-register-panel" type="button">Create Account Request</button>

        <p class="hint">Use your account from the system users table.</p>
    </div>

    <!-- Registration Modal Overlay -->
    <div class="register-overlay" id="register-overlay">
        <div class="register-backdrop" id="register-backdrop"></div>
        <div class="register-modal">
            <div class="register-modal-header">
                <div>
                    <h2>Student Account Request</h2>
                    <p>Fill out the form below to request access</p>
                </div>
                <button class="register-close-btn" id="register-close-btn" type="button">&times;</button>
            </div>

            <p class="register-disclaimer">If you are a library staff or library facilitator, please contact an admin to add your custom account.</p>

            <form id="register-request-form" novalidate>
                <div class="form-section-label">Personal Information</div>

                <div class="field">
                    <label for="reg-name">Full Name <span style="color: var(--danger);">*</span></label>
                    <input id="reg-name" type="text" placeholder="e.g. Juan Dela Cruz" required>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="reg-email">Email <span style="color: var(--danger);">*</span></label>
                        <input id="reg-email" type="email" placeholder="you@email.com" required>
                    </div>
                    <div class="field">
                        <label for="reg-password">Password <span style="color: var(--danger);">*</span></label>
                        <input id="reg-password" type="password" placeholder="Create a password" required>
                    </div>
                </div>

                <div class="form-section-label">Academic Information</div>

                <div class="form-row">
                    <div class="field">
                        <label for="reg-student-number">Student ID</label>
                        <input id="reg-student-number" type="text" placeholder="e.g. 24-1021-948">
                    </div>
                    <div class="field">
                        <label for="reg-year-level">Year Level</label>
                        <select id="reg-year-level">
                            <option value="">Select year level</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                            <option value="Graduate">Graduate</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="reg-course">Course</label>
                        <input id="reg-course" type="text" placeholder="e.g. BSIT, BSCS, BSN">
                    </div>
                    <div class="field">
                        <label for="reg-program">Program</label>
                        <input id="reg-program" type="text" placeholder="e.g. Information Technology">
                    </div>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="reg-section">Section</label>
                        <input id="reg-section" type="text" placeholder="e.g. A, B, C">
                    </div>
                    <div class="field">
                        <label for="reg-department">Department <span style="color: var(--danger);">*</span></label>
                        <select id="reg-department" required>
                            <option value="">Select department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= (int) $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button class="btn-submit" id="register-submit-btn" type="submit" style="margin-top: 0.5rem;">Submit Account Request</button>
            </form>
            <div class="register-status" id="register-status"></div>
        </div>
    </div>

    <script>
        const toggleRegisterBtn = document.getElementById('toggle-register-panel');
        const registerOverlay = document.getElementById('register-overlay');
        const registerBackdrop = document.getElementById('register-backdrop');
        const registerCloseBtn = document.getElementById('register-close-btn');
        const registerForm = document.getElementById('register-request-form');
        const registerStatus = document.getElementById('register-status');
        const registerSubmitBtn = document.getElementById('register-submit-btn');

        function openRegisterModal() {
            if (registerOverlay) registerOverlay.classList.add('active');
        }

        function closeRegisterModal() {
            if (registerOverlay) registerOverlay.classList.remove('active');
        }

        if (toggleRegisterBtn) {
            toggleRegisterBtn.addEventListener('click', openRegisterModal);
        }

        if (registerCloseBtn) {
            registerCloseBtn.addEventListener('click', closeRegisterModal);
        }

        if (registerBackdrop) {
            registerBackdrop.addEventListener('click', closeRegisterModal);
        }

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeRegisterModal();
        });

        if (registerForm) {
            registerForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const payload = {
                    name: document.getElementById('reg-name')?.value.trim() || '',
                    student_number: document.getElementById('reg-student-number')?.value.trim() || '',
                    email: document.getElementById('reg-email')?.value.trim() || '',
                    password: document.getElementById('reg-password')?.value || '',
                    department_id: document.getElementById('reg-department')?.value || '',
                    year_level: document.getElementById('reg-year-level')?.value || '',
                    course: document.getElementById('reg-course')?.value.trim() || '',
                    program: document.getElementById('reg-program')?.value.trim() || '',
                    section: document.getElementById('reg-section')?.value.trim() || ''
                };

                if (!payload.name || !payload.email || !payload.password || !payload.department_id) {
                    registerStatus.textContent = 'Please complete all required fields (Name, Email, Password, Department).';
                    registerStatus.className = 'register-status error';
                    return;
                }

                registerSubmitBtn.disabled = true;
                registerSubmitBtn.textContent = 'Submitting...';
                registerStatus.textContent = '';
                registerStatus.className = 'register-status';

                try {
                    const res = await fetch('api.php?action=submit_registration', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();

                    if (data.success) {
                        registerStatus.textContent = data.message || 'Registration request submitted.';
                        registerStatus.className = 'register-status success';
                        registerForm.reset();
                    } else {
                        registerStatus.textContent = data.message || 'Failed to submit registration request.';
                        registerStatus.className = 'register-status error';
                    }
                } catch (error) {
                    registerStatus.textContent = 'Failed to submit registration request.';
                    registerStatus.className = 'register-status error';
                } finally {
                    registerSubmitBtn.disabled = false;
                    registerSubmitBtn.textContent = 'Submit Account Request';
                }
            });
        }
    </script>
</body>
</html>
