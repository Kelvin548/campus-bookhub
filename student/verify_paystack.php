<?php
// student/verify_paystack.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_student();

header('Content-Type: application/json');

// Robustly check all environment variable sources on Railway
$paystack_secret = getenv('PAYSTACK_SECRET_KEY');
if (!$paystack_secret && isset($_ENV['PAYSTACK_SECRET_KEY'])) {
    $paystack_secret = $_ENV['PAYSTACK_SECRET_KEY'];
}
if (!$paystack_secret && isset($_SERVER['PAYSTACK_SECRET_KEY'])) {
    $paystack_secret = $_SERVER['PAYSTACK_SECRET_KEY'];
}

// Fallback if environment variable isn't read
if (empty($paystack_secret)) {
    $paystack_secret = 'sk_live_f9a131faf445d9d31c9ab'; // Your live secret key fallback
}

define('PAYSTACK_SECRET_KEY', trim($paystack_secret));

$reference = trim($_POST['reference'] ?? '');
$is_cart_checkout = isset($_POST['is_cart_checkout']) && $_POST['is_cart_checkout'] === '1';
$student_id = $_SESSION['user_id'];

if (empty($reference)) {
    echo json_encode(['success' => false, 'message' => 'Missing transaction reference.']);
    exit();
}

// If single book checkout, validate parameters
$book_id = 0;
$quantity = 1;
if (!$is_cart_checkout) {
    $book_id  = intval($_POST['book_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($book_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing transaction parameters.']);
        exit();
    }
}

// 1. Verify Payment with Paystack API
$url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
    "Cache-Control: no-cache"
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . $err]);
    exit();
}

$result = json_decode($response, true);

if (!$result || !isset($result['status']) || $result['status'] === false) {
    $api_message = $result['message'] ?? 'Unable to verify transaction with Paystack.';
    echo json_encode(['success' => false, 'message' => $api_message]);
    exit();
}

// 2. Save Order(s) to Database and Update Stock
if (isset($result['data']['status']) && $result['data']['status'] === 'success') {
    $conn->begin_transaction();

    try {
        if ($is_cart_checkout) {
            // Fetch all items currently in the student's cart
            $cart_stmt = $conn->prepare("SELECT c.*, b.price FROM cart c JOIN books b ON c.book_id = b.book_id WHERE c.student_id = ?");
            $cart_stmt->bind_param("i", $student_id);
            $cart_stmt->execute();
            $cart_items = $cart_stmt->get_result();

            if ($cart_items->num_rows === 0) {
                throw new Exception("Your cart is empty.");
            }

            while ($item = $cart_items->fetch_assoc()) {
                $item_total = $item['price'] * $item['quantity'];
                $item_book_id = $item['book_id'];
                $item_qty = $item['quantity'];

                // Insert into orders table
                $ord_stmt = $conn->prepare("INSERT INTO orders (student_id, book_id, quantity, total_amount, payment_reference, payment_status, collection_status, order_date) VALUES (?, ?, ?, ?, ?, 'Paid', 'Pending', NOW())");
                $ord_stmt->bind_param("iiids", $student_id, $item_book_id, $item_qty, $item_total, $reference);
                $ord_stmt->execute();
                $ord_stmt->close();

                // Decrement stock for each book in cart
                $update_stock = $conn->prepare("UPDATE books SET stock = GREATEST(0, stock - ?) WHERE book_id = ?");
                $update_stock->bind_param("ii", $item_qty, $item_book_id);
                $update_stock->execute();
                $update_stock->close();
            }
            $cart_stmt->close();

            // Clear the cart after successful checkout
            $clear_stmt = $conn->prepare("DELETE FROM cart WHERE student_id = ?");
            $clear_stmt->bind_param("i", $student_id);
            $clear_stmt->execute();
            $clear_stmt->close();

        } else {
            // Single book checkout flow
            $amount_paid_ghc = $result['data']['amount'] / 100; // Convert pesewas to GHS

            $stmt = $conn->prepare("INSERT INTO orders (student_id, book_id, quantity, total_amount, payment_reference, payment_status, collection_status, order_date) VALUES (?, ?, ?, ?, ?, 'Paid', 'Pending', NOW())");
            $stmt->bind_param("iiids", $student_id, $book_id, $quantity, $amount_paid_ghc, $reference);
            $stmt->execute();
            $stmt->close();

            // Decrement book stock count
            $update_stock = $conn->prepare("UPDATE books SET stock = GREATEST(0, stock - ?) WHERE book_id = ?");
            $update_stock->bind_param("ii", $quantity, $book_id);
            $update_stock->execute();
            $update_stock->close();
        }

        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Payment verified successfully!']);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database update error: ' . $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Payment was not successful according to Paystack.']);
    exit();
}