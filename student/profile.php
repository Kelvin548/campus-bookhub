<?php
// student/profile.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_student();

$student_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Fetch Student Profile
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Update Profile Form Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid session token.";
    } else {
        $fullname        = trim($_POST['fullname'] ?? '');
        $departmentlevel = trim($_POST['departmentlevel'] ?? '');
        $class           = trim($_POST['class'] ?? '');
        $phone           = trim($_POST['phone'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $new_password    = $_POST['new_password'] ?? '';

        if (empty($fullname) || empty($departmentlevel) || empty($class) || empty($phone)) {
            $errors[] = "Full Name, Department & Level, Class, and Phone are required.";
        }

        if (empty($errors)) {
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $errors[] = "New password must be at least 6 characters long.";
                } else {
                    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $upd_stmt = $conn->prepare("UPDATE students SET fullname = ?, departmentlevel = ?, class = ?, phone = ?, email = ?, password = ? WHERE student_id = ?");
                    $upd_stmt->bind_param("ssssssi", $fullname, $departmentlevel, $class, $phone, $email, $hashed, $student_id);
                    $upd_stmt->execute();
                    $upd_stmt->close();
                }
            } else {
                $upd_stmt = $conn->prepare("UPDATE students SET fullname = ?, departmentlevel = ?, class = ?, phone = ?, email = ? WHERE student_id = ?");
                $upd_stmt->bind_param("sssssi", $fullname, $departmentlevel, $class, $phone, $email, $student_id);
                $upd_stmt->execute();
                $upd_stmt->close();
            }

            if (empty($errors)) {
                $_SESSION['fullname'] = $fullname;
                $success = "Profile updated successfully!";
                $student['fullname'] = $fullname;
                $student['departmentlevel'] = $departmentlevel;
                $student['class'] = $class;
                $student['phone'] = $phone;
                $student['email'] = $email;
            }
        }
    }
}

$page_title = "My Profile - Campus BookHub";
require_once '../includes/header.php';
?>

