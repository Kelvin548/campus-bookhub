<?php
require_once 'includes/db.php';

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Truncate orders and payments tables
if ($conn->query("TRUNCATE TABLE payments") && $conn->query("TRUNCATE TABLE orders")) {
    echo "Successfully cleared all orders and payments!<br>";
} else {
    echo "Error clearing tables: " . $conn->error . "<br>";
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "You can now go back to the admin panel and delete your old books safely!";
?>