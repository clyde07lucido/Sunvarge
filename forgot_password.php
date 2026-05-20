<?php
session_start();
include "db.php";

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$status = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);

    // default safe message
    $status = "If the email exists, a reset link has been sent.";

    // check user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {

        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $stmt = $conn->prepare("
            UPDATE users 
            SET reset_token = ?, token_expiry = ? 
            WHERE email = ?
        ");
        $stmt->execute([$token, $expiry, $email]);

        $resetLink = ($_ENV['APP_URL'] ?? 'http://localhost/Information_Management/FINAL_PROJECT') . "/reset_password.php?token=" . $token;

        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'] ?? '';
            $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
            $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? 'tls';
            $mail->Port = $_ENV['SMTP_PORT'] ?? 587;

            $mail->setFrom($_ENV['SMTP_FROM_EMAIL'] ?? '', $_ENV['SMTP_RESET_FROM_NAME'] ?? 'Sunvarge');
            $mail->addAddress($email);

            $mail->Subject = 'Password Reset';

            $mail->isHTML(true);

            $mail->Body = "
            <div style='font-family:Arial,sans-serif;padding:20px;color:#333;'>
                <h2 style='color:#1a2556;'>Password Reset Request</h2>

                <p>You requested to reset your password.</p>

                <p>
                    Click the button below to reset your password:
                </p>

                <a href='$resetLink'
                style='
                    display:inline-block;
                    padding:12px 20px;
                    background:#1a2556;
                    color:white;
                    text-decoration:none;
                    border-radius:8px;
                    font-weight:bold;
                '>
                    Reset Password
                </a>

                <p style='margin-top:20px;font-size:13px;color:#777;'>
                    This link will expire in 1 hour.
                </p>
            </div>
            ";

            $mail->send();

        } catch (Exception $e) {

            $error = "Unable to send reset email.";
        }
    }

    // prevent form resubmission
    header("Location: forgot_password.php?status=sent");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>Sunvarge - Forgot Password</title>

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

<link rel="stylesheet" href="styles/forgot_password.css" />

</head>
<body>

<div class="bg-grid"></div>
<div class="bg-glow"></div>

<div class="site-logo">
    <img src="img/logo.png" class="logo-icon" alt="Sunvarge">
</div>

<div class="card">

    <h2 class="card-title">Forgot Password</h2>

    <p class="subtitle">
        Enter your email address and we'll send you a password reset link.
    </p>

    <?php if (isset($_GET['status'])) { ?>
        <div class="message success">
            If the email exists, a reset link has been sent.
        </div>
    <?php } ?>

    <?php if (!empty($error)) { ?>
        <div class="message error">
            <?php echo $error; ?>
        </div>
    <?php } ?>

    <form method="POST">

        <div class="form-group">

            <label>Email</label>

            <div class="input-wrap">

                <input 
                    type="email" 
                    name="email" 
                    class="input-field"
                    placeholder="Enter your Email Address"
                    required
                >

            </div>

        </div>

        <button type="submit" class="btn-primary">
            SEND RESET LINK
        </button>

    </form>

    <p class="switch-link">
        Remember your password?
        <a href="login.php">
            Back to Login
        </a>
    </p>

</div>

</body>
</html>
