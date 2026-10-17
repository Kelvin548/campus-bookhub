<?php
// admin/students.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success_msg = '';
$error_msg = '';

// Handle Student Deletion or Password Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Invalid security token. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';
        $student_id = (int)($_POST['student_id'] ?? 0);

        if ($action === 'delete_student' && $student_id > 0) {
            $conn->begin_transaction();
            try {
                $stmt_pay = $conn->prepare("DELETE FROM payments WHERE order_id IN (SELECT order_id FROM orders WHERE student_id = ?)");
                $stmt_pay->bind_param("i", $student_id);
                $stmt_pay->execute();

                $stmt_ord = $conn->prepare("DELETE FROM orders WHERE student_id = ?");
                $stmt_ord->bind_param("i", $student_id);
                $stmt_ord->execute();

                $stmt_stud = $conn->prepare("DELETE FROM students WHERE student_id = ?");
                $stmt_stud->bind_param("i", $student_id);
                $stmt_stud->execute();

                $conn->commit();
                $success_msg = "Student record and associated history deleted successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $error_msg = "Failed to delete student: " . $e->getMessage();
            }
        } elseif ($action === 'reset_password' && $student_id > 0) {
            $new_password = trim($_POST['new_password'] ?? '');
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_pw = $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                $stmt_pw->bind_param("si", $hashed_password, $student_id);
                if ($stmt_pw->execute()) {
                    $success_msg = "Student password has been successfully reset!";
                } else {
                    $error_msg = "Failed to reset password.";
                }
            } else {
                $error_msg = "Password cannot be empty.";
            }
        }
    }
}

// Search Filter
$search = trim($_GET['search'] ?? '');

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM students WHERE fullname LIKE ? OR index_number LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY student_id DESC");
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $students = $stmt->get_result();
} else {
    $students = $conn->query("SELECT * FROM students ORDER BY student_id DESC");
}

$page_title = "Manage Registered Students - Campus BookHub Admin";
require_once '../includes/header.php';
?>

