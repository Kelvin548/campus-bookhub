<?php
// student/cart.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_student();

$student_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle Cart Actions (Remove item only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'remove') {
        $cart_id = (int)$_POST['cart_id'];
        $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $cart_id, $student_id);
        $stmt->execute();
        $success_msg = "Item removed from cart.";
    }
}

// Fetch Logged-in Student Details for Paystack Checkout
$user_stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$user_stmt->bind_param("i", $student_id);
$user_stmt->execute();
$student = $user_stmt->get_result()->fetch_assoc() ?? [];
$user_stmt->close();

$student_name  = $student['fullname'] ?? $student['full_name'] ?? ($_SESSION['name'] ?? 'Student');
$student_email = $student['email'] ?? ($_SESSION['email'] ?? ('student' . $student_id . '@campusbookhub.com'));
$index_number  = $student['index_number'] ?? ('INDEX-' . $student_id);
$student_phone = $student['phone'] ?? '0544893582';

// Fetch current cart items for display
$cart_query = "SELECT c.*, b.title, b.course_code, b.price, b.image FROM cart c JOIN books b ON c.book_id = b.book_id WHERE c.student_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$cart_result = $stmt->get_result();

$subtotal = 0;
$cart_items_array = [];

if ($cart_result && $cart_result->num_rows > 0) {
    while ($item = $cart_result->fetch_assoc()) {
        $cart_items_array[] = $item;
        $subtotal += ($item['price'] * $item['quantity']);
    }
}

$page_title = "Shopping Cart - Campus BookHub";
require_once '../includes/header.php';
?>

