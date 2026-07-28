<?php
// admin/dashboard.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

// Fallback status badge helper function
if (!function_exists('get_status_badge_class')) {
    function get_status_badge_class($status) {
        switch (strtolower($status)) {
            case 'approved':
            case 'collected':
            case 'paid':
                return 'bg-success';
            case 'pending':
                return 'bg-warning text-dark';
            case 'rejected':
            case 'cancelled':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }
}

// Single aggregated query for metrics
$metrics_query = "
    SELECT 
        (SELECT COUNT(*) FROM books) AS total_books,
        (SELECT COUNT(*) FROM students) AS total_students,
        (SELECT COUNT(*) FROM orders) AS total_orders,
        (SELECT COUNT(*) FROM orders WHERE collection_status = 'Collected') AS collected_books,
        IFNULL(SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END), 0) AS pending_payments,
        IFNULL(SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END), 0) AS verified_payments,
        IFNULL(SUM(CASE WHEN status = 'Approved' THEN amount ELSE 0 END), 0.00) AS total_revenue
    FROM payments
";

$metrics_result = $conn->query($metrics_query);
$metrics = $metrics_result ? $metrics_result->fetch_assoc() : [];

$total_books       = $metrics['total_books'] ?? 0;
$total_students    = $metrics['total_students'] ?? 0;
$total_orders      = $metrics['total_orders'] ?? 0;
$pending_payments  = $metrics['pending_payments'] ?? 0;
$verified_payments = $metrics['verified_payments'] ?? 0;
$collected_books   = $metrics['collected_books'] ?? 0;
$total_revenue     = $metrics['total_revenue'] ?? 0.00;

// Fetch Recent Orders (Limit 5)
$recent_orders_query = "
    SELECT o.order_id, s.fullname, s.index_number, b.title, o.total_amount, o.payment_status, o.collection_status, o.order_date 
    FROM orders o
    JOIN students s ON o.student_id = s.student_id
    JOIN books b ON o.book_id = b.book_id
    ORDER BY o.order_date DESC LIMIT 5
";
$recent_orders = $conn->query($recent_orders_query);

$page_title = "Admin Dashboard - Campus BookHub";
require_once '../includes/header.php';
?>