<style>
    :root {
        --wine-main: #6b1d2f;
        --wine-dark: #2e0811;
        --wine-light: #8e2b43;
        --wine-gradient: linear-gradient(135deg, #6b1d2f 0%, #2e0811 100%);
        --wine-gradient-card: linear-gradient(145deg, #7c2237 0%, #420b17 100%);
        --sidebar-bg: #1f050b;
        --font-family: 'Plus Jakarta Sans', sans-serif;
    }

    body {
        font-family: var(--font-family);
        background-color: #f6f3f4;
        color: #2b2b2b;
        overflow-x: hidden;
    }

    #wrapper {
        display: flex;
        width: 100%;
        min-height: 100vh;
        position: relative;
    }

    /* Sidebar Styling */
    #sidebar-wrapper {
        width: 260px;
        min-width: 260px;
        background: var(--sidebar-bg) !important;
        min-height: 100vh;
        border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1050;
    }

    .sidebar-brand-box {
        background: rgba(255, 255, 255, 0.03);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .sidebar-logo-img {
        max-height: 48px;
        object-fit: contain;
        background: #ffffff;
        padding: 4px 8px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    }

    .sidebar-link {
        color: rgba(255, 255, 255, 0.7) !important;
        padding: 0.75rem 1rem;
        font-weight: 500;
        font-size: 0.88rem;
        border-radius: 10px !important;
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        text-decoration: none;
    }

    .sidebar-link:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        color: #ffffff !important;
        transform: translateX(3px);
    }

    .sidebar-link.active {
        background: var(--wine-gradient-card) !important;
        color: #ffffff !important;
        box-shadow: 0 6px 16px rgba(107, 29, 47, 0.4);
        font-weight: 600;
    }

    /* Page Content */
    #page-content-wrapper {
        flex: 1;
        width: 100%;
        min-width: 0;
    }

    /* Top Navbar */
    .top-navbar {
        background-color: #ffffff;
        border-bottom: 1px solid #eae2e4;
        height: 70px;
    }

    /* Helpers */
    .text-wine { color: var(--wine-main) !important; }
    .bg-wine { background-color: var(--wine-main) !important; color: #ffffff !important; }
    .bg-wine-subtle { background-color: rgba(107, 29, 47, 0.08) !important; color: var(--wine-main) !important; }
    .border-wine-subtle { border-color: rgba(107, 29, 47, 0.2) !important; }

    .btn-wine {
        background: var(--wine-gradient);
        color: #ffffff;
        border: none;
        transition: all 0.25s ease;
    }

    .btn-wine:hover {
        background: linear-gradient(135deg, #8e2b43 0%, #3e0c18 100%);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(107, 29, 47, 0.35);
    }

    /* Form Styles */
    .form-control:focus {
        border-color: var(--wine-light);
        box-shadow: 0 0 0 0.25rem rgba(107, 29, 47, 0.15);
    }

    /* Mobile Overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(2px);
        z-index: 1040;
    }

    @media (max-width: 991.98px) {
        #sidebar-wrapper {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            margin-left: -260px;
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.3);
        }

        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }

        #wrapper.toggled .sidebar-overlay {
            display: block;
        }

        .top-navbar {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        main {
            padding: 1.25rem !important;
        }
    }

    @media (min-width: 1200px) {
        main {
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }
    }
</style>

<div id="wrapper">
    <!-- Mobile Drawer Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Student Sidebar -->
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand-box px-3 py-3 text-center">
            <a href="dashboard.php" class="d-inline-block">
                <img src="../assets/images/logo.jpeg" alt="Campus BookHub Logo" class="sidebar-logo-img img-fluid">
            </a>
        </div>

        <div class="p-3">
            <small class="text-uppercase fw-bold px-2 mb-2 d-block fs-8" style="letter-spacing: 0.8px; color: rgba(255,255,255,0.4) !important;">
                Student Portal
            </small>

            <a href="dashboard.php" class="sidebar-link mb-1">
                <i class="bi bi-grid-1x2-fill me-2.5"></i> Dashboard
            </a>
            <a href="books.php" class="sidebar-link mb-1">
                <i class="bi bi-journal-bookmark-fill me-2.5"></i> Browse Manuals
            </a>
            <a href="my_orders.php" class="sidebar-link mb-1">
                <i class="bi bi-bag-check-fill me-2.5"></i> My Orders
            </a>
            <a href="profile.php" class="sidebar-link active mb-1">
                <i class="bi bi-person-gear me-2.5"></i> Profile Settings
            </a>

            <hr style="border-color: rgba(255,255,255,0.1); margin: 1.25rem 0;">

            <a href="../logout.php" class="sidebar-link text-danger mb-1" style="color: #ff6b81 !important;">
                <i class="bi bi-box-arrow-right me-2.5"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg top-navbar px-4">
            <div class="container-fluid p-0 d-flex align-items-center">
                <button class="btn btn-light border me-3 d-lg-none rounded-3 p-1 px-2" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-4 text-wine"></i>
                </button>

                <span class="navbar-brand fw-semibold text-muted fs-6 mb-0">Account Settings</span>

                <div class="d-flex align-items-center gap-2 ms-auto">
                    <div class="bg-wine-subtle border border-wine-subtle px-3 py-1.5 rounded-pill d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle text-wine fs-6"></i>
                        <span class="fw-semibold text-dark fs-7"><?php echo htmlspecialchars($student['fullname']); ?></span>
                    </div>
                </div>
            </div>
        </nav>

        <main class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Student Profile</h4>
                    <p class="text-muted fs-7 mb-0">Update your contact details, academic info, and password.</p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-3 fs-7 mb-4">
                    <div class="d-flex align-items-center mb-1">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                        <strong class="fw-semibold">Please resolve the following issues:</strong>
                    </div>
                    <ul class="mb-0 ps-4">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-3 fs-7 mb-4 d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2 fs-6"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Profile Form Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <h6 class="fw-bold text-wine mb-3">
                            <i class="bi bi-person-vcard me-2"></i>Personal & Academic Information
                        </h6>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7 text-dark">Full Name *</label>
                                <input type="text" name="fullname" class="form-control rounded-3 py-2 fs-7" value="<?php echo htmlspecialchars($student['fullname']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7 text-dark">Index Number (Read Only)</label>
                                <input type="text" class="form-control rounded-3 bg-light py-2 fs-7 text-muted" value="<?php echo htmlspecialchars($student['index_number']); ?>" readonly>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7 text-dark">Department & Level *</label>
                                <input type="text" name="departmentlevel" class="form-control rounded-3 py-2 fs-7" value="<?php echo htmlspecialchars($student['departmentlevel']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7 text-dark">Class / Stream *</label>
                                <input type="text" name="class" class="form-control rounded-3 py-2 fs-7" value="<?php echo htmlspecialchars($student['class']); ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7 text-dark">Phone Number *</label>
                                <input type="tel" name="phone" class="form-control rounded-3 py-2 fs-7" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7 text-dark">Email Address</label>
                                <input type="email" name="email" class="form-control rounded-3 py-2 fs-7" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                            </div>
                        </div>

                        <hr class="my-4" style="border-color: #eae2e4;">

                        <h6 class="fw-bold text-wine mb-3">
                            <i class="bi bi-shield-lock me-2"></i>Security Settings
                        </h6>

                        <div class="mb-4 style="max-width: 500px;">
                            <label class="form-label fw-semibold fs-7 text-dark">New Password</label>
                            <input type="password" name="new_password" class="form-control rounded-3 py-2 fs-7" placeholder="Leave blank to keep existing password">
                            <div class="form-text fs-8 text-muted mt-1">Must be at least 6 characters long if changing.</div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="btn btn-wine px-4 py-2 rounded-3 fw-semibold fs-7 d-inline-flex align-items-center gap-2">
                                <i class="bi bi-check-lg fs-6"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const wrapper = document.getElementById('wrapper');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            wrapper.classList.toggle('toggled');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            wrapper.classList.remove('toggled');
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>