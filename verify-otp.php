<?php
// verify-otp.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Ensure user came from forgot-password
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit();
}

$error = '';
$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session token. Please refresh and try again.";
    } else {
        $entered_otp = trim($_POST['otp'] ?? '');

        if (empty($entered_otp) || strlen($entered_otp) !== 6) {
            $error = "Please enter the valid 6-digit code.";
        } else {
            // Verify code against database and check expiration time
            $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
            $stmt->bind_param("ss", $email, $entered_otp);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Code is correct and active! Set verification flag
                $_SESSION['otp_verified'] = true;
                $stmt->close();
                header("Location: reset-password.php");
                exit();
            } else {
                $error = "Invalid or expired verification code. Please try again.";
            }
            $stmt->close();
        }
    }
}

$page_title = "Verify Code - Campus BookHub";
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
    .otp-input { letter-spacing: 0.5rem; font-size: 1.5rem; font-weight: 700; text-align: center; }
</style>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="mb-3">
            <i class="bi bi-shield-lock-fill display-5 text-wine"></i>
            <h3 class="fw-bold text-dark mt-2 mb-1">Enter Verification Code</h3>
            <p class="text-muted fs-7">We sent a 6-digit code to <strong><?php echo htmlspecialchars($email); ?></strong></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger rounded-3 fs-7 py-2 mb-3 text-start">
                <i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="verify-otp.php" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="text-start mb-4">
                <label class="form-label fs-7 fw-semibold text-dark">6-Digit Code *</label>
                <input type="text" name="otp" class="form-control bg-light rounded-3 otp-input" maxlength="6" placeholder="------" required autofocus>
            </div>

            <button type="submit" class="btn btn-wine w-100 py-2 mb-3">Verify Code</button>
        </form>

        <div class="fs-7 text-muted mt-3">
            Didn't receive the code? <a href="forgot-password.php" class="text-wine fw-bold text-decoration-none">Resend</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>