<!-- Google Fonts -->
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
        --sidebar-bg: #1f050b;
        --font-family: 'Plus Jakarta Sans', sans-serif;
        --sidebar-width: 260px;
    }

    body {
        font-family: var(--font-family);
        background-color: #f6f3f4;
        color: #2b2b2b;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }

    /* Outer Wrapper Container */
    #dashboard-layout {
        position: relative;
        width: 100%;
        min-height: 100vh;
    }

    /* STRICT FIXED SIDEBAR */
    #sidebar-wrapper {
        position: fixed;
        top: 0;
        left: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background: var(--sidebar-bg) !important;
        z-index: 1050;
        overflow-y: auto;
        border-right: 1px solid rgba(255, 255, 255, 0.05);
        transition: left 0.3s ease;
    }

    .sidebar-brand-box {
        background: rgba(255, 255, 255, 0.03);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 1rem;
        text-align: center;
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

    /* RIGHT SCROLLABLE CONTENT WRAPPER */
    #page-content-wrapper {
        margin-left: var(--sidebar-width);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        width: calc(100% - var(--sidebar-width));
        transition: margin-left 0.3s ease, width 0.3s ease;
    }

    main {
        flex: 1 0 auto;
    }

    /* Top Navigation Header */
    .top-navbar {
        background-color: #ffffff;
        border-bottom: 1px solid #eae2e4;
        height: 70px;
    }

    /* Metric Cards Styling */
    .metric-card {
        background: #ffffff;
        border: 1px solid #eee5e7;
        border-radius: 16px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(46, 8, 17, 0.03);
        height: 100%;
    }

    .metric-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(107, 29, 47, 0.08);
        border-color: #e3d2d6;
    }

    .icon-bubble {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .bubble-wine { background: rgba(107, 29, 47, 0.1); color: var(--wine-main); }
    .bubble-gold { background: rgba(212, 160, 23, 0.12); color: #b8860b; }
    .bubble-teal { background: rgba(13, 148, 136, 0.1); color: #0d9488; }
    .bubble-success { background: rgba(25, 135, 84, 0.1); color: #198754; }

    /* Primary Action Button */
    .btn-wine-gradient {
        background: var(--wine-gradient-card);
        color: #ffffff;
        border: none;
        padding: 9px 20px;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 6px 15px rgba(107, 29, 47, 0.25);
    }

    .btn-wine-gradient:hover {
        background: var(--wine-gradient);
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(107, 29, 47, 0.35);
    }

    /* Table Styles */
    .card-header-clean {
        background: transparent;
        border-bottom: 1px solid #f0e6e8;
        padding: 1.25rem 1.5rem;
    }

    .table-custom th {
        background-color: #fbf8f9;
        color: var(--wine-dark);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.72rem;
        letter-spacing: 0.6px;
        padding: 0.9rem 1rem;
        border-bottom: 1px solid #ebdada;
        white-space: nowrap;
    }

    .table-custom td {
        padding: 0.9rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f5eded;
        white-space: nowrap;
    }

    .text-wine { color: var(--wine-main); }

    /* Mobile Backdrop Overlay */
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

    /* Mobile & Tablet Adjustments (< 992px) */
    @media (max-width: 991.98px) {
        #sidebar-wrapper {
            left: calc(-1 * var(--sidebar-width));
        }

        #dashboard-layout.toggled #sidebar-wrapper {
            left: 0;
        }

        #dashboard-layout.toggled .sidebar-overlay {
            display: block;
        }

        #page-content-wrapper {
            margin-left: 0;
            width: 100%;
        }
    }
</style>

