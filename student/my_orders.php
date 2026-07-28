<?php
// student/my_orders.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_student();

$student_id = $_SESSION['user_id'];

// Fetch All Student Orders with Payment Info (including payment_reference)
$stmt = $conn->prepare("
    SELECT o.order_id, b.title, b.course_code, b.lecturer, o.quantity, o.total_amount, 
           o.payment_status, o.collection_status, o.order_date, o.payment_reference,
           p.transaction_id, p.payment_method, p.proof_image, p.status as payment_rec_status
    FROM orders o
    JOIN books b ON o.book_id = b.book_id
    LEFT JOIN payments p ON o.order_id = p.order_id
    WHERE o.student_id = ?
    ORDER BY o.order_date DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$orders = $stmt->get_result();

$page_title = "My Orders - Campus BookHub";
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

    /* Custom Table Styling */
    .orders-table thead th {
        background-color: #fcfafb;
        color: #5c4d52;
        font-weight: 600;
        text-uppercase: uppercase;
        font-size: 0.72rem;
        letter-spacing: 0.5px;
        padding: 1rem 0.85rem;
        border-bottom: 1px solid #eae2e4;
    }

    .orders-table tbody td {
        padding: 1.1rem 0.85rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1ebed;
    }

    .orders-table tbody tr:hover {
        background-color: rgba(107, 29, 47, 0.015);
    }

    .txn-code {
        font-family: monospace;
        font-size: 0.82rem;
        background: #f4eff1;
        color: #552835;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
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
            max-width: 1280px;
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
            <a href="my_orders.php" class="sidebar-link active mb-1">
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

    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg top-navbar px-4">
            <div class="container-fluid p-0 d-flex align-items-center">
                <button class="btn btn-light border me-3 d-lg-none rounded-3 p-1 px-2" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-4 text-wine"></i>
                </button>

                <span class="navbar-brand fw-semibold text-muted fs-6 mb-0">Order History & Verification</span>

                <div class="d-flex align-items-center gap-2 ms-auto">
                    <div class="bg-wine-subtle border border-wine-subtle px-3 py-1.5 rounded-pill d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle text-wine fs-6"></i>
                        <span class="fw-semibold text-dark fs-7"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    </div>
                </div>
            </div>
        </nav>

        <main class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">My Manual Orders</h4>
                    <p class="text-muted fs-7 mb-0">Track payment verification and pickup status from your Course Rep.</p>
                </div>
            </div>

            <!-- Orders Table Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 fs-7 orders-table">
                            <thead>
                                <tr>
                                    <th class="ps-4">Order ID</th>
                                    <th>Course</th>
                                    <th>Book Title</th>
                                    <th>Qty</th>
                                    <th>Total Price</th>
                                    <th>Txn ID</th>
                                    <th>Payment Status</th>
                                    <th>Pickup Status</th>
                                    <th class="pe-4 text-end">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($orders && $orders->num_rows > 0): ?>
                                    <?php while ($ord = $orders->fetch_assoc()): ?>
                                        <?php 
                                            // Determine Transaction ID from payments table or orders payment_reference
                                            $txn = $ord['transaction_id'] ?? $ord['payment_reference'] ?? 'N/A';
                                            $p_status = strtolower(trim($ord['payment_status'] ?? ''));
                                            $c_status = strtolower(trim($ord['collection_status'] ?? ''));
                                        ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-wine">#ORD-<?php echo sprintf('%04d', $ord['order_id']); ?></td>
                                            <td>
                                                <span class="badge bg-wine-subtle text-wine border border-wine-subtle rounded-pill px-2.5 py-1">
                                                    <?php echo htmlspecialchars($ord['course_code']); ?>
                                                </span>
                                            </td>
                                            <td class="fw-semibold text-dark" style="max-width: 220px;"><?php echo htmlspecialchars($ord['title']); ?></td>
                                            <td class="fw-medium text-muted"><?php echo $ord['quantity']; ?></td>
                                            <td class="fw-bold text-dark">GH₵ <?php echo number_format($ord['total_amount'], 2); ?></td>
                                            <td><span class="txn-code"><?php echo htmlspecialchars($txn); ?></span></td>
                                            
                                            <!-- Payment Status Badge -->
                                            <td>
                                                <?php if ($p_status === 'paid' || $p_status === 'verified'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold">
                                                        <i class="bi bi-check-circle-fill me-1"></i> Verified
                                                    </span>
                                                <?php elseif ($p_status === 'failed' || $p_status === 'rejected'): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-semibold">
                                                        <i class="bi bi-x-circle-fill me-1"></i> Failed
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">
                                                        <i class="bi bi-clock-history me-1"></i> Pending
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Pickup Status Badge -->
                                            <td>
                                                <?php if ($c_status === 'collected'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold">
                                                        <i class="bi bi-box-seam-fill me-1"></i> Collected
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">
                                                        <i class="bi bi-hourglass-split me-1"></i> Pending
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="pe-4 text-end text-muted"><?php echo date('M d, Y', strtotime($ord['order_date'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <div class="bg-wine-subtle rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                                <i class="bi bi-bag-x display-6 text-wine"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">No order records found</h6>
                                            <p class="text-muted fs-7 mb-3">You haven't placed any manual orders yet.</p>
                                            <a href="books.php" class="btn btn-wine btn-sm rounded-3 px-3 py-2 fw-semibold">
                                                Order Books Now
                                            </a>
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

<?php 
$stmt->close();
require_once '../includes/footer.php'; 
?>