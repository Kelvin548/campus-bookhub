<?php
// login.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    header("Location: " . ($_SESSION['user_role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit();
}

$error = '';
$login_type = $_POST['login_type'] ?? 'student'; // Default active tab
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = $_POST['login_type'] ?? 'student';
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session token. Refresh and try again.";
    } else {
        if (empty($identifier) || empty($password)) {
            $error = "Please fill in all required fields.";
        } else {
            if ($login_type === 'student') {
                // Student Login Check (Index Number OR Email)
                $stmt = $conn->prepare("SELECT student_id, fullname, index_number, email, password FROM students WHERE index_number = ? OR email = ?");
                $stmt->bind_param("ss", $identifier, $identifier);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($user = $result->fetch_assoc()) {
                    if (password_verify($password, $user['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_id']      = $user['student_id'];
                        $_SESSION['fullname']     = $user['fullname'];
                        $_SESSION['index_number'] = $user['index_number'];
                        $_SESSION['user_role']    = 'student';

                        header("Location: student/dashboard.php");
                        exit();
                    }
                }
                $error = "Invalid Student Index Number/Email or Password.";
                $stmt->close();
            } else {
                // Admin Login Check (Pre-approved Accounts Only)
                $stmt = $conn->prepare("SELECT admin_id, fullname, username, email, password FROM admin WHERE username = ? OR email = ?");
                $stmt->bind_param("ss", $identifier, $identifier);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($admin = $result->fetch_assoc()) {
                    if (password_verify($password, $admin['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_id']   = $admin['admin_id'];
                        $_SESSION['fullname']  = $admin['fullname'];
                        $_SESSION['username']  = $admin['username'];
                        $_SESSION['user_role'] = 'admin';

                        header("Location: admin/dashboard.php");
                        exit();
                    }
                }
                $error = "Invalid Admin Credentials.";
                $stmt->close();
            }
        }
    }
}

$page_title = "Sign In - Campus BookHub";
require_once 'includes/header.php';
?>

<!-- Google Fonts: Plus Jakarta Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --wine-main: #6b1d2f;
        --wine-dark: #4a101d;
        --wine-light: #8e2b43;
        --wine-gradient: linear-gradient(135deg, #6b1d2f 0%, #3b0a15 100%);
        --font-family: 'Plus Jakarta Sans', sans-serif;
    }

    body {
        font-family: var(--font-family);
        background: #f8f6f7;
    }

    .auth-wrapper {
        min-height: calc(100vh - 120px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }

    /* Container Box */
    .sliding-container {
        background-color: #ffffff;
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(74, 16, 29, 0.12);
        position: relative;
        overflow: hidden;
        width: 900px;
        max-width: 100%;
        min-height: 550px;
    }

    .form-container {
        position: absolute;
        top: 0;
        height: 100%;
        transition: all 0.6s ease-in-out;
    }

    /* Student Form Left Side */
    .student-container {
        left: 0;
        width: 50%;
        z-index: 2;
    }

    /* Admin Form Right Side (hidden behind overlay initially) */
    .admin-container {
        left: 0;
        width: 50%;
        opacity: 0;
        z-index: 1;
    }

    /* Active Animation States */
    .sliding-container.right-panel-active .student-container {
        transform: translateX(100%);
        opacity: 0;
        z-index: 1;
    }

    .sliding-container.right-panel-active .admin-container {
        transform: translateX(100%);
        opacity: 1;
        z-index: 5;
        animation: show 0.6s;
    }

    @keyframes show {
        0%, 49.99% {
            opacity: 0;
            z-index: 1;
        }
        50%, 100% {
            opacity: 1;
            z-index: 5;
        }
    }

    /* Overlay Sliding Panel */
    .overlay-container {
        position: absolute;
        top: 0;
        left: 50%;
        width: 50%;
        height: 100%;
        overflow: hidden;
        transition: transform 0.6s ease-in-out;
        z-index: 100;
    }

    .sliding-container.right-panel-active .overlay-container {
        transform: translateX(-100%);
    }

    .overlay {
        background: var(--wine-gradient);
        color: #ffffff;
        position: relative;
        left: -100%;
        height: 100%;
        width: 200%;
        transform: translateX(0);
        transition: transform 0.6s ease-in-out;
    }

    .sliding-container.right-panel-active .overlay {
        transform: translateX(50%);
    }

    .overlay-panel {
        position: absolute;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 0 40px;
        text-align: center;
        top: 0;
        height: 100%;
        width: 50%;
        transform: translateX(0);
        transition: transform 0.6s ease-in-out;
    }

    .overlay-left {
        transform: translateX(-20%);
    }

    .sliding-container.right-panel-active .overlay-left {
        transform: translateX(0);
    }

    .overlay-right {
        right: 0;
        transform: translateX(0);
    }

    .sliding-container.right-panel-active .overlay-right {
        transform: translateX(20%);
    }

    /* Form Styles */
    .auth-form {
        background-color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 0 45px;
        height: 100%;
        text-align: center;
    }

    .btn-wine {
        background-color: var(--wine-main);
        color: #ffffff;
        border: none;
        padding: 12px 30px;
        font-weight: 600;
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .btn-wine:hover {
        background-color: var(--wine-dark);
        color: #ffffff;
        transform: translateY(-2px);
    }

    .btn-outline-ghost {
        background-color: transparent;
        border: 2px solid #ffffff;
        color: #ffffff;
        padding: 10px 28px;
        font-weight: 600;
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .btn-outline-ghost:hover {
        background-color: #ffffff;
        color: var(--wine-main);
    }

    .form-control:focus {
        border-color: var(--wine-main);
        box-shadow: 0 0 0 0.25rem rgba(107, 29, 47, 0.15);
    }

    .text-wine {
        color: var(--wine-main);
    }

    /* Mobile Toggle Switch Pill */
    .mobile-tab-switch {
        display: none;
    }

    /* ========================================================
        MEDIA QUERY: Small Screens & Phones (Responsive Design)
        ======================================================== */
    @media (max-width: 768px) {
        .sliding-container {
            min-height: auto;
            border-radius: 20px;
        }

        .overlay-container {
            display: none; /* Hide desktop sliding overlay on mobile */
        }

        .student-container, 
        .admin-container {
            width: 100%;
            position: relative;
            opacity: 1 !important;
            transform: none !important;
            display: none;
            padding: 20px 0;
        }

        /* Show active container on mobile */
        .sliding-container:not(.right-panel-active) .student-container {
            display: block;
        }

        .sliding-container.right-panel-active .admin-container {
            display: block;
        }

        .auth-form {
            padding: 30px 20px;
        }

        .mobile-tab-switch {
            display: flex;
            background: #f1e9ec;
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 20px;
            width: 100%;
        }

        .mobile-tab-btn {
            flex: 1;
            border: none;
            background: transparent;
            padding: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 10px;
            color: var(--wine-dark);
            transition: all 0.3s ease;
        }

        .mobile-tab-btn.active {
            background: var(--wine-main);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(107, 29, 47, 0.2);
        }
    }
</style>

<div class="auth-wrapper">
    <div class="sliding-container <?php echo ($login_type === 'admin') ? 'right-panel-active' : ''; ?>" id="slidingContainer">
        
        <!-- STUDENT LOGIN FORM -->
        <div class="form-container student-container">
            <form action="login.php" method="POST" class="auth-form" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="login_type" value="student">

                <!-- Mobile Switch Buttons -->
                <div class="mobile-tab-switch">
                    <button type="button" class="mobile-tab-btn active" onclick="switchToStudent()">Student</button>
                    <button type="button" class="mobile-tab-btn" onclick="switchToAdmin()">Admin</button>
                </div>

                <div class="mb-3 text-center">
                    <i class="bi bi-mortarboard-fill display-5 text-wine"></i>
                    <h3 class="fw-bold text-dark mt-2 mb-1">Student Portal</h3>
                    <p class="text-muted fs-7">Access your course manual orders</p>
                </div>

                <?php if ($error && $login_type === 'student'): ?>
                    <div class="alert alert-danger w-100 rounded-3 fs-7 py-2 mb-3 text-start">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="w-100 text-start mb-3">
                    <label class="form-label fs-7 fw-semibold text-dark">Index Number or Gmail *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" name="identifier" class="form-control bg-light border-start-0 rounded-end-3" placeholder="Index number or Gmail" value="<?php echo htmlspecialchars($identifier); ?>" required>
                    </div>
                </div>

                <div class="w-100 text-start mb-4">
                    <label class="form-label fs-7 fw-semibold text-dark">Password *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control bg-light border-start-0 rounded-end-3" placeholder="••••••••" required>
                    </div>
                    <div class="text-end mt-1">
                        <a href="forgot-password.php" class="fs-7 text-muted text-decoration-none">Forgot password?</a>
                    </div>
                </div>

                <button type="submit" class="btn btn-wine w-100 py-2 mb-3">Sign In as Student</button>

                <div class="fs-7 text-muted">
                    Don't have an account? <a href="register.php" class="text-wine fw-bold text-decoration-none">Register here</a>
                </div>
            </form>
        </div>

        <!-- ADMIN LOGIN FORM -->
        <div class="form-container admin-container">
            <form action="login.php" method="POST" class="auth-form" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="login_type" value="admin">

                <!-- Mobile Switch Buttons -->
                <div class="mobile-tab-switch">
                    <button type="button" class="mobile-tab-btn" onclick="switchToStudent()">Student</button>
                    <button type="button" class="mobile-tab-btn active" onclick="switchToAdmin()">Admin</button>
                </div>

                <div class="mb-3 text-center">
                    <i class="bi bi-shield-lock-fill display-5 text-wine"></i>
                    <h3 class="fw-bold text-dark mt-2 mb-1">Admin Portal</h3>
                    <p class="text-muted fs-7">Management & Fulfillment Console</p>
                </div>

                <?php if ($error && $login_type === 'admin'): ?>
                    <div class="alert alert-danger w-100 rounded-3 fs-7 py-2 mb-3 text-start">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="w-100 text-start mb-3">
                    <label class="form-label fs-7 fw-semibold text-dark">Username or Gmail *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-person text-muted"></i></span>
                        <input type="text" name="identifier" class="form-control bg-light border-start-0 rounded-end-3" placeholder="e.g. admin_rep or admin@gmail.com" required>
                    </div>
                </div>

                <div class="w-100 text-start mb-4">
                    <label class="form-label fs-7 fw-semibold text-dark">Password *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-muted"></i></span>
                        <input type="password" name="password" class="form-control bg-light border-start-0 rounded-end-3" placeholder="••••••••" required>
                    </div>
                    <div class="text-end mt-1">
                        <a href="forgot-password.php" class="fs-7 text-muted text-decoration-none">Forgot password?</a>
                    </div>
                </div>

                <button type="submit" class="btn btn-wine w-100 py-2 mb-3">Sign In as Admin</button>

                <div class="fs-7 text-muted">
                    <i class="bi bi-info-circle me-1"></i> Admin logins are strictly pre-approved.
                </div>
            </form>
        </div>

        <!-- SLIDING OVERLAY PANEL (DESKTOP) -->
        <div class="overlay-container">
            <div class="overlay">
                
                <!-- Left Overlay (Revealed when Admin tab is active -> offers switch to Student) -->
                <div class="overlay-panel overlay-left">
                    <i class="bi bi-book-half display-3 mb-3"></i>
                    <h2 class="fw-bold mb-2">Are you a Student?</h2>
                    <p class="fs-7 opacity-75 mb-4">Switch over to the student portal to order manual copies and track pick-ups.</p>
                    <button type="button" class="btn btn-outline-ghost" id="signInStudentBtn" onclick="switchToStudent()">Student Portal</button>
                </div>

                <!-- Right Overlay (Revealed when Student tab is active -> offers switch to Admin) -->
                <div class="overlay-panel overlay-right">
                    <i class="bi bi-shield-check display-3 mb-3"></i>
                    <h2 class="fw-bold mb-2">Administrator Access</h2>
                    <p class="fs-7 opacity-75 mb-4">Are you a system admin or manual rep? Sign in here to manage book handovers.</p>
                    <button type="button" class="btn btn-outline-ghost" id="signInAdminBtn" onclick="switchToAdmin()">Admin Portal</button>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
const slidingContainer = document.getElementById('slidingContainer');

function switchToAdmin() {
    slidingContainer.classList.add('right-panel-active');
}

function switchToStudent() {
    slidingContainer.classList.remove('right-panel-active');
}
</script>

<?php require_once 'includes/footer.php'; ?>