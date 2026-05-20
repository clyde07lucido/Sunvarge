<?php
include "db.php";

if (!isset($_GET['token'])) {
    die("Invalid request.");
}

$token = $_GET['token'];

$stmt = $conn->prepare("
    SELECT id 
    FROM users 
    WHERE reset_token = ? 
    AND token_expiry > ?
");
$stmt->execute([$token, date("Y-m-d H:i:s")]);

$user = $stmt->fetch();

if (!$user) {
    die("Invalid or expired token.");
}

$user_id = $user['id'];

$error = "";

// HANDLE SUBMIT
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm)) {

        $error = "All fields are required.";

    } elseif ($password !== $confirm) {

        $error = "Passwords do not match.";

    } else {

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE users 
            SET password = ?, 
                reset_token = NULL, 
                token_expiry = NULL 
            WHERE id = ?
        ");

        $stmt->execute([$hashed, $user_id]);

        header("Location: login.php?reset=success");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>Sunvarge - Reset Password</title>

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

<link rel="stylesheet" href="styles/reset_password.css" />

</head>
<body>

<div class="bg-grid"></div>
<div class="bg-glow"></div>

<div class="site-logo">
    <img src="img/logo.png" class="logo-icon" alt="Sunvarge">
</div>

<div class="card">

    <h2 class="card-title">Reset Password</h2>

    <p class="subtitle">
        Enter your new password and confirm it below.
    </p>

    <?php if (!empty($error)) { ?>
        <div class="message error">
            <?php echo $error; ?>
        </div>
    <?php } ?>

    <form method="POST">

        <div class="form-group">

            <label>New Password</label>

            <div class="input-wrap">

                <input 
                    type="password" 
                    name="password"
                    class="input-field"
                    placeholder="Enter New Password"
                    required
                >

            </div>

        </div>

        <div class="form-group">

            <label>Confirm Password</label>

            <div class="input-wrap">

                <input 
                    type="password" 
                    name="confirm_password"
                    class="input-field"
                    placeholder="Confirm New Password"
                    required
                >

            </div>

        </div>

        <button type="submit" class="btn-primary">
            UPDATE PASSWORD
        </button>

    </form>

    <p class="note">
        Make sure both passwords match before submitting.
    </p>

</div>

</body>
</html>
