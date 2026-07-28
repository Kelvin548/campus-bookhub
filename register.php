<?php
// register.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    header("Location: " . ($_SESSION['user_role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid session token. Refresh and try again.";
    } else {
        $fullname         = trim($_POST['fullname'] ?? '');
        $index_number     = trim($_POST['index_number'] ?? '');
        $departmentlevel  = trim($_POST['departmentlevel'] ?? '');
        $class            = trim($_POST['class'] ?? '');
        $phone            = trim($_POST['phone'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Server-Side Field Validation
        if (empty($fullname) || empty($index_number) || empty($departmentlevel) || empty($class) || empty($phone) || empty($email) || empty($password)) {
            $errors[] = "Please fill in all required fields.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid Gmail / Email address.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }

        // Check for Existing Index Number or Email in Database
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT index_number, email FROM students WHERE index_number = ? OR email = ?");
            $stmt->bind_param("ss", $index_number, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                if (strtolower($row['index_number']) === strtolower($index_number)) {
                    $errors[] = "A student with this Index Number is already registered.";
                }
                if (strtolower($row['email']) === strtolower($email)) {
                    $errors[] = "An account with this Email address already exists.";
                }
            }
            $stmt->close();
        }

        // Create Account (Strictly as Student)
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO students (fullname, index_number, departmentlevel, class, phone, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $fullname, $index_number, $departmentlevel, $class, $phone, $email, $hashed_password);

            if ($stmt->execute()) {
                $success = "Account created successfully!";
                $_POST = []; // Clear form input values
            } else {
                $errors[] = "An error occurred during registration. Please try again.";
            }
            $stmt->close();
        }
    }
}

$page_title = "Register Account - Campus BookHub";
require_once 'includes/header.php';
?>

