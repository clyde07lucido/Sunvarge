<?php
session_start();
include "db.php";

$error = "";

// 🔥 GOOGLE CALLBACK ERROR DISPLAY
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 🔥 CAPTCHA VERIFY
    $captcha = $_POST['g-recaptcha-response'] ?? '';

    if (empty($captcha)) {

    $error = "No internet connection or CAPTCHA not completed.";

    } else {

        $secretKey = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';

        $verify = @file_get_contents(
            "https://www.google.com/recaptcha/api/siteverify?secret="
            . $secretKey .
            "&response=" . $captcha
        );

        // 🔥 INTERNET / GOOGLE ERROR
        if ($verify === false) {

            $error = "Unable to verify CAPTCHA. Please check your internet connection.";

        } else {

            $response = json_decode($verify);

            if (!$response->success) {
                $error = "CAPTCHA verification failed.";
            }
        }
    }

    // 🔥 CUSTOM CAPTCHA VERIFY
    if (empty($error)) {

        if ($_POST['captcha'] != $_SESSION['captcha']) {
            $error = "Invalid CAPTCHA text.";
        }
    }

    // 🔥 ONLY RUN LOGIN IF CAPTCHA PASSED
    if (empty($error)) {

        $email = $_POST['email'];
    $password = $_POST['password'];

    // 🔥 GET USER + SECURITY DATA
    $stmt = $conn->prepare("
        SELECT u.*, ls.login_attempts, ls.last_attempt
        FROM users u
        LEFT JOIN login_security ls ON u.id = ls.user_id
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "Invalid credentials.";
    } else {

        // 🔥 ENSURE login_security ROW EXISTS
        $stmt = $conn->prepare("
            INSERT INTO login_security (user_id, login_attempts, last_attempt)
            VALUES (?, 0, NULL)
            ON CONFLICT (user_id) DO NOTHING
        ");
        $stmt->execute([$user['id']]);

        $attempts = $user['login_attempts'] ?? 0;
        $last_attempt = $user['last_attempt'];

        // 🔥 LOCK CHECK (5 mins)
        if ($attempts >= 3 && !empty($last_attempt)) {

            $stmt = $conn->prepare("
                SELECT EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - ?::timestamp)) AS diff
            ");
            $stmt->execute([$last_attempt]);
            $timeData = $stmt->fetch();

            $diff = $timeData['diff'] ?? 0;

            if ($diff < 300) {
                $error = "Too many attempts. Try again in " . ceil((300 - $diff) / 60) . " minute(s).";
            }
        }

        // 🔥 LOGIN PROCESS
        if (empty($error)) {

            if (password_verify($password, $user['password'])) {

                // reset attempts
                $stmt = $conn->prepare("
                    UPDATE login_security 
                    SET login_attempts = 0, last_attempt = NULL 
                    WHERE user_id = ?
                ");
                $stmt->execute([$user['id']]);

                // GENERATE OTP
                $otp = rand(100000, 999999);

                // SAVE OTP SESSION
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_expiry'] = time() + 300;
                $_SESSION['otp_user'] = $user['id'];

                // SEND OTP
                require 'send_otp.php';

                if (sendOTP($user['email'], $otp)) {

                    header("Location: verify_otp.php");
                    exit();

                } else {

                    $error = "Failed to send OTP.";

                }

            } else {

                // increment attempts
                $stmt = $conn->prepare("
                    UPDATE login_security 
                    SET login_attempts = login_attempts + 1,
                        last_attempt = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                ");
                $stmt->execute([$user['id']]);

                $remaining = 3 - ($attempts + 1);

                $error = $remaining <= 0
                    ? "Too many attempts. Try again after 5 minutes."
                    : "Wrong password. Attempts left: $remaining";
            }
        }
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<title>Sunvarge - Login</title>

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

<link rel="stylesheet" href="styles/login.css" />

</head>
<body>

<div class="bg-grid"></div>
<div class="bg-glow"></div>

<div class="site-logo">
    <img src="img/logo.png" class="logo-icon" alt="Sunvarge">
</div>

<div class="card">

    <h2 class="card-title">Sign in</h2>

    <form action="login.php" method="POST">

        <div class="form-group">
            <label>Email</label>

            <div class="input-wrap">
                <input 
                    type="email" 
                    name="email" 
                    class="input-field"
                    placeholder="Enter your Email" 
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label>Password</label>

            <div class="input-wrap">
                <input 
                    type="password" 
                    name="password" 
                    class="input-field"
                    placeholder="Enter your Password" 
                    required
                >
            </div>
        </div>

        <a href="forgot_password.php" class="forgot-link">
            Forgot Password?
        </a>

        <div class="captcha-box">
            <img src="captcha.php" alt="CAPTCHA">

            <input 
                type="text" 
                name="captcha" 
                class="input-field"
                placeholder="Enter CAPTCHA" 
                required
            >
        </div>

        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($_ENV['RECAPTCHA_SITE_KEY'] ?? ''); ?>"></div>

        <?php if (!empty($error)) { ?>
            <div class="message error">
                <?php echo $error; ?>
            </div>
        <?php } ?>

        <button type="submit" class="btn-primary">
            LOG IN
        </button>

    </form>

    <div class="or-divider">
        <span>or</span>
    </div>

    <div class="social-row">

        <a href="google_login.php" class="google-btn">
            <img src="img/google.png" alt="Google" class="google-icon">
            <span>Google</span>
        </a>

    </div>

    <p class="switch-link">
        Don't have an account?
        <a href="signup.php">
        Create Account
        </a>
    </p>

    

</div>

</body>
</html>
