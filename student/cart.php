<?php
// student/cart.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_student();

$student_id = $_SESSION['student_id'];
$success_msg = '';
$error_msg = '';

// Handle Cart Actions (Remove item or Checkout)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'remove') {
        $cart_id = (int)$_POST['cart_id'];
        $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $cart_id, $student_id);
        $stmt->execute();
        $success_msg = "Item removed from cart.";
    } elseif ($action === 'checkout') {
        // Fetch all items in cart
        $cart_query = "SELECT c.*, b.title, b.price FROM cart c JOIN books b ON c.book_id = b.book_id WHERE c.student_id = ?";
        $stmt_cart = $conn->prepare($cart_query);
        $stmt_cart->bind_param("i", $student_id);
        $stmt_cart->execute();
        $cart_items = $stmt_cart->get_result();

        if ($cart_items->num_rows > 0) {
            $conn->begin_transaction();
            try {
                // For simplicity with your existing orders table structure, we can create an order for each book 
                // or group them. Let's insert each cart item as an order linked to this transaction batch.
                $total_batch_amount = 0;
                $orders_created = [];

                while ($item = $cart_items->fetch_assoc()) {
                    $item_total = $item['price'] * $item['quantity'];
                    $total_batch_amount += $item_total;

                    // Insert into orders table
                    $stmt_ord = $conn->prepare("INSERT INTO orders (student_id, book_id, quantity, total_amount, payment_status, collection_status, order_date) VALUES (?, ?, ?, ?, 'Pending', 'Pending', NOW())");
                    $stmt_ord->bind_param("iiid", $student_id, $item['book_id'], $item['quantity'], $item_total);
                    $stmt_ord->execute();
                    $orders_created[] = $conn->insert_id;
                }

                // Clear the cart after creating orders
                $stmt_clear = $conn->prepare("DELETE FROM cart WHERE student_id = ?");
                $stmt_clear->bind_param("i", $student_id);
                $stmt_clear->execute();

                $conn->commit();

                // Redirect to payment processing for the latest order or payment gateway
                header("Location: my_orders.php");
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $error_msg = "Checkout failed: " . $e->getMessage();
            }
        } else {
            $error_msg = "Your cart is empty.";
        }
    }
}

// Fetch current cart items for display
$cart_query = "SELECT c.*, b.title, b.course_code, b.price, b.image FROM cart c JOIN books b ON c.book_id = b.book_id WHERE c.student_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$cart_result = $stmt->get_result();

$subtotal = 0;

$page_title = "Shopping Cart - Campus BookHub";
require_once '../includes/header.php';
?>

<style>
    :root {
        --wine-main: #6b1d2f;
        --wine-dark: #2e0811;
        --wine-gradient: linear-gradient(135deg, #6b1d2f 0%, #2e0811 100%);
        --sidebar-bg: #1f050b;
        --font-family: 'Plus Jakarta Sans', sans-serif;
    }
    body { font-family: var(--font-family); background-color: #f6f3f4; color: #2b2b2b; }
    #wrapper { display: flex; width: 100%; min-height: 100vh; }
    #sidebar-wrapper { width: 260px; min-width: 260px; background: var(--sidebar-bg) !important; }
    #page-content-wrapper { flex: 1; width: 100%; }
    .top-navbar { background-color: #ffffff; border-bottom: 1px solid #eae2e4; height: 70px; }
    .text-wine { color: var(--wine-main) !important; }
    .bg-wine-subtle { background-color: rgba(107, 29, 47, 0.08) !important; color: var(--wine-main) !important; }
    .btn-wine { background: var(--wine-gradient); color: #ffffff !important; border: none; }
</style>

<div id="wrapper">
    <aside id="sidebar-wrapper">
        <div class="px-3 py-3 text-center border-bottom border-secondary border-opacity-10">
            <a href="dashboard.php"><img src="../assets/images/logo.jpeg" alt="Logo" style="max-height: 48px; object-fit: contain; background:#fff; padding:4px; border-radius:10px;"></a>
        </div>
        <div class="p-3">
            <a href="dashboard.php" class="sidebar-link mb-1 text-white-50 text-decoration-none d-block p-2"><i class="bi bi-grid-1x2-fill me-2"></i> Dashboard</a>
            <a href="books.php" class="sidebar-link mb-1 text-white-50 text-decoration-none d-block p-2"><i class="bi bi-journal-bookmark-fill me-2"></i> Browse Manuals</a>
            <a href="cart.php" class="sidebar-link active mb-1 text-white text-decoration-none d-block p-2 bg-dark rounded"><i class="bi bi-cart3 me-2"></i> My Cart</a>
            <a href="my_orders.php" class="sidebar-link mb-1 text-white-50 text-decoration-none d-block p-2"><i class="bi bi-bag-check-fill me-2"></i> My Orders</a>
            <a href="profile.php" class="sidebar-link mb-1 text-white-50 text-decoration-none d-block p-2"><i class="bi bi-person-gear me-2"></i> Profile</a>
            <hr class="border-secondary opacity-25">
            <a href="../logout.php" class="sidebar-link text-danger text-decoration-none d-block p-2"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
        </div>
    </aside>

    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg top-navbar px-4">
            <span class="navbar-brand fw-semibold text-muted fs-6">Shopping Cart</span>
        </nav>

        <main class="p-4">
            <h4 class="fw-bold text-dark mb-4">Your Course Manual Cart</h4>

            <?php if ($success_msg): ?>
                <div class="alert alert-success rounded-3"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger rounded-3"><?php echo htmlspecialchars($error_msg); ?></div>
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
                                    <?php if ($cart_result && $cart_result->num_rows > 0): ?>
                                        <?php while ($item = $cart_result->fetch_assoc()): 
                                            $item_total = $item['price'] * $item['quantity'];
                                            $subtotal += $item_total;
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <?php if (!empty($item['image'])): ?>
                                                            <img src="../<?php echo htmlspecialchars($item['image']); ?>" width="45" height="55" class="rounded object-fit-contain bg-light p-1">
                                                        <?php else: ?>
                                                            <div class="bg-light rounded p-2 text-center" style="width: 45px; height: 55px;"><i class="bi bi-book text-wine"></i></div>
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
                                        <?php endwhile; ?>
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
                        <div class="d-flex justify-content-between mb-4 fw-bold text-dark fs-5">
                            <span>Total</span>
                            <span class="text-wine">GH₵ <?php echo number_format($subtotal, 2); ?></span>
                        </div>

                        <?php if ($subtotal > 0): ?>
                            <form action="cart.php" method="POST">
                                <input type="hidden" name="action" value="checkout">
                                <button type="submit" class="btn btn-wine btn-lg w-100 rounded-3 fw-semibold fs-6">
                                    Proceed to Checkout <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </form>
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

<?php require_once '../includes/footer.php'; ?>