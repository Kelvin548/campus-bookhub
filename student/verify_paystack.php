<?php
// student/verify_paystack.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_student();

header('Content-Type: application/json');

// Make sure your working secret key (sk_test_0c...) is pasted here:
define('PAYSTACK_SECRET_KEY', trim('sk_test_0c6b27306dc36215874694b5d3df9d58f36d050f')); 

$reference = $_POST['reference'] ?? '';
$book_id   = intval($_POST['book_id'] ?? 0);
$quantity  = intval($_POST['quantity'] ?? 1);
$student_id = $_SESSION['user_id'];

if (empty($reference) || $book_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing transaction parameters.']);
    exit();
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

// 2. Save Order to Database and Update Stock
if (isset($result['data']['status']) && $result['data']['status'] === 'success') {
    $amount_paid_ghc = $result['data']['amount'] / 100; // Convert pesewas to GHS

    $conn->begin_transaction();

    try {
        // Aligned with Campus BookHub database columns:
        // (student_id, book_id, quantity, total_amount, payment_reference, payment_status, collection_status, order_date)
        $stmt = $conn->prepare("INSERT INTO orders (student_id, book_id, quantity, total_amount, payment_reference, payment_status, collection_status, order_date) VALUES (?, ?, ?, ?, ?, 'Paid', 'Pending', NOW())");
        $stmt->bind_param("iiids", $student_id, $book_id, $quantity, $amount_paid_ghc, $reference);
        $stmt->execute();
        $stmt->close();

        // Decrement book stock count
        $update_stock = $conn->prepare("UPDATE books SET stock = GREATEST(0, stock - ?) WHERE book_id = ?");
        $update_stock->bind_param("ii", $quantity, $book_id);
        $update_stock->execute();
        $update_stock->close();

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