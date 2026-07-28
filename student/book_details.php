<?php
// student/book_details.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_student();

$book_id = intval($_GET['id'] ?? 0);
if ($book_id <= 0) {
    header("Location: books.php");
    exit();
}

// 1. Fetch Book Information
$stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    header("Location: books.php");
    exit();
}

// 2. Fetch Logged-in Student Details from the 'students' table
$student_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$user_stmt->bind_param("i", $student_id);
$user_stmt->execute();
$student = $user_stmt->get_result()->fetch_assoc() ?? [];
$user_stmt->close();

// Set student metadata with safe fallbacks to match your database columns
$student_name   = $student['fullname'] ?? $student['full_name'] ?? ($_SESSION['name'] ?? 'Student');
$student_email  = $student['email'] ?? ($_SESSION['email'] ?? ('student' . $student_id . '@campusbookhub.com'));
$index_number   = $student['index_number'] ?? ('INDEX-' . $student_id);
$student_class  = $student['class'] ?? 'N/A';
$dept_level     = $student['departmentlevel'] ?? $student['department'] ?? 'N/A';

$page_title = htmlspecialchars($book['title']) . " - Campus BookHub";
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

    .text-wine { color: var(--wine-main) !important; }
    .bg-wine { background-color: var(--wine-main) !important; color: #ffffff !important; }
    .bg-wine-subtle { background-color: rgba(107, 29, 47, 0.08) !important; color: var(--wine-main) !important; }
    .border-wine-subtle { border-color: rgba(107, 29, 47, 0.2) !important; }

    .btn-wine {
        background: var(--wine-gradient);
        color: #ffffff !important;
        border: none;
        transition: all 0.3s ease;
    }

    .btn-wine:hover, .btn-wine:focus {
        background: var(--wine-gradient-card);
        color: #ffffff !important;
        box-shadow: 0 6px 18px rgba(107, 29, 47, 0.35);
    }

    .btn-wine:disabled {
        background: #d6cbce !important;
        color: #7a6e71 !important;
        box-shadow: none;
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

            <a href="dashboard.php" class="sidebar-link mb-1">
                <i class="bi bi-grid-1x2-fill me-2.5"></i> Dashboard
            </a>
            <a href="books.php" class="sidebar-link active mb-1">
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
                    <i class="bi bi-credit-card-2-front-fill text-wine fs-5"></i>
                    <span>Paystack MoMo Checkout</span>
                </span>

                <div class="d-flex align-items-center gap-3 ms-auto">
                    <a href="books.php" class="btn btn-outline-secondary btn-sm rounded-3 d-flex align-items-center gap-1">
                        <i class="bi bi-arrow-left"></i>
                        <span class="d-none d-sm-inline">Back to Catalog</span>
                    </a>
                </div>
            </div>
        </nav>

        <main class="p-4">
            <div class="row g-4">
                <!-- Book Info Column -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <?php if (!empty($book['image']) && file_exists('../' . $book['image'])): ?>
                            <img src="../<?php echo htmlspecialchars($book['image']); ?>" class="img-fluid rounded-3 mb-3 shadow-sm" style="max-height: 320px; width: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-wine-subtle rounded-3 d-flex align-items-center justify-content-center mb-3" style="height: 260px;">
                                <i class="bi bi-book display-1 text-wine"></i>
                            </div>
                        <?php endif; ?>

                        <span class="badge bg-wine-subtle text-wine border border-wine-subtle rounded-pill align-self-start px-3 py-1.5 fw-semibold mb-2">
                            <?php echo htmlspecialchars($book['course_code']); ?>
                        </span>

                        <h4 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($book['title']); ?></h4>
                        <p class="text-muted fs-7 mb-3"><i class="bi bi-person-fill me-1 text-wine"></i> Lecturer: <strong><?php echo htmlspecialchars($book['lecturer']); ?></strong></p>

                        <div class="bg-light p-3 rounded-3 mb-3 border">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted fw-semibold fs-7">Unit Price:</span>
                                <span class="fs-4 fw-bold text-wine">GH₵ <?php echo number_format($book['price'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2 fs-7">
                                <span class="text-muted">Stock Status:</span>
                                <?php if ($book['stock'] > 0): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill"><?php echo $book['stock']; ?> Copies Available</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h6 class="fw-bold fs-7 mb-1 text-dark">Manual Description:</h6>
                        <p class="text-muted fs-7 mb-0"><?php echo nl2br(htmlspecialchars($book['description'] ?? 'No description provided.')); ?></p>
                    </div>
                </div>

                <!-- Paystack Form Column -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white h-100">
                        <div class="alert alert-light border border-wine-subtle bg-wine-subtle text-dark rounded-3 d-flex align-items-center gap-3 mb-4">
                            <i class="bi bi-shield-check fs-2 text-wine"></i>
                            <div>
                                <strong class="d-block fs-6 text-wine">Paystack Mobile Money Gateway</strong>
                                <small class="text-muted">Paying as <strong><?php echo htmlspecialchars($student_name); ?></strong> (Index No: <strong><?php echo htmlspecialchars($index_number); ?></strong>)</small>
                            </div>
                        </div>

                        <form id="paystackCheckoutForm">
                            <input type="hidden" id="book_id" value="<?php echo $book_id; ?>">
                            <input type="hidden" id="unit_price" value="<?php echo floatval($book['price']); ?>">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Quantity *</label>
                                <input type="number" id="quantityInput" class="form-control form-control-lg rounded-3 fs-6" value="1" min="1" max="<?php echo $book['stock']; ?>" <?php echo ($book['stock'] <= 0) ? 'disabled' : ''; ?> required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">Mobile Money Phone Number *</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light text-muted fw-semibold fs-6 border-end-0">+233</span>
                                    <input type="tel" id="phone_number" class="form-control form-control-lg rounded-end-3 fs-6 fw-semibold" value="<?php echo htmlspecialchars($student['phone'] ?? '0544893582'); ?>" placeholder="054XXXXXXX" maxlength="10" required>
                                </div>
                                <small class="text-muted fs-8 mt-1 d-block"><i class="bi bi-info-circle me-1"></i> A USSD prompt will be triggered on this phone number to enter your PIN.</small>
                            </div>

                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 mb-4 border">
                                <span class="fw-bold text-dark fs-6">Total Amount Payable:</span>
                                <span class="fw-bold fs-3 text-wine" id="totalPriceDisplay">GH₵ <?php echo number_format($book['price'], 2); ?></span>
                            </div>

                            <button type="submit" id="payBtn" class="btn btn-wine btn-lg w-100 py-3 rounded-3 fw-bold shadow-sm" <?php echo ($book['stock'] <= 0) ? 'disabled' : ''; ?>>
                                <i class="bi bi-phone-vibrate me-1"></i> Pay with Mobile Money
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Paystack Inline JS Library -->
<script src="https://js.paystack.co/v1/inline.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Mobile Drawer Scripts
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

    // Checkout Logic
    const quantityInput = document.getElementById('quantityInput');
    const unitPrice = parseFloat(document.getElementById('unit_price').value);
    const totalPriceDisplay = document.getElementById('totalPriceDisplay');
    const checkoutForm = document.getElementById('paystackCheckoutForm');
    const payBtn = document.getElementById('payBtn');

    // Live Price Calculation
    quantityInput.addEventListener('input', function () {
        let qty = parseInt(this.value) || 1;
        if (qty < 1) qty = 1;
        let total = (qty * unitPrice).toFixed(2);
        totalPriceDisplay.textContent = 'GH₵ ' + total;
    });

    checkoutForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const phone = document.getElementById('phone_number').value.trim();
        const bookId = document.getElementById('book_id').value;
        const qty = parseInt(quantityInput.value) || 1;
        const totalAmountGhc = (qty * unitPrice).toFixed(2);
        const amountPesewas = Math.round(totalAmountGhc * 100);

        if (phone.length < 10) {
            alert('Please enter a valid 10-digit phone number.');
            return;
        }

        payBtn.disabled = true;
        payBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Connecting to Paystack...';

        // Clean reference starting with Student Index Number
        const rawIndex = "<?php echo preg_replace('/[^A-Za-z0-9\-]/', '', $index_number); ?>";
        const uniqueRef = rawIndex + '-' + Math.floor((Math.random() * 10000) + 1);

        // Initialize Paystack Modal with exact student details
        const handler = PaystackPop.setup({
            key: 'pk_test_9d325d24823d0100ce2a49cb9cc015ae51fc1dc7',
            email: "<?php echo htmlspecialchars($student_email); ?>",
            firstname: "<?php echo htmlspecialchars($student_name); ?>",
            amount: amountPesewas,
            currency: 'GHS',
            ref: uniqueRef,
            metadata: {
                custom_fields: [
                    { display_name: "Student Name", variable_name: "student_name", value: "<?php echo htmlspecialchars($student_name); ?>" },
                    { display_name: "Index Number", variable_name: "index_number", value: "<?php echo htmlspecialchars($index_number); ?>" },
                    { display_name: "Department & Level", variable_name: "department_level", value: "<?php echo htmlspecialchars($dept_level); ?>" },
                    { display_name: "Class", variable_name: "class", value: "<?php echo htmlspecialchars($student_class); ?>" },
                    { display_name: "Book ID", variable_name: "book_id", value: bookId },
                    { display_name: "Quantity", variable_name: "quantity", value: qty }
                ]
            },
            callback: function (response) {
                payBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying Payment...';
                
                const formData = new FormData();
                formData.append('reference', response.reference);
                formData.append('book_id', bookId);
                formData.append('quantity', qty);

                fetch('verify_paystack.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Payment successfully verified! Redirecting to orders...');
                        window.location.href = 'my_orders.php';
                    } else {
                        alert('Verification Failed: ' + data.message);
                        payBtn.disabled = false;
                        payBtn.innerHTML = '<i class="bi bi-phone-vibrate me-1"></i> Pay with Mobile Money';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred while verifying transaction with the server.');
                    payBtn.disabled = false;
                    payBtn.innerHTML = '<i class="bi bi-phone-vibrate me-1"></i> Pay with Mobile Money';
                });
            },
            onClose: function () {
                alert('Transaction was cancelled.');
                payBtn.disabled = false;
                payBtn.innerHTML = '<i class="bi bi-phone-vibrate me-1"></i> Pay with Mobile Money';
            }
        });

        handler.openIframe();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>