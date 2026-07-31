<?php
// includes/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Use absolute root path to avoid relative directory issues
$root_path = dirname(__DIR__);

require_once $root_path . '/src/Exception.php';
require_once $root_path . '/src/PHPMailer.php';
require_once $root_path . '/src/SMTP.php';

function send_otp_email($recipient_email, $otp_code) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kelvinhonorajunior@gmail.com';
        $mail->Password   = 'wrfx vkcm shde uaym';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('kelvinhonorajunior@gmail.com', 'Campus BookHub');
        $mail->addAddress($recipient_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Verification Code - Campus BookHub';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                <h2 style='color: #6b1d2f;'>Campus BookHub Password Recovery</h2>
                <p>Hello,</p>
                <p>You requested a password reset for your account. Use the 6-digit verification code below:</p>
                <div style='background: #f8f6f7; padding: 15px; font-size: 24px; font-weight: bold; letter-spacing: 5px; color: #6b1d2f; text-align: center; border-radius: 8px; margin: 20px 0;'>
                    {$otp_code}
                </div>
                <p>This code expires in <strong>10 minutes</strong>. If you didn't request this, please ignore this email.</p>
            </div>
        ";
        $mail->AltBody = "Your Campus BookHub password reset verification code is: {$otp_code}. It expires in 10 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return false;
    }
}