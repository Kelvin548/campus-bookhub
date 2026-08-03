<?php
// forgot-password.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/mailer.php';

// Ensure a CSRF token exists for this session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_token = $_POST['csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';

    // Direct, robust CSRF verification
    if (empty($post_token) || empty($session_token) || !hash_equals($session_token, $post_token)) {
        $error = "Invalid session token. Please refresh and try again.";
    } else {
        // Rotate token to prevent reuse
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            $user_type = '';
            
            // 1. Check in students table
            $stmt = $conn->prepare("SELECT student_id FROM students WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $user_type = 'student';
                }
                $stmt->close();
            }

            // 2. If not found, check admin table
            if (!$user_type) {
                $stmt = $conn->prepare("SELECT admin_id FROM admin WHERE email = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $user_type = 'admin';
                    }
                    $stmt->close();
                }
            }

            $success_message = "If that email address exists in our system, a 6-digit verification code has been sent.";

            if ($user_type) {
                $otp = sprintf("%06d", mt_rand(0, 999999));
                $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes expiration
                
                // Clear old tokens
                $del_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                if ($del_stmt) {
                    $del_stmt->bind_param("s", $email);
                    $del_stmt->execute();
                    $del_stmt->close();
                }

                // Insert new OTP
                $ins_stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                if ($ins_stmt) {
                    $ins_stmt->bind_param("sss", $email, $otp, $expires_at);
                    $ins_stmt->execute();
                    $ins_stmt->close();
                }

                $_SESSION['reset_email'] = $email;

                // Send email via PHPMailer SMTP (IPv4 + Port 465)
                $email_sent = send_otp_email($email, $otp);

                if ($email_sent !== true) {
                    $error = "SMTP Error: " . $email_sent;
                } else {
                    $message = $success_message;
                }
            } else {
                $message = $success_message;
            }
        }
    }
}

$page_title = "Forgot Password - Campus BookHub";
require_once 'includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --wine-main: #6b1d2f;
        --wine-dark: #4a101d;
        --font-family: 'Plus Jakarta Sans', sans-serif;
    }
    body { font-family: var(--font-family); background: #f8f6f7; }
    .auth-wrapper { min-height: calc(100vh - 120px); display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
    .auth-card { background-color: #ffffff; border-radius: 24px; box-shadow: 0 20px 40px rgba(74, 16, 29, 0.12); width: 480px; max-width: 100%; padding: 40px; text-align: center; }
    .btn-wine { background-color: var(--wine-main); color: #ffffff; border: none; padding: 12px 30px; font-weight: 600; border-radius: 12px; transition: all 0.3s ease; }
    .btn-wine:hover { background-color: var(--wine-dark); color: #ffffff; transform: translateY(-2px); }
    .text-wine { color: var(--wine-main); }
    .form-control:focus { border-color: var(--wine-main); box-shadow: 0 0 0 0.25rem rgba(107, 29, 47, 0.15); }
</style>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="mb-3">
            <i class="bi bi-key-fill display-5 text-wine"></i>
            <h3 class="fw-bold text-dark mt-2 mb-1">Forgot Password?</h3>
            <p class="text-muted fs-7">Enter your account email to receive a verification code</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger rounded-3 fs-7 py-2 mb-3 text-start">
                <i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success rounded-3 fs-7 py-3 mb-3 text-start">
                <i class="bi bi-check-circle-fill me-1"></i><?php echo htmlspecialchars($message); ?>
            </div>

            <a href="verify-otp.php" class="btn btn-wine w-100 py-2 mt-2 text-decoration-none d-block">Enter Verification Code</a>
        <?php else: ?>
            <form action="forgot-password.php" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <div class="text-start mb-4">
                    <label class="form-label fs-7 fw-semibold text-dark">Account Email Address *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control bg-light border-start-0 rounded-end-3" placeholder="name@example.com" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-wine w-100 py-2 mb-3">Send Verification Code</button>

                <div class="fs-7 text-muted">
                    Remembered your password? <a href="login.php" class="text-wine fw-bold text-decoration-none">Sign in</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>