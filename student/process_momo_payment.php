<?php
// student/process_momo_payment.php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Enable error catching
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in again.']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit();
    }

    $student_id     = $_SESSION['user_id'];
    $book_id        = intval($_POST['book_id'] ?? 0);
    $quantity       = intval($_POST['quantity'] ?? 1);
    $phone_number   = trim($_POST['phone_number'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'MTN Mobile Money');

    if ($book_id <= 0 || $quantity <= 0 || empty($phone_number)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid quantity and phone number.']);
        exit();
    }

    // Fetch Book Info
    $stmt = $conn->prepare("SELECT price, stock FROM books WHERE book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$book) {
        echo json_encode(['success' => false, 'message' => 'Selected book was not found.']);
        exit();
    }

    if ($book['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock available.']);
        exit();
    }

    $total_amount   = $book['price'] * $quantity;
    $transaction_id = 'MOMO-' . date('Ymd') . '-' . rand(100000, 999999);

    // Begin DB Transaction
    $conn->begin_transaction();

    // 1. Insert Order with payment_reference included
    $order_stmt = $conn->prepare("
        INSERT INTO orders (student_id, book_id, quantity, total_amount, payment_reference, payment_status, collection_status, order_date) 
        VALUES (?, ?, ?, ?, ?, 'Verified', 'Pending', NOW())
    ");
    $order_stmt->bind_param("iiids", $student_id, $book_id, $quantity, $total_amount, $transaction_id);
    $order_stmt->execute();
    $new_order_id = $conn->insert_id;
    $order_stmt->close();

    // 2. Check and insert into payments table if it exists in your database
    $payments_check = $conn->query("SHOW TABLES LIKE 'payments'");
    if ($payments_check && $payments_check->num_rows > 0) {
        $proof_placeholder = 'IN_APP_MOMO_VERIFIED';
        $pay_stmt = $conn->prepare("
            INSERT INTO payments (order_id, transaction_id, payment_method, amount, proof_image, status) 
            VALUES (?, ?, ?, ?, ?, 'Approved')
        ");
        $pay_stmt->bind_param("issds", $new_order_id, $transaction_id, $payment_method, $total_amount, $proof_placeholder);
        $pay_stmt->execute();
        $pay_stmt->close();
    }

    // 3. Update Book Stock
    $stock_stmt = $conn->prepare("UPDATE books SET stock = stock - ? WHERE book_id = ?");
    $stock_stmt->bind_param("ii", $quantity, $book_id);
    $stock_stmt->execute();
    $stock_stmt->close();

    $conn->commit();

    echo json_encode([
        'success'        => true,
        'message'        => 'Payment successfully authorized!',
        'order_id'       => $new_order_id,
        'transaction_id' => $transaction_id
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        @$conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}