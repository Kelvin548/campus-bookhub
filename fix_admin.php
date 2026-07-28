<?php
// fix_admin.php
require_once 'includes/db.php';

$username = 'admin_rep';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE admin SET password = ? WHERE username = ?");
$stmt->bind_param("ss", $hashed_password, $username);

if ($stmt->execute()) {
    echo "<h3>Success! Admin password has been updated.</h3>";
    echo "Username: <b>admin_rep</b><br>";
    echo "Password: <b>admin123</b><br><br>";
    echo "<a href='login.php'>Go to Login Page</a>";
} else {
    echo "Error updating record: " . $conn->error;
}
?>