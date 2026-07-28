<?php
// admin/reports.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

// Analytical Metrics
$revenue_total = $conn->query("SELECT IFNULL(SUM(amount), 0) FROM payments WHERE status = 'Approved'")->fetch_row()[0] ?? 0;
$total_sold_books = $conn->query("SELECT IFNULL(SUM(quantity), 0) FROM orders WHERE payment_status = 'Verified'")->fetch_row()[0] ?? 0;
$pending_payment_count = $conn->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'Pending'")->fetch_row()[0] ?? 0;
$uncollected_count = $conn->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'Verified' AND collection_status = 'Pending'")->fetch_row()[0] ?? 0;

// Sales summary by course code
$course_sales = $conn->query("
    SELECT b.course_code, b.title, SUM(o.quantity) as total_qty, SUM(o.total_amount) as total_rev
    FROM orders o
    JOIN books b ON o.book_id = b.book_id
    WHERE o.payment_status = 'Verified'
    GROUP BY b.book_id
    ORDER BY total_rev DESC
");

$page_title = "Reports & Analytics - Campus BookHub";
require_once '../includes/header.php';
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

    /* Analytical Cards */
    .metric-card {
        border-radius: 16px;
        background: #ffffff;
        border: 1px solid #f0e6e8;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05) !important;
    }

    .metric-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    /* Table Styling */
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

    /* Print Stylesheet Adjustments */
    @media print {
        #sidebar-wrapper, .top-navbar, .no-print, .sidebar-overlay {
            display: none !important;
        }

        body {
            background-color: #ffffff !important;
        }

        #wrapper {
            display: block !important;
        }

        #page-content-wrapper {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
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

    @media (min-width: 1400px) {
        main {
            max-width: 1600px;
            margin-left: auto;
            margin-right: auto;
        }
    }
</style>

<div id="wrapper">
    <!-- Mobile Drawer Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Admin Sidebar -->
    <aside id="sidebar-wrapper">
        <!-- Logo Branding Header -->
        <div class="sidebar-brand-box px-3 py-3 text-center">
            <a href="dashboard.php" class="d-inline-block">
                <img src="../assets/images/logo.jpeg" alt="Campus BookHub Logo" class="sidebar-logo-img img-fluid">
            </a>
        </div>

        <div class="p-3">
            <small class="text-uppercase fw-bold text-muted px-2 mb-2 d-block fs-8" style="letter-spacing: 0.8px; color: rgba(255,255,255,0.4) !important;">
                Admin Control
            </small>

            <a href="dashboard.php" class="sidebar-link mb-1">
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
            <a href="reports.php" class="sidebar-link active mb-1">
                <i class="bi bi-bar-chart-line-fill me-2.5"></i> Reports
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
                    <i class="bi bi-bar-chart-line-fill text-wine d-none d-sm-inline"></i>
                    <span>System Analytics & Reports</span>
                </span>

                <div class="d-flex align-items-center gap-3 ms-auto">
                    <div class="badge bg-white text-dark border px-3 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2 fs-7">
                        <i class="bi bi-person-circle text-wine fs-6"></i>
                        <span class="fw-semibold d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    </div>
                </div>
            </div>
        </nav>

        <main class="p-4">
            <!-- Header Title Banner -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1 fs-4 fs-sm-3">Financial & Sales Summary</h3>
                    <p class="text-muted fs-7 mb-0">Overview of total revenue generated and manual distribution statistics.</p>
                </div>
                <div class="no-print">
                    <button onclick="window.print();" class="btn btn-outline-secondary rounded-3 px-3 py-2 fw-semibold fs-7 d-inline-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-printer fs-6 text-wine"></i>
                        <span>Print Report</span>
                    </button>
                </div>
            </div>

            <!-- Analytical Cards -->
            <div class="row g-3 mb-4">
                <!-- Verified Revenue -->
                <div class="col-6 col-lg-3">
                    <div class="metric-card p-3 shadow-sm h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted fw-semibold fs-8 text-uppercase" style="letter-spacing: 0.5px;">Verified Revenue</span>
                            <div class="metric-icon-box bg-success-subtle text-success">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-success mb-0 fs-4 fs-sm-3">GH₵ <?php echo number_format($revenue_total, 2); ?></h3>
                    </div>
                </div>

                <!-- Total Books Sold -->
                <div class="col-6 col-lg-3">
                    <div class="metric-card p-3 shadow-sm h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted fw-semibold fs-8 text-uppercase" style="letter-spacing: 0.5px;">Total Books Sold</span>
                            <div class="metric-icon-box" style="background-color: #fce8ec; color: var(--wine-main);">
                                <i class="bi bi-journal-check"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-dark mb-0 fs-4 fs-sm-3"><?php echo number_format($total_sold_books); ?> <span class="fs-7 text-muted fw-normal">Units</span></h3>
                    </div>
                </div>

                <!-- Unverified Payments -->
                <div class="col-6 col-lg-3">
                    <div class="metric-card p-3 shadow-sm h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted fw-semibold fs-8 text-uppercase" style="letter-spacing: 0.5px;">Unverified Payments</span>
                            <div class="metric-icon-box bg-warning-subtle text-warning-emphasis">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-dark mb-0 fs-4 fs-sm-3"><?php echo number_format($pending_payment_count); ?> <span class="fs-7 text-muted fw-normal">Pending</span></h3>
                    </div>
                </div>

                <!-- Uncollected Pickups -->
                <div class="col-6 col-lg-3">
                    <div class="metric-card p-3 shadow-sm h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted fw-semibold fs-8 text-uppercase" style="letter-spacing: 0.5px;">Uncollected Pickups</span>
                            <div class="metric-icon-box bg-info-subtle text-info-emphasis">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-dark mb-0 fs-4 fs-sm-3"><?php echo number_format($uncollected_count); ?> <span class="fs-7 text-muted fw-normal">Awaiting</span></h3>
                    </div>
                </div>
            </div>

            <!-- Revenue Breakdown Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header-clean d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-journal-text text-wine fs-5"></i> Sales Breakdown by Manual
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Course Code</th>
                                    <th>Book Title</th>
                                    <th>Total Quantity Sold</th>
                                    <th class="text-end pe-4">Revenue Generated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($course_sales && $course_sales->num_rows > 0): ?>
                                    <?php while ($cs = $course_sales->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <span class="badge bg-light text-dark border px-2.5 py-1 rounded-2 fs-8 fw-semibold">
                                                    <?php echo htmlspecialchars($cs['course_code']); ?>
                                                </span>
                                            </td>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($cs['title']); ?></td>
                                            <td class="fw-medium text-dark"><?php echo number_format($cs['total_qty']); ?> copies</td>
                                            <td class="text-end pe-4 fw-bold text-success fs-7">
                                                GH₵ <?php echo number_format($cs['total_rev'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                                            No verified sales data available yet.
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

<?php require_once '../includes/footer.php'; ?>