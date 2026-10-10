<?php
// includes/db.php

$host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost');
$user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
$pass = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: '');
$db   = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'campus_bookhub');
$port = (int)(getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: 3306));

$conn = null;
$max_retries = 3;
$retry_delay = 1; // seconds

for ($i = 0; $i < $max_retries; $i++) {
    // Suppress connection warnings with @ to handle wake-up delays gracefully
    $conn = @new mysqli($host, $user, $pass, $db, $port);
    
    if (!$conn->connect_error) {
        break; // Success! Exit the retry loop
    }
    
    // Wait a second before trying again to give MySQL time to wake up
    sleep($retry_delay);
}

if ($conn->connect_error) {
    die("Database Connection Error (Server is waking up, please refresh in a moment): " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>