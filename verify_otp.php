<?php
session_start();

$error = "";

if (!isset($_SESSION['otp'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $userOTP = trim($_POST['otp']);

    // OTP EXPIRED
    if (time() > $_SESSION['otp_expiry']) {

        session_unset();
        session_destroy();

        header("Location: login.php?message=OTP expired");
        exit();
    }

    // OTP MATCH
    if ($userOTP == $_SESSION['otp']) {

        session_regenerate_id(true);

        $_SESSION['authenticated'] = $_SESSION['otp_user'];

        unset($_SESSION['otp']);
        unset($_SESSION['otp_expiry']);
        unset($_SESSION['otp_user']);

        header("Location: dashboard.php");
        exit();

    } else {

        $error = "Invalid OTP code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>Sunvarge - OTP Verification</title>

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

<link rel="stylesheet" href="styles/verify_otp.css" />

</head>
<body>

<div class="bg-grid"></div>
<div class="bg-glow"></div>

<div class="site-logo">
    <img src="img/logo.png" class="logo-icon" alt="Sunvarge">
</div>

<div class="card">

    <h2 class="card-title">OTP Verification</h2>

    <p class="subtitle">
        Enter the 6-digit verification code sent to your email address.
    </p>

    <?php if (!empty($error)) { ?>
        <div class="message error">
            <?php echo $error; ?>
        </div>
    <?php } ?>

    <form method="POST">

        <div class="form-group">

            <label>Verification Code</label>

            <div class="otp-container">

                <input type="text" maxlength="1" class="otp-box" required>
                <input type="text" maxlength="1" class="otp-box" required>
                <input type="text" maxlength="1" class="otp-box" required>
                <input type="text" maxlength="1" class="otp-box" required>
                <input type="text" maxlength="1" class="otp-box" required>
                <input type="text" maxlength="1" class="otp-box" required>

            </div>

            <input type="hidden" name="otp" id="finalOTP">

        </div>

        <button type="submit" class="btn-primary">
            VERIFY OTP
        </button>

    </form>

    <p class="note">
        This verification code will expire in 5 minutes for security purposes.
    </p>

</div>

<script>

const otpBoxes = document.querySelectorAll(".otp-box");
const finalOTP = document.getElementById("finalOTP");

otpBoxes.forEach((box, index) => {

    box.addEventListener("input", (e) => {

        e.target.value = e.target.value.replace(/[^0-9]/g, '');

        if (e.target.value && index < otpBoxes.length - 1) {
            otpBoxes[index + 1].focus();
        }

        updateOTP();
    });

    box.addEventListener("keydown", (e) => {

        if (e.key === "Backspace" && !box.value && index > 0) {
            otpBoxes[index - 1].focus();
        }

    });

});

function updateOTP(){

    let otp = "";

    otpBoxes.forEach(box => {
        otp += box.value;
    });

    finalOTP.value = otp;
}

</script>

</body>
</html>
