<?php
// admin/payments.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

$msg = '';
$error = '';

// Handle Payment Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session token.";
    } else {
        $payment_id = intval($_POST['payment_id']);
        $order_id = intval($_POST['order_id']);
        $action = $_POST['action']; // 'approve' or 'reject'

        if ($action === 'approve') {
            // Update Payment Status
            $p_stmt = $conn->prepare("UPDATE payments SET status = 'Approved' WHERE payment_id = ?");
            $p_stmt->bind_param("i", $payment_id);
            $p_stmt->execute();
            $p_stmt->close();

            // Update Order Payment Status
            $o_stmt = $conn->prepare("UPDATE orders SET payment_status = 'Verified' WHERE order_id = ?");
            $o_stmt->bind_param("i", $order_id);
            $o_stmt->execute();
            $o_stmt->close();

            $msg = "Payment for Order #ORD-" . sprintf('%04d', $order_id) . " approved successfully!";
        } elseif ($action === 'reject') {
            $p_stmt = $conn->prepare("UPDATE payments SET status = 'Rejected' WHERE payment_id = ?");
            $p_stmt->bind_param("i", $payment_id);
            $p_stmt->execute();
            $p_stmt->close();

            $o_stmt = $conn->prepare("UPDATE orders SET payment_status = 'Rejected' WHERE order_id = ?");
            $o_stmt->bind_param("i", $order_id);
            $o_stmt->execute();
            $o_stmt->close();

            $msg = "Payment rejected.";
        }
    }
}

// Fetch All Payments
$payments = $conn->query("
    SELECT p.payment_id, p.order_id, p.transaction_id, p.payment_method, p.amount, p.proof_image, p.status, p.payment_date, s.fullname, s.index_number, b.title
    FROM payments p
    JOIN orders o ON p.order_id = o.order_id
    JOIN students s ON o.student_id = s.student_id
    JOIN books b ON o.book_id = b.book_id
    ORDER BY p.payment_date DESC
");

$page_title = "Manage Payments - Campus BookHub";
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

    /* View Receipt Button */
    .btn-receipt {
        background-color: #f3e8eb;
        color: var(--wine-main);
        border: 1px solid #e2ccd2;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .btn-receipt:hover {
        background-color: var(--wine-main);
        color: #ffffff;
        border-color: var(--wine-main);
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
            <a href="payments.php" class="sidebar-link active mb-1">
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
                    <i class="bi bi-receipt-cutoff text-wine d-none d-sm-inline"></i>
                    <span>Payment Verification Panel</span>
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
                    <h3 class="fw-bold text-dark mb-1 fs-4 fs-sm-3">Manual Payment Verifications</h3>
                    <p class="text-muted fs-7 mb-0">Cross-reference uploaded Mobile Money / Bank screenshots against receipts.</p>
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

            <!-- Payments Table Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header-clean d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-wallet2 text-wine fs-5"></i> Transaction History & Approvals
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Txn ID</th>
                                    <th>Order</th>
                                    <th>Student</th>
                                    <th>Book Title</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Proof Screenshot</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($payments && $payments->num_rows > 0): ?>
                                    <?php while ($p = $payments->fetch_assoc()): ?>
                                        <?php $p_status = strtolower(trim($p['status'] ?? '')); ?>
                                        <tr>
                                            <td class="ps-4 fw-bold"><code><?php echo htmlspecialchars($p['transaction_id']); ?></code></td>
                                            <td class="fw-bold text-wine">#ORD-<?php echo sprintf('%04d', $p['order_id']); ?></td>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($p['fullname']); ?></td>
                                            <td class="fw-medium text-dark"><?php echo htmlspecialchars($p['title']); ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark border px-2.5 py-1 rounded-2 fs-8">
                                                    <i class="bi bi-credit-card-2-front me-1 text-muted"></i>
                                                    <?php echo htmlspecialchars($p['payment_method']); ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold text-dark">GH₵ <?php echo number_format($p['amount'], 2); ?></td>
                                            
                                            <!-- Proof Screenshot Column with Modal Trigger -->
                                            <td>
                                                <?php if (!empty($p['proof_image']) && file_exists('../' . $p['proof_image'])): ?>
                                                    <button type="button" class="btn btn-sm btn-receipt rounded-3 fs-8 py-1 px-2.5" data-bs-toggle="modal" data-bs-target="#receiptModal<?php echo $p['payment_id']; ?>">
                                                        <i class="bi bi-file-earmark-image me-1"></i> View Receipt
                                                    </button>

                                                    <!-- Receipt Lightbox Modal -->
                                                    <div class="modal fade" id="receiptModal<?php echo $p['payment_id']; ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                                            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                                                <div class="modal-header bg-light border-bottom py-3 px-4">
                                                                    <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                                                                        <i class="bi bi-receipt text-wine"></i>
                                                                        Payment Receipt — Order #ORD-<?php echo sprintf('%04d', $p['order_id']); ?>
                                                                    </h6>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body text-center p-4 bg-dark-subtle">
                                                                    <img src="../<?php echo htmlspecialchars($p['proof_image']); ?>" alt="Proof Receipt" class="img-fluid rounded-3 shadow-sm" style="max-height: 75vh; object-fit: contain;">
                                                                </div>
                                                                <div class="modal-footer bg-white border-top py-2.5 px-4 d-flex justify-content-between align-items-center">
                                                                    <span class="text-muted fs-8">Txn ID: <strong><?php echo htmlspecialchars($p['transaction_id']); ?></strong></span>
                                                                    <a href="../<?php echo htmlspecialchars($p['proof_image']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-3 fs-8">
                                                                        <i class="bi bi-box-arrow-up-right me-1"></i> Open Original
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted fs-8 fst-italic">No Screenshot</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Status Column -->
                                            <td>
                                                <?php if ($p_status === 'approved'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fs-8 fw-bold">
                                                        <i class="bi bi-check-circle-fill me-1"></i> Approved
                                                    </span>
                                                <?php elseif ($p_status === 'rejected'): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1.5 fs-8 fw-bold">
                                                        <i class="bi bi-x-circle-fill me-1"></i> Rejected
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1.5 fs-8 fw-bold">
                                                        <i class="bi bi-hourglass-split me-1"></i> Pending
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Actions Column -->
                                            <td class="text-end pe-4">
                                                <?php if ($p_status === 'pending'): ?>
                                                    <form action="payments.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="payment_id" value="<?php echo $p['payment_id']; ?>">
                                                        <input type="hidden" name="order_id" value="<?php echo $p['order_id']; ?>">
                                                        
                                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success rounded-3 fw-semibold px-2.5 py-1 fs-8 me-1" onclick="return confirm('Approve payment for Order #ORD-<?php echo sprintf('%04d', $p['order_id']); ?>?');">
                                                            <i class="bi bi-check-lg me-1"></i> Approve
                                                        </button>
                                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger rounded-3 fw-semibold px-2.5 py-1 fs-8" onclick="return confirm('Reject this payment?');">
                                                            <i class="bi bi-x-lg me-1"></i> Reject
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted fs-8 fw-semibold">
                                                        <i class="bi bi-lock-fill me-1"></i> Processed
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                                            No payment records logged yet.
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