<?php
// index.php
require_once 'includes/auth.php';

if (is_logged_in()) {
    header("Location: " . ($_SESSION['user_role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit();
}

$page_title = "Welcome - Campus BookHub";
require_once 'includes/header.php';
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4 text-dark d-flex align-items-center gap-2" href="index.php">
            <i class="bi bi-book-half text-primary fs-3"></i>
            <span>Campus<span class="text-primary">BookHub</span></span>
        </a>
        <div class="d-flex gap-2">
            <a href="login.php" class="btn btn-outline-primary px-4 rounded-3 fw-semibold">Log In</a>
            <a href="register.php" class="btn btn-primary px-4 rounded-3 fw-semibold">Register</a>
        </div>
    </div>
</nav>

<div class="container my-auto py-5">
    <div class="row align-items-center g-5">
        <div class="col-lg-6">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold mb-3">
                Order. Pay. Collect.
            </span>
            <h1 class="display-4 fw-bold text-dark lh-sm mb-3">Simplified Course Manual Distribution for Universities</h1>
            <p class="lead text-muted mb-4">Browse available textbooks, place instant orders, log payment details, and collect your materials seamlessly.</p>
            <div class="d-flex flex-wrap gap-3">
                <a href="register.php" class="btn btn-primary btn-lg rounded-3 px-4 fw-semibold shadow-sm">
                    Get Started <i class="bi bi-arrow-right ms-2"></i>
                </a>
                <a href="login.php" class="btn btn-light btn-lg border rounded-3 px-4 fw-semibold">
                    Student Login
                </a>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-5 bg-primary text-white position-relative">
                    <h3 class="fw-bold mb-3">Course Representatives</h3>
                    <p class="opacity-75 mb-4">Manage inventory, approve student payments, track book pickup status, and keep clean reports with ease.</p>
                    <a href="login.php" class="btn btn-light text-primary fw-bold px-4 py-2 rounded-3">
                        Admin Portal Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>