<?php
// includes/db.php

$host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost');
$user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
$pass = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: '');
$db   = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'campus_bookhub');
$port = (int)(getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: 3306));

$conn = @new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>