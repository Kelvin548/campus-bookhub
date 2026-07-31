<?php
// reset-password.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Ensure user has verified their OTP code successfully first
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: forgot-password.php");
    exit();
}

$error = '';
$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session token. Please refresh and try again.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($password) || strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $updated = false;

            // 1. Update in students table if email exists there
            $stmt = $conn->prepare("UPDATE students SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $updated = true;
            }
            $stmt->close();

            // 2. If not found in students, update in admin table
            if (!$updated) {
                $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hashed_password, $email);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $updated = true;
                }
                $stmt->close();
            }

            // Clean up the password resets entry
            $del_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del_stmt->bind_param("s", $email);
            $del_stmt->execute();
            $del_stmt->close();

            // Clear reset session variables
            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);

            // Success redirect to login with a flash message
            $_SESSION['flash_success'] = "Password successfully reset! You can now sign in with your new password.";
            header("Location: login.php");
            exit();
        }
    }
}

$page_title = "Set New Password - Campus BookHub";
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
            <i class="bi bi-shield-check display-5 text-wine"></i>
            <h3 class="fw-bold text-dark mt-2 mb-1">Set New Password</h3>
            <p class="text-muted fs-7">Choose a secure new password for your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger rounded-3 fs-7 py-2 mb-3 text-start">
                <i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="reset-password.php" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="text-start mb-3">
                <label class="form-label fs-7 fw-semibold text-dark">New Password *</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control bg-light border-start-0 rounded-end-3" placeholder="At least 6 characters" required autofocus>
                </div>
            </div>

            <div class="text-start mb-4">
                <label class="form-label fs-7 fw-semibold text-dark">Confirm New Password *</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-muted"></i></span>
                    <input type="password" name="confirm_password" class="form-control bg-light border-start-0 rounded-end-3" placeholder="Re-enter password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-wine w-100 py-2 mb-3">Update Password</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>