<?php
// admin/orders.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

$msg = '';
$error = '';

// Handle Actions (Payment Verification & Collection Confirmation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session token.";
    } else {
        $order_id = intval($_POST['order_id']);
        $admin_id = $_SESSION['user_id'];

        // 1. Action: Verify Payment
        if ($_POST['action'] === 'verify_payment') {
            $upd = $conn->prepare("UPDATE orders SET payment_status = 'Verified' WHERE order_id = ?");
            $upd->bind_param("i", $order_id);
            if ($upd->execute()) {
                $msg = "Payment for Order #ORD-" . sprintf('%04d', $order_id) . " verified successfully!";
            } else {
                $error = "Failed to update payment status.";
            }
            $upd->close();
        }

        // 2. Action: Handover Physical Book
        if ($_POST['action'] === 'collect') {
            $chk = $conn->prepare("SELECT payment_status, collection_status FROM orders WHERE order_id = ?");
            $chk->bind_param("i", $order_id);
            $chk->execute();
            $ord_data = $chk->get_result()->fetch_assoc();
            $chk->close();

            if ($ord_data) {
                $p_stat = strtolower(trim($ord_data['payment_status'] ?? ''));
                if (!in_array($p_stat, ['paid', 'verified'])) {
                    $error = "Payment must be verified before marking book as collected.";
                } elseif (strtolower(trim($ord_data['collection_status'] ?? '')) === 'collected') {
                    $error = "Book is already marked as collected.";
                } else {
                    $upd = $conn->prepare("UPDATE orders SET collection_status = 'Collected' WHERE order_id = ?");
                    $upd->bind_param("i", $order_id);
                    $upd->execute();
                    $upd->close();

                    $col = $conn->prepare("INSERT INTO collection (order_id, admin_id, notes) VALUES (?, ?, 'Verified and handed over in-person')");
                    $col->bind_param("ii", $order_id, $admin_id);
                    $col->execute();
                    $col->close();

                    $msg = "Order #ORD-" . sprintf('%04d', $order_id) . " marked as Handed Over!";
                }
            }
        }
    }
}

// Fetch Orders
$orders_result = $conn->query("
    SELECT o.order_id, s.fullname, s.index_number, s.phone, b.title, o.quantity, o.total_amount, o.payment_status, o.collection_status, o.order_date
    FROM orders o
    JOIN students s ON o.student_id = s.student_id
    JOIN books b ON o.book_id = b.book_id
    ORDER BY o.order_date DESC
");

$all_orders = [];
if ($orders_result && $orders_result->num_rows > 0) {
    while ($row = $orders_result->fetch_assoc()) {
        $all_orders[] = $row;
    }
}

// Fetch Total Orders Classified by Each Book (Summary for Pickup)
$book_summary = $conn->query("
    SELECT b.book_id, b.title, b.course_code, b.price, 
           COALESCE(SUM(o.quantity), 0) as total_quantity,
           COUNT(o.order_id) as total_orders_count
    FROM books b
    LEFT JOIN orders o ON b.book_id = o.book_id
    GROUP BY b.book_id, b.title, b.course_code, b.price
    ORDER BY total_quantity DESC
");

$page_title = "Manage Orders - Campus BookHub";
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

    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
        overflow: hidden; /* Prevent body scroll */
        font-family: var(--font-family);
        background-color: #f6f3f4;
        color: #2b2b2b;
    }

    #wrapper {
        display: flex;
        width: 100vw;
        height: 100vh;
        overflow: hidden;
    }

    /* Fixed Sidebar Styling */
    #sidebar-wrapper {
        width: 260px;
        min-width: 260px;
        background: var(--sidebar-bg) !important;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1050;
        overflow-y: auto;
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

    /* Independent Scrollable Page Content Wrapper */
    #page-content-wrapper {
        margin-left: 260px;
        flex: 1;
        width: calc(100% - 260px);
        height: 100vh;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }

    /* Top Navbar */
    .top-navbar {
        background-color: #ffffff;
        border-bottom: 1px solid #eae2e4;
        min-height: 70px;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    /* Action Buttons & Helpers */
    .btn-wine-gradient {
        background: var(--wine-gradient-card);
        color: #ffffff;
        border: none;
        padding: 7px 16px;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(107, 29, 47, 0.25);
    }

    .btn-wine-gradient:hover {
        background: var(--wine-gradient);
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(107, 29, 47, 0.35);
    }

    .text-wine { color: var(--wine-main); }

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

    /* Interactive Badge Button */
    .btn-badge-pending {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
        transition: all 0.2s ease;
    }

    .btn-badge-pending:hover {
        background-color: #ffe8a1;
        color: #533f03;
        transform: scale(1.03);
    }

    /* Overlay for Mobile Drawer */
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

    .hidden-order-row {
        display: none;
    }

    /* Responsive Queries */
    @media (max-width: 991.98px) {
        #sidebar-wrapper {
            margin-left: -260px;
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.3);
        }

        #page-content-wrapper {
            margin-left: 0;
            width: 100%;
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
            width: 100%;
        }
    }
