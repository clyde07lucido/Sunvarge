<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP</title>
</head>
<body>
    <form action="verify.php" method="POST">
  <input type="text" name="otp" placeholder="Enter OTP" required>
  <button type="submit">Verify</button>
</form>
</body>
</html>

<?php
session_start();
include "db.php";

$email = $_SESSION['email'];
$otp = $_POST['otp'];

$sql = "SELECT * FROM otp_codes 
        WHERE email = :email AND otp = :otp 
        AND expires_at > NOW()";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':email' => $email,
    ':otp' => $otp
]);

if ($stmt->rowCount() > 0) {
    $_SESSION['authenticated'] = true;
    header("Location: dashboard.php");
    exit();
} else {
    echo "Invalid or expired OTP";
}
?>