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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" crossorigin="anonymous">
    <style>
        :root {
            --bg: #f0f4fa;
            --panel: rgba(255, 255, 255, 0.75);
            --panel-solid: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-glow: rgba(37, 99, 235, 0.25);
            --border: rgba(226, 232, 240, 0.6);
            --danger: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        /* === BODY & BACKGROUND === */
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            background: #e8eef7;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 1.2rem 6rem 1.2rem 1.2rem;
            color: var(--text);
            overflow: hidden;
            position: relative;
        }

        /* Animated gradient blobs */
        .bg-blobs {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .bg-blobs .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.5;
            will-change: transform;
        }

        .blob-1 {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, #7fabe0ff 0%, transparent 100%);
            top: -10%;
            left: -5%;
            animation: blobFloat1 18s ease-in-out infinite;
        }

        .blob-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #7fabe0ff 0%, transparent 100%);
            bottom: -15%;
            right: 10%;
            animation: blobFloat2 22s ease-in-out infinite;
        }

        .blob-3 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, #7fabe0ff 0%, transparent 100%);
            top: 40%;
            left: 35%;
            animation: blobFloat3 15s ease-in-out infinite;
        }

        .blob-4 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, #7fabe0ff 0%, transparent 100%);
            top: 10%;
            right: 25%;
            animation: blobFloat4 20s ease-in-out infinite;
        }

        @keyframes blobFloat1 {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            33% {
                transform: translate(80px, 60px) scale(1.08);
            }

            66% {
                transform: translate(-40px, 30px) scale(0.95);
            }
        }

        @keyframes blobFloat2 {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            33% {
                transform: translate(-60px, -50px) scale(1.1);
            }

            66% {
                transform: translate(50px, -20px) scale(0.92);
            }
        }

        @keyframes blobFloat3 {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(60px, -40px) scale(1.12);
            }
        }

        @keyframes blobFloat4 {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            40% {
                transform: translate(-50px, 50px) scale(1.05);
            }

            80% {
                transform: translate(30px, -30px) scale(0.96);
            }
        }

        /* Subtle grid overlay */
        .bg-grid {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(48, 57, 68, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(48, 57, 68, 0.06) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        /* === AUTH CARD === */
        .auth-shell {
            width: min(460px, 100%);
            background: var(--panel);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow:
                0 24px 48px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(255, 255, 255, 0.6) inset;
            padding: 2.2rem 2rem;
            position: relative;
            z-index: 10;
            transform-style: preserve-3d;
            transition: transform 0.15s ease, box-shadow 0.3s ease;
            animation: cardEntrance 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(30px) rotateX(4deg);
            }

            to {
                opacity: 1;
                transform: translateY(0) rotateX(0deg);
            }
        }

        .auth-shell:hover {
            box-shadow:
                0 30px 60px rgba(15, 23, 42, 0.12),
                0 0 0 1px rgba(255, 255, 255, 0.6) inset;
        }

        /* Shine sweep on card */
        .auth-shell::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent 0%,
                    rgba(255, 255, 255, 0.15) 50%,
                    transparent 100%);
            border-radius: 20px;
            pointer-events: none;
            animation: shineSweep 4s ease-in-out infinite;
            animation-delay: 1.5s;
        }

        @keyframes shineSweep {
            0% {
                left: -100%;
            }

            30% {
                left: 100%;
            }

            100% {
                left: 100%;
            }
        }

        /* === BRAND === */
        .auth-brand {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            margin-bottom: 1.2rem;
        }

        .auth-brand img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid rgba(37, 99, 235, 0.15);
            padding: 3px;
            animation: logoFloat 4s ease-in-out infinite;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }

        .auth-brand img:hover {
            transform: scale(1.1) rotate(5deg);
            border-color: var(--primary);
        }

        @keyframes logoFloat {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-4px);
            }
        }

        h1 {
            margin: 0;
            font-size: 1.35rem;
            letter-spacing: 0.01em;
            background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .auth-subtitle {
            margin: 0.3rem 0 1.6rem;
            color: var(--muted);
            font-size: 0.92rem;
        }

        /* === FORM FIELDS === */
        .field {
            margin-bottom: 1.1rem;
            position: relative;
        }

        .field label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--muted);
            transition: color 0.2s ease;
        }

        .field:focus-within label {
            color: var(--primary);
        }

        .field input {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 0.78rem 0.9rem;
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            background: rgba(255, 255, 255, 0.7);
            transition: border-color 0.25s ease, box-shadow 0.25s ease, background 0.25s ease, transform 0.15s ease;
        }

        .field input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3.5px var(--primary-glow);
            background: #fff;
            transform: translateY(-1px);
        }

        .field input:hover:not(:focus) {
            border-color: #94a3b8;
        }

        /* === BUTTONS === */
        .btn-submit {
            width: 100%;
            border: none;
            border-radius: 12px;
            padding: 0.85rem 1rem;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: inherit;
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%);
            color: #fff;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: transform 0.15s ease, box-shadow 0.25s ease;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0) scale(0.98);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        }

        /* Button shimmer */
        .btn-submit::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 60%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, 0.2),
                    transparent);
            transition: left 0.6s ease;
        }

        .btn-submit:hover::after {
            left: 120%;
        }

        .error-box {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: var(--danger);
            border-radius: 12px;
            padding: 0.7rem 0.8rem;
            font-size: 0.86rem;
            margin-bottom: 1rem;
            animation: shakeIn 0.4s ease;
        }

        @keyframes shakeIn {
            0% {
                transform: translateX(0);
            }

            20% {
                transform: translateX(-8px);
            }

            40% {
                transform: translateX(6px);
            }

            60% {
                transform: translateX(-4px);
            }

            80% {
                transform: translateX(2px);
            }

            100% {
                transform: translateX(0);
            }
        }

        .hint {
            margin-top: 1.2rem;
            font-size: 0.8rem;
            color: var(--muted);
            text-align: center;
        }

        .divider {
            margin: 1.4rem 0;
            border: none;
            border-top: 1px solid var(--border);
            position: relative;
        }

        .divider span {
            position: absolute;
            top: -0.55rem;
            left: 50%;
            transform: translateX(-50%);
            padding: 0 0.5rem;
            background: var(--panel-solid);
            color: var(--muted);
            font-size: 0.75rem;
            font-weight: 500;
        }

        .btn-link {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 0.72rem 0.9rem;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: inherit;
            color: #1e293b;
            background: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
        }

        .btn-link:hover {
            background: rgba(255, 255, 255, 0.9);
            border-color: #93c5fd;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
        }

        .btn-link:active {
            transform: translateY(0) scale(0.98);
        }

        .register-panel {
            margin-top: 0.9rem;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1rem;
            background: rgba(248, 250, 252, 0.8);
            display: none;
            animation: panelSlide 0.3s ease;
        }

        @keyframes panelSlide {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            border-radius: 12px;
            padding: 0.65rem 0.75rem;
            font-size: 0.8rem;
            line-height: 1.35;
        }

        /* Stagger entrance for fields */
        .auth-shell .field:nth-child(1) {
            animation: fieldFade 0.5s 0.15s both;
        }

        .auth-shell .field:nth-child(2) {
            animation: fieldFade 0.5s 0.25s both;
        }

        .auth-shell .btn-submit {
            animation: fieldFade 0.5s 0.35s both;
        }

        .auth-shell .divider {
            animation: fieldFade 0.5s 0.4s both;
        }

        .auth-shell .btn-link {
            animation: fieldFade 0.5s 0.45s both;
        }

        @keyframes fieldFade {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <!-- Animated background -->
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="blob blob-4"></div>
    </div>
    <div class="bg-grid"></div>

    <div class="auth-shell" id="auth-shell">
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
            <p class="register-disclaimer">If you are a library staff or library facilitator, please contact an admin to
                add your custom account.</p>
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
                    <select id="reg-department" required
                        style="width: 100%; border: 1.5px solid var(--border); border-radius: 12px; padding: 0.78rem 0.9rem; font-family: inherit; font-size: 0.95rem; background: rgba(255,255,255,0.7); outline: none; transition: border-color 0.25s ease, box-shadow 0.25s ease;">
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

        // 3D tilt effect on card
        (function () {
            const card = document.getElementById('auth-shell');
            if (!card) return;

            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const rotateX = ((y - centerY) / centerY) * -3;
                const rotateY = ((x - centerX) / centerX) * 3;

                card.style.transform = `perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(800px) rotateX(0deg) rotateY(0deg)';
            });
        })();
    </script>
</body>

</html>