</style>

<div id="wrapper">
    <!-- Overlay for closing drawer on mobile tap -->
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
            <a href="orders.php" class="sidebar-link active mb-1">
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

    <!-- Page Content Area (Scrolls independently) -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg top-navbar px-4">
            <div class="container-fluid p-0 d-flex align-items-center">
                <!-- Mobile Sidebar Toggle -->
                <button class="btn btn-light border me-3 d-lg-none rounded-3 p-1 px-2" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-4 text-wine"></i>
                </button>

                <span class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                    <i class="bi bi-cart-check-fill text-wine d-none d-sm-inline"></i>
                    <span>Order & Pickup Tracking</span>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1 fs-4 fs-sm-3">All Student Orders</h3>
                    <p class="text-muted fs-7 mb-0">Track order fulfillment and confirm physical manual collections.</p>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($msg): ?>
                <div class="alert alert-success rounded-3 fs-7 py-2.5 px-3 alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger rounded-3 fs-7 py-2.5 px-3 alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Book Pickup Summary Section -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header-clean d-flex justify-content-between align-items-center bg-white">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-journal-check text-wine fs-5"></i> Book Pickup Summary (Total Demand by Book)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Course Code</th>
                                    <th>Book Title</th>
                                    <th>Price</th>
                                    <th>Total Units Ordered</th>
                                    <th class="text-end pe-4">Total Order Transactions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($book_summary && $book_summary->num_rows > 0): ?>
                                    <?php while ($bs = $book_summary->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4"><code class="px-2 py-1 bg-light rounded text-dark border fs-8 fw-bold"><?php echo htmlspecialchars($bs['course_code']); ?></code></td>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($bs['title']); ?></td>
                                            <td class="text-secondary">GH₵ <?php echo number_format($bs['price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fs-8 fw-bold">
                                                    <?php echo $bs['total_quantity']; ?> Units
                                                </span>
                                            </td>
                                            <td class="text-end pe-4 fw-bold text-wine"><?php echo $bs['total_orders_count']; ?> Orders</td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No books available or ordered yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Orders Table Card with Search & Limit Toggle -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header-clean d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-wine fs-5"></i> Recent Orders Log
                    </h6>
                    
                    <!-- Index Number Search Engine -->
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-light border-end-0 rounded-start-3 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="orderSearchInput" class="form-control bg-light border-start-0 rounded-end-3 fs-7" placeholder="Search Index No. or Student...">
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0" id="ordersTable">
                            <thead>
                                <tr>
                                    <th class="ps-4">Order ID</th>
                                    <th>Student Name</th>
                                    <th>Index Number</th>
                                    <th>Phone</th>
                                    <th>Book Title</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Collection</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($all_orders)): ?>
                                    <?php foreach ($all_orders as $index => $ord): ?>
                                        <?php 
                                            $p_status = strtolower(trim($ord['payment_status'] ?? ''));
                                            $c_status = strtolower(trim($ord['collection_status'] ?? ''));
                                            $is_verified = ($p_status === 'verified' || $p_status === 'paid');
                                            $is_collected = ($c_status === 'collected');
                                            $is_hidden = ($index >= 20); // Hide items beyond top 20
                                        ?>
                                        <tr class="order-row <?php echo $is_hidden ? 'hidden-order-row' : ''; ?>" data-index="<?php echo htmlspecialchars($ord['index_number']); ?>" data-name="<?php echo htmlspecialchars(strtolower($ord['fullname'])); ?>" data-ordid="#ORD-<?php echo sprintf('%04d', $ord['order_id']); ?>">
                                            <td class="ps-4 fw-bold text-wine">#ORD-<?php echo sprintf('%04d', $ord['order_id']); ?></td>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($ord['fullname']); ?></td>
                                            <td><code class="px-2 py-1 bg-light rounded text-dark border fs-8 fw-bold"><?php echo htmlspecialchars($ord['index_number']); ?></code></td>
                                            <td class="text-secondary"><?php echo htmlspecialchars($ord['phone']); ?></td>
                                            <td class="fw-medium text-dark"><?php echo htmlspecialchars($ord['title']); ?></td>
                                            <td class="fw-bold"><?php echo $ord['quantity']; ?></td>
                                            <td class="fw-bold text-dark">GH₵ <?php echo number_format($ord['total_amount'], 2); ?></td>
                                            
                                            <!-- Payment Status Badge / Click to Verify -->
                                            <td>
                                                <?php if ($is_verified): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fs-8 fw-bold">
                                                        <i class="bi bi-patch-check-fill me-1"></i> Verified
                                                    </span>
                                                <?php else: ?>
                                                    <form action="orders.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="action" value="verify_payment">
                                                        <input type="hidden" name="order_id" value="<?php echo $ord['order_id']; ?>">
                                                        <button type="submit" class="badge rounded-pill px-3 py-1.5 fs-8 fw-bold btn-badge-pending" onclick="return confirm('Confirm payment received for Order #ORD-<?php echo sprintf('%04d', $ord['order_id']); ?>?');" title="Click to verify payment">
                                                            <i class="bi bi-hourglass-split me-1"></i> Pending
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Collection Status Badge -->
                                            <td>
                                                <?php if ($is_collected): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fs-8 fw-bold">
                                                        <i class="bi bi-check-circle-fill me-1"></i> Handed Over
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1.5 fs-8 fw-bold">
                                                        <i class="bi bi-clock me-1"></i> Pending
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Action Column -->
                                            <td class="text-end pe-4">
                                                <?php if ($is_verified && !$is_collected): ?>
                                                    <form action="orders.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="action" value="collect">
                                                        <input type="hidden" name="order_id" value="<?php echo $ord['order_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success rounded-3 fw-semibold px-3 py-1.5 fs-8" onclick="return confirm('Confirm physical book handover to student?');">
                                                            <i class="bi bi-box-seam me-1"></i> Mark Handed Over
                                                        </button>
                                                    </form>
                                                <?php elseif ($is_collected): ?>
                                                    <span class="text-success fw-semibold fs-8 d-inline-flex align-items-center">
                                                        <i class="bi bi-check-all fs-5 me-1 text-success"></i> Handed Over
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted fs-8 fst-italic">Payment Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                                            No orders placed yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Toggle Button for Older Orders -->
                    <?php if (count($all_orders) > 20): ?>
                        <div class="p-3 text-center bg-light border-top" id="loadMoreContainer">
                            <button type="button" id="toggleOlderBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-4 fw-semibold fs-7">
                                Show Older Orders (<?php echo count($all_orders) - 20; ?> Hidden)
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>

        <!-- The footer is dynamically included inside #page-content-wrapper -->
        <?php require_once '../includes/footer.php'; ?>

<!-- Mobile Drawer & Search Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Mobile Sidebar Toggler
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

        // Live Search Filter
        const searchInput = document.getElementById('orderSearchInput');
        const orderRows = document.querySelectorAll('.order-row');
        const toggleBtn = document.getElementById('toggleOlderBtn');
        const loadMoreContainer = document.getElementById('loadMoreContainer');
        let isExpanded = false;

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();

                orderRows.forEach((row, index) => {
                    const indexNum = row.getAttribute('data-index').toLowerCase();
                    const name = row.getAttribute('data-name').toLowerCase();
                    const ordId = row.getAttribute('data-ordid').toLowerCase();

                    if (indexNum.includes(query) || name.includes(query) || ordId.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (query !== '') {
                    if (loadMoreContainer) loadMoreContainer.style.display = 'none';
                } else {
                    if (loadMoreContainer) loadMoreContainer.style.display = 'block';
                    // Re-apply 20 limit if search cleared
                    orderRows.forEach((row, index) => {
                        if (index >= 20 && !isExpanded) {
                            row.style.display = 'none';
                        }
                    });
                }
            });
        }

        // Toggle Older Orders Limit
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                isExpanded = !isExpanded;
                const hiddenRows = document.querySelectorAll('.hidden-order-row');

                hiddenRows.forEach(row => {
                    row.style.display = isExpanded ? '' : 'none';
                });

                toggleBtn.textContent = isExpanded ? 'Collapse Older Orders' : 'Show Older Orders (<?php echo count($all_orders) - 20; ?> Hidden)';
            });
        }
    });
</script>