<div id="dashboard-layout">
    <!-- Backdrop overlay for mobile drawer -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Stationary Fixed Admin Sidebar -->
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand-box">
            <a href="dashboard.php" class="d-inline-block">
                <img src="../assets/images/logo.jpeg" alt="Campus BookHub Logo" class="sidebar-logo-img img-fluid">
            </a>
        </div>

        <div class="p-3">
            <small class="text-uppercase fw-bold text-muted px-2 mb-2 d-block fs-8" style="letter-spacing: 0.8px; color: rgba(255,255,255,0.4) !important;">
                Admin Control
            </small>

            <a href="dashboard.php" class="sidebar-link active mb-1">
                <i class="bi bi-grid-1x2-fill me-2.5"></i> Dashboard
            </a>
            <a href="books.php" class="sidebar-link mb-1">
                <i class="bi bi-journal-bookmark-fill me-2.5"></i> Manage Books
            </a>
            <a href="orders.php" class="sidebar-link mb-1">
                <i class="bi bi-cart-check-fill me-2.5"></i> Orders
            </a>
            <a href="payments.php" class="sidebar-link mb-1">
                <i class="bi bi-receipt-cutoff me-2.5"></i> Payments
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

    <!-- Scrollable Main Content Column -->
    <div id="page-content-wrapper">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg top-navbar px-4">
            <div class="container-fluid p-0 d-flex align-items-center">
                <button class="btn btn-light border me-3 d-lg-none rounded-3 p-1 px-2" id="sidebarToggle" type="button" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-4 text-wine"></i>
                </button>

                <span class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                    <i class="bi bi-speedometer2 text-wine d-none d-sm-inline"></i>
                    <span>Admin Workspace</span>
                </span>

                <div class="d-flex align-items-center gap-3 ms-auto">
                    <div class="badge bg-white text-dark border px-3 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2 fs-7">
                        <i class="bi bi-person-circle text-wine fs-6"></i>
                        <span class="fw-semibold d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Body Content -->
        <main class="p-4">
            <!-- Banner Header -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1 fs-4 fs-sm-3">System Dashboard</h3>
                    <p class="text-muted fs-7 mb-0">Track system performance, review payments, and dispatch course manuals.</p>
                </div>
                <div>
                    <a href="add_book.php" class="btn btn-wine-gradient text-white d-inline-flex align-items-center gap-2 text-nowrap">
                        <i class="bi bi-plus-circle-fill fs-6"></i> Add New Manual
                    </a>
                </div>
            </div>

            <!-- Primary Metrics -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="metric-card p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-bubble bubble-wine me-3">
                                <i class="bi bi-book-half"></i>
                            </div>
                            <div>
                                <small class="text-muted fw-semibold d-block fs-8">Total Books</small>
                                <h3 class="fw-bold text-dark mb-0"><?php echo number_format($total_books); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="metric-card p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-bubble bubble-teal me-3">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <small class="text-muted fw-semibold d-block fs-8">Registered Students</small>
                                <h3 class="fw-bold text-dark mb-0"><?php echo number_format($total_students); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="metric-card p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-bubble bubble-gold me-3">
                                <i class="bi bi-cart3"></i>
                            </div>
                            <div>
                                <small class="text-muted fw-semibold d-block fs-8">Total Orders</small>
                                <h3 class="fw-bold text-dark mb-0"><?php echo number_format($total_orders); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="metric-card p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-bubble bubble-success me-3">
                                <i class="bi bi-wallet2"></i>
                            </div>
                            <div>
                                <small class="text-muted fw-semibold d-block fs-8">Total Revenue</small>
                                <h3 class="fw-bold text-dark mb-0">GH₵ <?php echo number_format($total_revenue, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action Counters -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="metric-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted fw-semibold d-block fs-8">Pending Payments</small>
                                <h4 class="fw-bold text-warning mb-0 mt-1"><?php echo $pending_payments; ?></h4>
                            </div>
                            <a href="payments.php" class="btn btn-sm btn-outline-warning rounded-3 fs-8 fw-semibold px-3">Review</a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="metric-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted fw-semibold d-block fs-8">Verified Payments</small>
                                <h4 class="fw-bold text-success mb-0 mt-1"><?php echo $verified_payments; ?></h4>
                            </div>
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fs-8 fw-bold">Approved</span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="metric-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted fw-semibold d-block fs-8">Collected Manuals</small>
                                <h4 class="fw-bold text-wine mb-0 mt-1"><?php echo $collected_books; ?></h4>
                            </div>
                            <a href="orders.php" class="btn btn-sm btn-outline-secondary rounded-3 fs-8 fw-semibold px-3">View Queue</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Section -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header-clean d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-wine fs-5"></i> Recent Orders
                    </h6>
                    <a href="orders.php" class="btn btn-sm btn-light text-wine fw-semibold rounded-3 fs-7">
                        View All Orders <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Order ID</th>
                                    <th>Student</th>
                                    <th>Index Number</th>
                                    <th>Book Title</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Collection</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                                    <?php while ($row = $recent_orders->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-wine">#ORD-<?php echo sprintf('%04d', $row['order_id']); ?></td>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($row['fullname']); ?></td>
                                            <td><code class="bg-light text-dark px-2 py-1 rounded fs-8"><?php echo htmlspecialchars($row['index_number']); ?></code></td>
                                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td class="fw-bold text-dark">GH₵ <?php echo number_format($row['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo get_status_badge_class($row['payment_status']); ?> rounded-pill px-3 py-1 fs-8">
                                                    <?php echo htmlspecialchars($row['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo get_status_badge_class($row['collection_status']); ?> rounded-pill px-3 py-1 fs-8">
                                                    <?php echo htmlspecialchars($row['collection_status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-muted fs-8"><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                                            No orders placed yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer stays strictly within the right content column -->
        <?php require_once '../includes/footer.php'; ?>
    </div>
</div>

<!-- Mobile Menu JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const layout = document.getElementById('dashboard-layout');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function (e) {
                e.preventDefault();
                layout.classList.toggle('toggled');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function () {
                layout.classList.remove('toggled');
            });
        }
    });
</script>