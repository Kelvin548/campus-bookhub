<?php
// includes/db.php

// Read from Railway environment variables, with local XAMPP defaults as fallbacks
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'campus_bookhub';
$port = getenv('DB_PORT') ?: 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Pass the port as the fifth parameter to ensure connection on custom ports
    $conn = new mysqli($host, $user, $pass, $db, (int)$port);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>