<style>
    :root {
        --wine-main: #6b1d2f;
        --wine-dark: #2e0811;
        --wine-light: #8e2b43;
        --wine-gradient: linear-gradient(135deg, #6b1d2f 0%, #2e0811 100%);
        --sidebar-bg: #1f050b;
        --font-family: 'Plus Jakarta Sans', sans-serif;
    }

    body {
        font-family: var(--font-family);
        background-color: #f6f3f4;
        color: #2b2b2b;
    }

    #wrapper { display: flex; width: 100%; min-height: 100vh; }
    #sidebar-wrapper {
        width: 260px; min-width: 260px; background: var(--sidebar-bg) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
    }
    .sidebar-brand-box { background: rgba(255, 255, 255, 0.03); border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
    .sidebar-logo-img { max-height: 48px; object-fit: contain; background: #ffffff; padding: 4px 8px; border-radius: 10px; }
    .sidebar-link {
        color: rgba(255, 255, 255, 0.7) !important; padding: 0.75rem 1rem;
        font-weight: 500; font-size: 0.88rem; border-radius: 10px !important; display: flex; align-items: center; text-decoration: none;
    }
    .sidebar-link:hover { background: rgba(255, 255, 255, 0.08) !important; color: #ffffff !important; }
    .sidebar-link.active { background: linear-gradient(145deg, #7c2237 0%, #420b17 100%) !important; color: #ffffff !important; font-weight: 600; }
    #page-content-wrapper { flex: 1; width: 100%; }
    .top-navbar { background-color: #ffffff; border-bottom: 1px solid #eae2e4; height: 70px; }
    .text-wine { color: var(--wine-main) !important; }
    .bg-wine-subtle { background-color: rgba(107, 29, 47, 0.08) !important; color: var(--wine-main) !important; }
    .btn-wine { background: var(--wine-gradient); color: #ffffff !important; border: none; }
</style>

<div id="wrapper">
    <!-- Admin Sidebar -->
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand-box px-3 py-3 text-center">
            <a href="dashboard.php" class="d-inline-block">
                <img src="../assets/images/logo.jpeg" alt="Campus BookHub Logo" class="sidebar-logo-img img-fluid">
            </a>
        </div>
        <div class="p-3">
            <small class="text-uppercase fw-bold px-2 mb-2 d-block fs-8" style="letter-spacing: 0.8px; color: rgba(255,255,255,0.4) !important;">
                Admin Control
            </small>
            <a href="dashboard.php" class="sidebar-link mb-1">
                <i class="bi bi-speedometer2 me-2.5"></i> Dashboard
            </a>
            <a href="students.php" class="sidebar-link active mb-1">
                <i class="bi bi-people-fill me-2.5"></i> Registered Students
            </a>
            <a href="books.php" class="sidebar-link mb-1">
                <i class="bi bi-journal-album me-2.5"></i> Manage Books
            </a>
            <a href="orders.php" class="sidebar-link mb-1">
                <i class="bi bi-cart-check-fill me-2.5"></i> Orders
            </a>
            <a href="payments.php" class="sidebar-link mb-1">
                <i class="bi bi-credit-card-2-front-fill me-2.5"></i> Payments
            </a>
            <a href="reports.php" class="sidebar-link mb-1">
                <i class="bi bi-bar-chart-line-fill me-2.5"></i> Reports
            </a>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 1.25rem 0;">
            <a href="../logout.php" class="sidebar-link text-danger mb-1" style="color: #ff6b81 !important;">
                <i class="bi bi-box-arrow-right me-2.5"></i> Logout
            </a>
        </div>
    </aside>

    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg top-navbar px-4">
            <div class="container-fluid p-0 d-flex align-items-center">
                <span class="navbar-brand fw-semibold text-muted fs-6 mb-0">Admin Workspace</span>
                <div class="ms-auto bg-wine-subtle px-3 py-1.5 rounded-pill d-flex align-items-center gap-2">
                    <i class="bi bi-person-badge-fill text-wine fs-6"></i>
                    <span class="fw-semibold text-dark fs-7">Administrator</span>
                </div>
            </div>
        </nav>

        <main class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Registered Students Directory</h4>
                    <p class="text-muted fs-7 mb-0">View all enrolled student accounts, inspect registration details, reset passwords, or remove obsolete accounts.</p>
                </div>
                <div class="badge bg-wine-subtle text-wine fs-6 px-3 py-2 rounded-pill">
                    Total Registered: <b><?php echo $students ? $students->num_rows : 0; ?></b>
                </div>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Search Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-3 p-md-4">
                    <form action="students.php" method="GET" class="row g-2">
                        <div class="col-md-10">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control form-control-lg border-start-0 fs-6 rounded-end-3" placeholder="Search by name, index number, email, or phone number..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-wine btn-lg w-100 rounded-3 fs-6 fw-semibold">
                                Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Students Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted fs-8 text-uppercase">
                            <tr>
                                <th class="ps-4"># ID</th>
                                <th>Student Name</th>
                                <th>Index Number</th>
                                <th>Email Address</th>
                                <th>Phone Number</th>
                                <th>Registered Date</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($students && $students->num_rows > 0): ?>
                                <?php while ($st = $students->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted">#<?php echo $st['student_id']; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($st['fullname']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-wine-subtle text-wine px-2.5 py-1 rounded-pill fw-semibold">
                                                <?php echo htmlspecialchars($st['index_number']); ?>
                                            </span>
                                        </td>
                                        <td class="text-muted fs-7"><?php echo htmlspecialchars($st['email']); ?></td>
                                        <td class="text-muted fs-7"><?php echo htmlspecialchars($st['phone'] ?? 'N/A'); ?></td>
                                        <td class="text-muted fs-7">
                                            <?php echo !empty($st['created_at']) ? date('M d, Y', strtotime($st['created_at'])) : 'N/A'; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-2">
                                                <!-- Reset Password Button -->
                                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1 fs-8 fw-semibold" data-bs-toggle="modal" data-bs-target="#resetModal<?php echo $st['student_id']; ?>" title="Reset Password">
                                                    <i class="bi bi-key-fill"></i> Reset
                                                </button>

                                                <!-- Compact, Cute Delete Button -->
                                                <form action="students.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($st['fullname']); ?>?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="action" value="delete_student">
                                                    <input type="hidden" name="student_id" value="<?php echo $st['student_id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-1 d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px;" title="Delete Student">
                                                        <i class="bi bi-trash3-fill fs-8"></i>
                                                    </button>
                                                </form>
                                            </div>

                                            <!-- Reset Password Modal -->
                                            <div class="modal fade text-start" id="resetModal<?php echo $st['student_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0 shadow rounded-4">
                                                        <form action="students.php" method="POST">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                            <input type="hidden" name="action" value="reset_password">
                                                            <input type="hidden" name="student_id" value="<?php echo $st['student_id']; ?>">
                                                            
                                                            <div class="modal-header border-0 pb-0 pt-4 px-4">
                                                                <h5 class="fw-bold text-dark mb-0">Reset Password</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body px-4 py-3">
                                                                <p class="text-muted fs-7 mb-3">Set a new temporary password for <b><?php echo htmlspecialchars($st['fullname']); ?></b> (Index: <?php echo htmlspecialchars($st['index_number']); ?>):</p>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold fs-8 text-uppercase text-muted">New Password</label>
                                                                    <input type="text" name="new_password" class="form-control rounded-3" value="Password123" required>
                                                                    <small class="text-muted fs-8 mt-1 d-block">Default suggestion provided. You can edit it before saving.</small>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                                                                <button type="button" class="btn btn-light rounded-3 px-3 fs-7 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-wine rounded-3 px-4 fs-7 fw-semibold">Update Password</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="bi bi-people display-4 text-muted d-block mb-2"></i>
                                        <h6 class="fw-bold text-muted">No Registered Students Found</h6>
                                        <p class="text-muted fs-7 mb-0">Try clearing your search query to view all students.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>