<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once __DIR__ . '/env.php';

function sendOTP($email, $otp)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = $_ENV['SMTP_HOST'] ?? '';

        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USERNAME'] ?? '';

        $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = $_ENV['SMTP_PORT'] ?? 587;

        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'] ?? '', $_ENV['SMTP_OTP_FROM_NAME'] ?? 'SunVarge Security');

        $mail->addAddress($email);

        $mail->isHTML(true);

        $mail->Subject = 'SunVarge OTP Verification';

        $mail->Body = "
            <div style='font-family:Arial;padding:20px;'>

                <h2>SunVarge Security OTP</h2>

                <p>Your verification code is:</p>

                <h1 style='letter-spacing:5px;color:#1a2556;'>
                    $otp
                </h1>

                <p>
                    This OTP will expire in 5 minutes.
                </p>

            </div>
        ";

        $mail->send();

        return true;

    } catch (Exception $e) {

        return false;

    }
}
?>
