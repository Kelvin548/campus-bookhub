<?php
// student/dashboard.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_student();

$student_id = $_SESSION['user_id'];

// Consolidated Query for Student Metrics
$metrics_stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_orders,
        SUM(CASE WHEN payment_status = 'Pending' THEN 1 ELSE 0 END) AS pending_payments,
        SUM(CASE WHEN payment_status = 'Verified' AND collection_status = 'Pending' THEN 1 ELSE 0 END) AS ready_for_pickup,
        SUM(CASE WHEN collection_status = 'Collected' THEN 1 ELSE 0 END) AS collected_books
    FROM orders 
    WHERE student_id = ?
");
$metrics_stmt->bind_param("i", $student_id);
$metrics_stmt->execute();
$metrics = $metrics_stmt->get_result()->fetch_assoc();
$metrics_stmt->close();

$total_orders     = $metrics['total_orders'] ?? 0;
$pending_payments = $metrics['pending_payments'] ?? 0;
$ready_for_pickup = $metrics['ready_for_pickup'] ?? 0;
$collected_books  = $metrics['collected_books'] ?? 0;

// Fetch Recent Orders for Student (Limit 5)
$recent_stmt = $conn->prepare("
    SELECT o.order_id, b.title, b.course_code, o.quantity, o.total_amount, o.payment_status, o.collection_status, o.order_date
    FROM orders o
    JOIN books b ON o.book_id = b.book_id
    WHERE o.student_id = ?
    ORDER BY o.order_date DESC LIMIT 5
");
$recent_stmt->bind_param("i", $student_id);
$recent_stmt->execute();
$recent_orders = $recent_stmt->get_result();

$page_title = "Student Dashboard - Campus BookHub";
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

    /* Page Content Wrapper */
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

    .text-wine { color: var(--wine-main); }

    /* Dashboard Hero Banner */
    .banner-wine {
        background: var(--wine-gradient);
        position: relative;
        overflow: hidden;
    }

    .banner-wine::after {
        content: '';
        position: absolute;
        right: -30px;
        bottom: -50px;
        width: 220px;
        height: 220px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        pointer-events: none;
    }

    /* Stat Cards Icon Shapes */
    .icon-box-shape {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }

    /* Mobile Drawer Overlay */
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

    /* Responsive Breakpoints */
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
            max-width: 1240px;
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
        <!-- Logo Branding Header -->
        <div class="sidebar-brand-box px-3 py-3 text-center">
            <a href="dashboard.php" class="d-inline-block">
                <img src="../assets/images/logo.jpeg" alt="Campus BookHub Logo" class="sidebar-logo-img img-fluid">
            </a>
        </div>

        <div class="p-3">
            <small class="text-uppercase fw-bold text-muted px-2 mb-2 d-block fs-8" style="letter-spacing: 0.8px; color: rgba(255,255,255,0.4) !important;">
                Student Portal
            </small>

            <a href="dashboard.php" class="sidebar-link active mb-1">
                <i class="bi bi-grid-1x2-fill me-2.5"></i> Dashboard
            </a>
            <a href="books.php" class="sidebar-link mb-1">
                <i class="bi bi-journal-bookmark-fill me-2.5"></i> Browse Manuals
            </a>
            <a href="my_orders.php" class="sidebar-link mb-1">
                <i class="bi bi-bag-check-fill me-2.5"></i> My Orders
            </a>
            <a href="profile.php" class="sidebar-link mb-1">
                <i class="bi bi-person-gear me-2.5"></i> Profile Settings
            </a>

            <hr style="border-color: rgba(255,255,255,0.1); margin: 1.25rem 0;">

            <a href="../logout.php" class="sidebar-link text-danger mb-1" style="color: #ff6b81 !important;">
                <i class="bi bi-box-arrow-right me-2.5"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg top-navbar px-4">
            <div class="container-fluid p-0 d-flex align-items-center">
                <!-- Mobile Sidebar Toggle -->
                <button class="btn btn-light border me-3 d-lg-none rounded-3 p-1 px-2" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-4 text-wine"></i>
                </button>

                <span class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                    <span class="text-muted fw-normal d-none d-sm-inline">Welcome back,</span> 
                    <span><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                </span>

                <div class="d-flex align-items-center gap-3 ms-auto">
                    <div class="badge bg-white text-dark border px-3 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2 fs-7">
                        <i class="bi bi-card-heading text-wine fs-6"></i>
                        <span class="fw-semibold">Index: <?php echo htmlspecialchars($_SESSION['index_number']); ?></span>
                    </div>
                </div>
            </div>
        </nav>

        <main class="p-4">
            <!-- Hero Banner -->
            <div class="card border-0 text-white shadow-sm rounded-4 mb-4 p-4 banner-wine">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 position-relative" style="z-index: 2;">
                    <div>
                        <h4 class="fw-bold mb-1 fs-4">Course Manual Ordering Portal</h4>
                        <p class="mb-0 text-white-50 fs-7">Browse required textbooks, upload payment proofs, and track your pickup status.</p>
                    </div>
                    <div>
                        <a href="books.php" class="btn btn-light text-wine fw-bold px-4 py-2 rounded-3 shadow-sm fs-7 d-inline-flex align-items-center gap-2">
                            <i class="bi bi-search"></i>
                            <span>Browse Books</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metrics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                        <div class="d-flex align-items-center">
                            <div class="icon-box-shape bg-danger-subtle text-danger me-3 fs-4">
                                <i class="bi bi-bag-fill"></i>
                            </div>
                            <div>
                                <small class="text-muted fw-semibold fs-8 uppercase d-block">Total Orders</small>
                                <h4 class="fw-bold text-dark mb-0 fs-3"><?php echo $total_orders; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                        <div class="d-flex align-items-center">
                            <div class="icon-box-shape bg-warning-subtle text-warning-emphasis me-3 fs-4">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <div>
                                <small class="text-muted fw-semibold fs-8 uppercase d-block">Pending Approval</small>
                                <h4 class="fw-bold text-dark mb-0 fs-3"><?php echo $pending_payments; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                        <div class="d-flex align-items-center">
                            <div class="icon-box-shape bg-info-subtle text-info me-3 fs-4">
                                <i class="bi bi-box-seam-fill"></i>
                            </div>
                            <div>
                                <small class="text-muted fw-semibold fs-8 uppercase d-block">Ready for Pickup</small>
                                <h4 class="fw-bold text-dark mb-0 fs-3"><?php echo $ready_for_pickup; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                        <div class="d-flex align-items-center">
                            <div class="icon-box-shape bg-success-subtle text-success me-3 fs-4">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div>
                                <small class="text-muted fw-semibold fs-8 uppercase d-block">Collected Books</small>
                                <h4 class="fw-bold text-dark mb-0 fs-3"><?php echo $collected_books; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-wine fs-5"></i>
                        <span>My Recent Orders</span>
                    </h6>
                    <a href="my_orders.php" class="btn btn-sm btn-light border text-wine fw-semibold rounded-3 px-3 fs-7">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="bg-light text-muted border-bottom">
                                <tr>
                                    <th class="ps-4 py-3">Order ID</th>
                                    <th class="py-3">Course</th>
                                    <th class="py-3">Book Title</th>
                                    <th class="py-3 text-center">Qty</th>
                                    <th class="py-3">Total Amount</th>
                                    <th class="py-3">Payment Status</th>
                                    <th class="py-3">Collection</th>
                                    <th class="py-3 text-end pe-4">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                                    <?php while ($row = $recent_orders->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark">#ORD-<?php echo sprintf('%04d', $row['order_id']); ?></td>
                                            <td><span class="badge bg-light text-dark border rounded-2 px-2 py-1 fw-semibold"><?php echo htmlspecialchars($row['course_code']); ?></span></td>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td class="text-center fw-semibold"><?php echo (int)$row['quantity']; ?></td>
                                            <td class="fw-bold text-wine">GH₵ <?php echo number_format($row['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo get_status_badge_class($row['payment_status']); ?> rounded-pill px-3 py-1.5">
                                                    <?php echo htmlspecialchars($row['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo get_status_badge_class($row['collection_status']); ?> rounded-pill px-3 py-1.5">
                                                    <?php echo htmlspecialchars($row['collection_status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4 text-muted"><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                                            You have not placed any orders yet. 
                                            <a href="books.php" class="text-wine fw-bold ms-1 text-decoration-underline">Browse books</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Mobile Drawer Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
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

<?php 
$recent_stmt->close();
require_once '../includes/footer.php'; 
?>