<!-- Google Fonts: Plus Jakarta Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --wine-main: #6b1d2f;
        --wine-dark: #2e0811;
        --wine-light: #8e2b43;
        --wine-gradient: linear-gradient(135deg, #6b1d2f 0%, #2e0811 100%);
        --wine-gradient-card: linear-gradient(145deg, #7c2237 0%, #420b17 100%);
        --font-family: 'Plus Jakarta Sans', sans-serif;
    }

    body {
        font-family: var(--font-family);
        background: #f8f6f7;
    }

    .register-wrapper {
        min-height: calc(100vh - 120px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2.5rem 1rem;
    }

    .register-card {
        background-color: #ffffff;
        border-radius: 24px;
        box-shadow: 0 20px 45px rgba(46, 8, 17, 0.12);
        overflow: hidden;
        width: 1020px;
        max-width: 100%;
        border: none;
    }

    /* Left Panel: Deep Wine Gradient Hero */
    .register-hero {
        background: var(--wine-gradient);
        color: #ffffff;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 3.5rem 2.5rem;
        overflow: hidden;
        height: 100%;
    }

    /* Radiant visual background effects */
    .register-hero::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0) 70%);
        pointer-events: none;
    }

    .register-hero::after {
        content: '';
        position: absolute;
        bottom: -90px;
        left: -90px;
        width: 320px;
        height: 320px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(142, 43, 67, 0.45) 0%, rgba(0, 0, 0, 0) 70%);
        filter: blur(30px);
        pointer-events: none;
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        padding: 6px 16px;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 2rem;
    }

    .btn-outline-ghost {
        background-color: transparent;
        border: 2px solid rgba(255, 255, 255, 0.85);
        color: #ffffff;
        padding: 10px 24px;
        font-weight: 600;
        border-radius: 12px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-outline-ghost:hover {
        background-color: #ffffff;
        color: var(--wine-main);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    /* Right Panel: Form Styling */
    .register-form-container {
        padding: 3rem 3rem;
    }

    .text-wine {
        color: var(--wine-main);
    }

    .btn-wine-gradient {
        background: var(--wine-gradient-card);
        color: #ffffff;
        border: none;
        padding: 12px 30px;
        font-weight: 600;
        border-radius: 12px;
        transition: all 0.3s ease;
        box-shadow: 0 8px 20px rgba(107, 29, 47, 0.25);
    }

    .btn-wine-gradient:hover {
        background: var(--wine-gradient);
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(107, 29, 47, 0.35);
    }

    .form-control {
        border-radius: 0 10px 10px 0;
        border: 1px solid #e3d9dc;
        padding: 0.65rem 0.9rem;
        font-size: 0.9rem;
        background-color: #fdfbfc;
    }

    .form-control:focus {
        border-color: var(--wine-main);
        box-shadow: 0 0 0 0.25rem rgba(107, 29, 47, 0.15);
        background-color: #ffffff;
    }

    .input-group-text {
        border-radius: 10px 0 0 10px;
        border: 1px solid #e3d9dc;
        border-end: none;
        background-color: #fcf6f8;
        color: var(--wine-main);
    }

    /* Mobile Responsive Rules */
    @media (max-width: 991px) {
        .register-hero {
            padding: 2.5rem 2rem;
            text-align: center;
        }

        .hero-badge {
            margin: 0 auto 1.5rem auto;
        }

        .register-form-container {
            padding: 2.2rem 1.8rem;
        }
    }

    @media (max-width: 576px) {
        .register-card {
            border-radius: 18px;
        }

        .register-form-container {
            padding: 1.5rem 1.2rem;
        }
    }
</style>

<div class="register-wrapper">
    <div class="register-card">
        <div class="row g-0">
            
            <!-- LEFT HERO PANEL (STYLISH WINE GRADIENT) -->
            <div class="col-lg-5">
                <div class="register-hero">
                    <div class="hero-content">
                        <span class="hero-badge"><i class="bi bi-mortarboard-fill"></i> Student Hub</span>
                        <h2 class="fw-bold display-6 mb-3">Join Campus BookHub</h2>
                        <p class="fs-7 opacity-75 leading-relaxed mb-4">
                            Register your account to manage course manual pre-orders, monitor pickup queues, and streamline your academic resources.
                        </p>
                    </div>

                    <div class="hero-footer position-relative z-2 mt-4">
                        <p class="fs-7 mb-2 opacity-90 fw-medium">Already have an account?</p>
                        <a href="login.php" class="btn btn-outline-ghost fs-7">
                            <i class="bi bi-arrow-left-circle me-1"></i> Sign In to Portal
                        </a>
                    </div>
                </div>
            </div>

            <!-- RIGHT FORM PANEL -->
            <div class="col-lg-7">
                <div class="register-form-container">
                    
                    <div class="mb-4">
                        <h3 class="fw-bold text-dark mb-1">Create Student Account</h3>
                        <p class="text-muted fs-7">Fill in your academic details to get started</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-3 fs-7 py-2 mb-3">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?php echo htmlspecialchars($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success rounded-3 fs-7 mb-3">
                            <i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($success); ?> 
                            <a href="login.php" class="fw-bold text-success text-decoration-underline ms-1">Sign In Now</a>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <!-- Full Name -->
                        <div class="mb-3">
                            <label class="form-label fs-7 fw-semibold text-dark">Full Name <span class="text-wine">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>" placeholder="e.g. Kelvyn Honora" required>
                            </div>
                        </div>

                        <!-- Index Number & Phone Number -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-7 fw-semibold text-dark">Index Number <span class="text-wine">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
                                    <input type="text" name="index_number" class="form-control" value="<?php echo htmlspecialchars($_POST['index_number'] ?? ''); ?>" placeholder="e.g. 5240101305" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-7 fw-semibold text-dark">Phone Number <span class="text-wine">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="0544893582" required>
                                </div>
                            </div>
                        </div>

                        <!-- Department/Level & Class -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-7 fw-semibold text-dark">Department & Level <span class="text-wine">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                                    <input type="text" name="departmentlevel" class="form-control" placeholder="e.g. IT - Level 200" value="<?php echo htmlspecialchars($_POST['departmentlevel'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-7 fw-semibold text-dark">Class <span class="text-wine">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-people"></i></span>
                                    <input type="text" name="class" class="form-control" placeholder="e.g. BSC IT A" value="<?php echo htmlspecialchars($_POST['class'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Email Address -->
                        <div class="mb-3">
                            <label class="form-label fs-7 fw-semibold text-dark">Gmail / Email Address <span class="text-wine">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="student@gmail.com" required>
                            </div>
                        </div>

                        <!-- Passwords -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fs-7 fw-semibold text-dark">Password <span class="text-wine">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-7 fw-semibold text-dark">Confirm Password <span class="text-wine">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                                    <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-wine-gradient w-100 py-2 fs-6">Register Account</button>
                    </form>

                    <div class="text-center mt-4 d-lg-none">
                        <small class="text-muted">Already registered? <a href="login.php" class="text-wine fw-bold text-decoration-none">Sign In here</a></small>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>