<style>
    :root {
        --wine-main: #6b1d2f;
        --wine-dark: #2e0811;
        --wine-gradient: linear-gradient(135deg, #6b1d2f 0%, #2e0811 100%);
        --wine-gradient-card: linear-gradient(145deg, #7c2237 0%, #420b17 100%);
        --sidebar-bg: #1f050b;
        --font-family: 'Plus Jakarta Sans', sans-serif;
    }

    body { font-family: var(--font-family); background-color: #f6f3f4; color: #2b2b2b; overflow-x: hidden; }
    
    #wrapper { display: flex; width: 100%; min-height: 100vh; position: relative; }
    
    #sidebar-wrapper {
        width: 260px; min-width: 260px; background: var(--sidebar-bg) !important;
        min-height: 100vh; border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
        transition: all 0.3s ease-in-out; z-index: 1050;
    }
    .sidebar-brand-box { background: rgba(255, 255, 255, 0.03); border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
    .sidebar-logo-img { max-height: 48px; object-fit: contain; background: #ffffff; padding: 4px 8px; border-radius: 10px; }
    .sidebar-link {
        color: rgba(255, 255, 255, 0.7) !important; padding: 0.75rem 1rem; font-weight: 500; font-size: 0.88rem;
        border-radius: 10px !important; display: flex; align-items: center; text-decoration: none; transition: all 0.25s ease;
    }
    .sidebar-link:hover { background: rgba(255, 255, 255, 0.08) !important; color: #ffffff !important; }
    .sidebar-link.active { background: var(--wine-gradient-card) !important; color: #ffffff !important; font-weight: 600; }

    #page-content-wrapper { flex: 1; width: 100%; min-width: 0; }
    .top-navbar { background-color: #ffffff; border-bottom: 1px solid #eae2e4; height: 70px; }
    .text-wine { color: var(--wine-main) !important; }
    .bg-wine-subtle { background-color: rgba(107, 29, 47, 0.08) !important; color: var(--wine-main) !important; }

    /* MTN Yellow Checkout Button & Focus Styling */
    .btn-mtn {
        background-color: #FFCC00 !important;
        color: #000000 !important;
        font-weight: 700 !important;
        border: none !important;
        transition: all 0.3s ease !important;
    }

    .btn-mtn:hover {
        background-color: #E6B800 !important;
        color: #000000 !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(255, 204, 0, 0.4) !important;
    }

    .mtn-border-focus:focus-within {
        border-color: #FFCC00 !important;
        box-shadow: 0 0 0 3px rgba(255, 204, 0, 0.25) !important;
    }

    /* Mobile Drawer Overlay */
    .sidebar-overlay {
        display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(2px); z-index: 1040;
    }

    @media (max-width: 991.98px) {
        #sidebar-wrapper {
            position: fixed; top: 0; bottom: 0; left: 0;
            margin-left: -260px;
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.3);
        }
        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }
        #wrapper.toggled .sidebar-overlay {
            display: block;
        }
    }
</style>

<div id="wrapper">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside id="sidebar-wrapper">
        <div class="sidebar-brand-box px-3 py-3 text-center">
            <a href="dashboard.php" class="d-inline-block">
                <img src="../assets/images/logo.jpeg" alt="Logo" class="sidebar-logo-img img-fluid">
            </a>
        </div>
        <div class="p-3">
            <small class="text-uppercase fw-bold px-2 mb-2 d-block fs-8" style="color: rgba(255,255,255,0.4) !important;">Student Portal</small>
            <a href="dashboard.php" class="sidebar-link mb-1"><i class="bi bi-grid-1x2-fill me-2.5"></i> Dashboard</a>
            <a href="books.php" class="sidebar-link mb-1"><i class="bi bi-journal-bookmark-fill me-2.5"></i> Browse Manuals</a>
            <a href="cart.php" class="sidebar-link active mb-1"><i class="bi bi-cart3 me-2.5"></i> My Cart</a>
            <a href="my_orders.php" class="sidebar-link mb-1"><i class="bi bi-bag-check-fill me-2.5"></i> My Orders</a>
            <a href="profile.php" class="sidebar-link mb-1"><i class="bi bi-person-gear me-2.5"></i> Profile Settings</a>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 1.25rem 0;">
            <a href="../logout.php" class="sidebar-link text-danger mb-1" style="color: #ff6b81 !important;"><i class="bi bi-box-arrow-right me-2.5"></i> Logout</a>
        </div>
    </aside>

    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg top-navbar px-4">
            <div class="container-fluid p-0 d-flex align-items-center">
                <button class="btn btn-light border me-3 d-lg-none rounded-3 p-1 px-2" id="sidebarToggle" type="button"><i class="bi bi-list fs-4 text-wine"></i></button>
                <span class="navbar-brand fw-semibold text-muted fs-6 mb-0">Shopping Cart</span>
            </div>
        </nav>

        <main class="p-4">
            <h4 class="fw-bold text-dark mb-4">Your Course Manual Cart</h4>

            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4"><?php echo htmlspecialchars($success_msg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4"><?php echo htmlspecialchars($error_msg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="text-muted fs-8 text-uppercase">
                                    <tr>
                                        <th>Manual</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($cart_items_array)): ?>
                                        <?php foreach ($cart_items_array as $item): 
                                            $item_total = $item['price'] * $item['quantity'];
                                            $img_path = $item['image'] ?? '';
                                            $img_path = ltrim($img_path, './');
                                            if (strpos($img_path, 'uploads/') !== 0 && !empty($img_path)) {
                                                $img_path = 'uploads/' . ltrim($img_path, '/');
                                            }
                                            $full_img_url = '../' . $img_path;
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <?php if (!empty($item['image']) && file_exists('../' . ltrim($item['image'], './'))): ?>
                                                            <img src="<?php echo htmlspecialchars($full_img_url); ?>" width="45" height="55" class="rounded object-fit-contain bg-light p-1">
                                                        <?php else: ?>
                                                            <div class="bg-wine-subtle rounded p-2 text-center d-flex align-items-center justify-content-center" style="width: 45px; height: 55px;"><i class="bi bi-book text-wine"></i></div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <h6 class="fw-bold text-dark mb-0 fs-7"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                            <small class="text-muted fs-8"><?php echo htmlspecialchars($item['course_code']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="fw-semibold">GH₵ <?php echo number_format($item['price'], 2); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td class="fw-bold text-wine">GH₵ <?php echo number_format($item_total, 2); ?></td>
                                                <td class="text-end">
                                                    <form action="cart.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="remove">
                                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-1" style="width: 28px; height: 28px;" title="Remove">
                                                            <i class="bi bi-trash3 fs-8"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-cart-x display-4 text-muted d-block mb-2"></i>
                                                Your cart is currently empty.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h5 class="fw-bold text-dark mb-3">Order Summary</h5>
                        <div class="d-flex justify-content-between mb-2 text-muted fs-7">
                            <span>Subtotal</span>
                            <span>GH₵ <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <hr class="my-3">

                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark fs-7">MoMo Phone Number *</label>
                            <div class="input-group mtn-border-focus">
                                <span class="input-group-text bg-light text-muted fw-semibold fs-7 border-end-0">+233</span>
                                <input type="tel" id="phone_number" class="form-control rounded-end-3 fs-7 fw-semibold" value="<?php echo htmlspecialchars($student_phone); ?>" placeholder="054XXXXXXX" maxlength="10" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mb-4 fw-bold text-dark fs-5">
                            <span>Total</span>
                            <span class="text-wine">GH₵ <?php echo number_format($subtotal, 2); ?></span>
                        </div>

                        <?php if ($subtotal > 0): ?>
                            <button type="button" id="payCartBtn" class="btn btn-mtn btn-lg w-100 rounded-3 fw-semibold fs-6 py-2.5 shadow-sm">
                                <i class="bi bi-phone-vibrate me-1"></i> Proceed to Checkout
                            </button>
                        <?php else: ?>
                            <a href="books.php" class="btn btn-outline-secondary btn-lg w-100 rounded-3 fw-semibold fs-6">
                                Browse Manuals
                            </a>
                        <?php endif; ?>
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

    const payCartBtn = document.getElementById('payCartBtn');
    if (payCartBtn) {
        payCartBtn.addEventListener('click', function (e) {
            e.preventDefault();

            const phone = document.getElementById('phone_number').value.trim();
            const totalAmountGhc = <?php echo floatval($subtotal); ?>;
            const amountPesewas = Math.round(totalAmountGhc * 100);

            if (phone.length < 10) {
                alert('Please enter a valid 10-digit Mobile Money phone number.');
                return;
            }

            payCartBtn.disabled = true;
            payCartBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Connecting to Paystack...';

            const rawIndex = "<?php echo preg_replace('/[^A-Za-z0-9\-]/', '', $index_number); ?>";
            const uniqueRef = rawIndex + '-' + Math.floor((Math.random() * 10000) + 1);

            const handler = PaystackPop.setup({
                key: 'pk_live_7959805de6cdaf28fdceb86c0d2b5aaa5f3fcbf0',
                email: "<?php echo htmlspecialchars($student_email); ?>",
                firstname: "<?php echo htmlspecialchars($student_name); ?>",
                amount: amountPesewas,
                currency: 'GHS',
                ref: uniqueRef,
                metadata: {
                    custom_fields: [
                        { display_name: "Student Name", variable_name: "student_name", value: "<?php echo htmlspecialchars($student_name); ?>" },
                        { display_name: "Index Number", variable_name: "index_number", value: "<?php echo htmlspecialchars($index_number); ?>" },
                        { display_name: "Order Type", variable_name: "order_type", value: "Multi-item Cart Checkout" }
                    ]
                },
                callback: function (response) {
                    payCartBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying Payment...';
                    
                    const formData = new FormData();
                    formData.append('reference', response.reference);
                    formData.append('is_cart_checkout', '1');

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
                            payCartBtn.disabled = false;
                            payCartBtn.innerHTML = '<i class="bi bi-phone-vibrate me-1"></i> Proceed to Checkout';
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('An error occurred while verifying transaction with the server.');
                        payCartBtn.disabled = false;
                        payCartBtn.innerHTML = '<i class="bi bi-phone-vibrate me-1"></i> Proceed to Checkout';
                    });
                },
                onClose: function () {
                    alert('Transaction was cancelled.');
                    payCartBtn.disabled = false;
                    payCartBtn.innerHTML = '<i class="bi bi-phone-vibrate me-1"></i> Proceed to Checkout';
                }
            });

            handler.openIframe();
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>