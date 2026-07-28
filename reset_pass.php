<?php
// reset_pass.php
require_once 'includes/db.php';

$new_password = 'admin123';
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE admin SET password = ? WHERE username = 'admin_rep'");
$stmt->bind_param("s", $hashed);

if ($stmt->execute()) {
    echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
    echo "<h2 style='color:#198754;'>Success! Password updated for admin_rep</h2>";
    echo "<p>New Password: <b>admin123</b></p>";
    echo "<a href='login.php' style='display:inline-block; padding:10px 20px; background:#0d6efd; color:#fff; text-decoration:none; border-radius:5px;'>Go to Login Page</a>";
    echo "</div>";
} else {
    echo "Database Error: " . $conn->error;
}
?>