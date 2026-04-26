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
            margin-top: 0.9rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.9rem;
            background: #f8fafc;
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

        <div class="register-panel" id="register-panel">
            <p class="register-disclaimer">If you are a library staff or library facilitator, please contact an admin to add your custom account.</p>
            <form id="register-request-form" novalidate>
                <div class="field">
                    <label for="reg-name">Full Name</label>
                    <input id="reg-name" type="text" required>
                </div>

                <div class="field">
                    <label for="reg-student-number">Student Number (optional)</label>
                    <input id="reg-student-number" type="text">
                </div>

                <div class="field">
                    <label for="reg-email">Email</label>
                    <input id="reg-email" type="email" required>
                </div>

                <div class="field">
                    <label for="reg-password">Password</label>
                    <input id="reg-password" type="password" required>
                </div>

                <div class="field">
                    <label for="reg-department">Department</label>
                    <select id="reg-department" required style="width: 100%; border: 1px solid var(--border); border-radius: 10px; padding: 0.75rem 0.85rem; font-family: inherit; font-size: 0.95rem; background: #fff;">
                        <option value="">Select department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= (int) $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="btn-submit" id="register-submit-btn" type="submit">Submit Request</button>
            </form>
            <div class="register-status" id="register-status"></div>
        </div>

        <p class="hint">Use your account from the system users table.</p>
    </div>

    <script>
        const toggleRegisterBtn = document.getElementById('toggle-register-panel');
        const registerPanel = document.getElementById('register-panel');
        const registerForm = document.getElementById('register-request-form');
        const registerStatus = document.getElementById('register-status');
        const registerSubmitBtn = document.getElementById('register-submit-btn');

        if (toggleRegisterBtn && registerPanel) {
            toggleRegisterBtn.addEventListener('click', () => {
                const isOpen = registerPanel.style.display === 'block';
                registerPanel.style.display = isOpen ? 'none' : 'block';
                toggleRegisterBtn.textContent = isOpen ? 'Create Account Request' : 'Hide Account Request Form';
            });
        }

        if (registerForm) {
            registerForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const payload = {
                    name: document.getElementById('reg-name')?.value.trim() || '',
                    student_number: document.getElementById('reg-student-number')?.value.trim() || '',
                    email: document.getElementById('reg-email')?.value.trim() || '',
                    password: document.getElementById('reg-password')?.value || '',
                    department_id: document.getElementById('reg-department')?.value || ''
                };

                if (!payload.name || !payload.email || !payload.password || !payload.department_id) {
                    registerStatus.textContent = 'Please complete all required fields.';
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
                    registerSubmitBtn.textContent = 'Submit Request';
                }
            });
        }
    </script>
</body>
</html>
