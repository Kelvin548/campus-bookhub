<?php
// Include your database connection file
require_once 'config/db.php'; // Change path if your db file is in a different folder

$index_number = '5240101387';
$course_code  = 'ICT353';
$amount       = 61.00;
$reference    = '5240101387-969';
$created_at   = '2026-10-21 09:10:00';

$sql = "INSERT INTO orders (student_id, book_id, amount, payment_status, reference, created_at) 
        VALUES (
            (SELECT student_id FROM students WHERE index_number = '$index_number' LIMIT 1),
            (SELECT book_id FROM books WHERE course_code = '$course_code' LIMIT 1), 
            $amount, 
            'Paid', 
            '$reference', 
            '$created_at'
        )";

if ($conn->query($sql) === TRUE) {
    echo "<h1>SUCCESS: Order inserted successfully for student {$index_number}!</h1>";
} else {
    echo "<h1>ERROR: " . $conn->error . "</h1>";
}
?>