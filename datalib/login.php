<!-- <?php
session_start();
require_once __DIR__ . '/packages/core/Database.php';

$error = null;

function verifyEmailViaSMTP($email)
{
    // Stage 1: DNS MX Record Lookup
    list($user, $domain) = explode('@', $email);
    if (!checkdnsrr($domain, 'MX')) {
        return false;
    }

    // Retrieve the MX records
    getmxrr($domain, $mxHosts, $mxWeights);
    if (empty($mxHosts))
        return false;

    // Sort by priority/weight
    array_multisort($mxWeights, $mxHosts);
    $primaryMx = $mxHosts[0];

    // Stage 2: Attempt SMTP Handshake "Ping" to Verify User Existence
    $connectTimeout = 5;
    $sock = @fsockopen($primaryMx, 25, $errno, $errstr, $connectTimeout);
    if (!$sock)
        return false;

    stream_set_timeout($sock, 5);

    function smtp_get_line($sock)
    {
        $res = "";
        while (($line = fgets($sock, 515)) !== false) {
            $res .= $line;
            if (substr($line, 3, 1) == " ")
                break;
        }
        return $res;
    }

    $reply = smtp_get_line($sock);
    if (!preg_match('/^220/', $reply)) {
        fclose($sock);
        return false;
    }

    // Say HELO
    fwrite($sock, "HELO datalib.local\r\n");
    $reply = smtp_get_line($sock);
    if (!preg_match('/^250/', $reply)) {
        fclose($sock);
        return false;
    }

    // MAIL FROM (we use a generic fallback valid string for the ping)
    fwrite($sock, "MAIL FROM: <verify@datalib.local>\r\n");
    $reply = smtp_get_line($sock);
    if (!preg_match('/^250/', $reply)) {
        fclose($sock);
        return false;
    }

    // RCPT TO (This is the crucial step - asking if the specific user mailbox exists)
    fwrite($sock, "RCPT TO: <$email>\r\n");
    $reply = smtp_get_line($sock);

    // Send standard QUIT before disconnecting gracefully to avoid blacklisting
    fwrite($sock, "QUIT\r\n");
    fclose($sock);

    // A 250 response indicates the recipient exists or the server accepted the routing
    // A 550 response means mailbox unavailable/not found
    if (preg_match('/^250/', $reply)) {
        return true;
    }

    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (filter_var($email, FILTER_VALIDATE_EMAIL) && str_ends_with(strtolower($email), '@student.auf.edu.ph')) {

        // --- Execute DNS & SMTP Verification ---
        $isEmailReal = verifyEmailViaSMTP($email);

        if ($isEmailReal) {
            $db = (new Database())->getPdo();

            $stmt = $db->prepare("SELECT id, name, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['student_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: index.php');
                exit;
            } else {
                // MVP: Register automatically if a new VALID email is proven
                $stmt = $db->prepare("INSERT INTO users (student_number, name, email, role) VALUES ('Verified', 'New Student', ?, 'Student')");
                $stmt->execute([$email]);
                $_SESSION['user_id'] = $db->lastInsertId();
                $_SESSION['student_email'] = $email;
                $_SESSION['user_name'] = 'New Student';
                header('Location: index.php');
                exit;
            }
        } else {
            $error = "Verification failed. The specified @student.auf.edu.ph mailbox does not seem to exist or is unreachable via SMTP records.";
        }
    } else {
        $error = "Invalid format. Please enter a valid @student.auf.edu.ph address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Authentication | Schedulator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--bg-color);
            padding: 1rem;
        }

        .login-card {
            background: var(--panel-bg);
            padding: 3.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            width: 100%;
            max-width: 480px;
            text-align: center;
            border: 1px solid var(--border);
        }

        .login-card h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
            font-weight: 800;
        }

        .login-card p {
            color: var(--text-secondary);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            text-align: left;
        }

        .login-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1.15rem;
            font-family: inherit;
            letter-spacing: 0.1em;
            text-align: center;
            transition: all 0.2s ease;
        }

        .login-input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(47, 129, 247, 0.2);
        }

        .login-input::placeholder {
            letter-spacing: normal;
            opacity: 0.5;
        }

        .error-msg {
            background: #fef2f2;
            color: var(--danger);
            border: 1px solid #fca5a5;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: -0.5rem;
            text-align: center;
        }

        .btn-google {
            background-color: #fff;
            color: #3c4043;
            border: 1px solid #dadce0;
        }

        .btn-google:hover {
            background-color: #f8f9fa;
            border-color: #d2e3fc;
            box-shadow: 0 1px 2px 0 rgba(60, 64, 67, 0.3), 0 1px 3px 1px rgba(60, 64, 67, 0.15);
            transform: translateY(-1px);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border);
        }

        .divider:not(:empty)::before {
            margin-right: 1em;
        }

        .divider:not(:empty)::after {
            margin-left: 1em;
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card">
            <img src="img/auf-logo.png" alt="AUF Logo" style="height: 80px; margin-bottom: 1.5rem;">
            <h1>Student Access</h1>
            <p>Please enter your authorized email address to securely enter the global scheduling ecosystem.</p>

            <form method="POST" class="login-form">
                <?php if ($error): ?>
<div class="error-msg"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<div>
    <input type="email" name="email" class="login-input" placeholder="example@student.auf.edu.ph"
        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required pattern=".+@student\.auf\.edu\.ph$"
        title="Please provide a valid @student.auf.edu.ph address">
</div>
<button type="submit" class="btn btn-primary"
    style="justify-content: center; padding: 1rem; font-size: 1.15rem;">Establish Node
    Connection</button>

<div class="divider">OR</div>

<button type="button" class="btn btn-google" id="google-login-btn"
    style="justify-content: center; padding: 1rem; font-size: 1.1rem; border-radius: 8px;">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20px" height="20px" style="margin-right: 8px;">
        <path fill="#FFC107"
            d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z" />
        <path fill="#FF3D00"
            d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z" />
        <path fill="#4CAF50"
            d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z" />
        <path fill="#1976D2"
            d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z" />
    </svg>
    Sign in using Google
</button>
</form>
</div>
</div>
<script>
    document.getElementById('google-login-btn').addEventListener('click', () => {
        const email = prompt('G Suite Authentication Context \\n\\nPlease natively select your authorized workspace email:\\n(Must be standard @student.auf.edu.ph domain format)');

        if (email !== null) {
            if (!email.match(/.+@student\.auf\.edu\.ph$/i)) {
                alert('ACCESS DENIED: Organization Policy requires a valid @student.auf.edu.ph Google Account to pass this gateway.');
            } else {
                // Populate and safely submit bypassing standard UI typing
                const input = document.querySelector('input[name="email"]');
                input.value = email;
                document.querySelector('.login-form').submit();
            }
        }
    });
</script>
</body>

</html> -->