<?php
// includes/db.php

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'campus_bookhub';
$port = (int)(getenv('DB_PORT') ?: 3306);

$conn = @new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>