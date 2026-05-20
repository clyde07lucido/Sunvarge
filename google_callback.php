<?php
session_start();
if (isset($_SESSION['authenticated'])) {
    session_unset();
    session_destroy();
    session_start();
}

include "db.php";
require 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? '');
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI'] ?? '');

$client->addScope("email");
$client->addScope("profile");

/* ---------------------------
   1. CSRF STATE CHECK (SAFE)
---------------------------- */
if (!isset($_GET['state']) || !isset($_SESSION['oauth_state'])) {
    die("Invalid state session.");
}

if ($_GET['state'] !== $_SESSION['oauth_state']) {
    die("Invalid state mismatch.");
}

unset($_SESSION['oauth_state']);

/* ---------------------------
   2. GET ACCESS TOKEN
---------------------------- */
if (!isset($_GET['code'])) {
    header("Location: google_login.php");
    exit();
}

$client->authenticate($_GET['code']);
$token = $client->getAccessToken();
$client->setAccessToken($token);

/* ---------------------------
   3. GET GOOGLE USER INFO
---------------------------- */
$oauth = new Google_Service_Oauth2($client);
$userInfo = $oauth->userinfo->get();

$email = $userInfo->email;
$firstname = $userInfo->givenName;
$lastname = $userInfo->familyName;
$google_id = $userInfo->id;

/* ---------------------------
   4. CHECK USER
---------------------------- */
$stmt = $conn->prepare(" SELECT * FROM users  WHERE email = ? AND provider = 'google' ");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {

    // extra safety: prevent conflict with existing signup accounts
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->execute([$email]);
    $existing = $check->fetch();

    if ($existing) {
        $_SESSION['login_error'] = "This email is already registered using Sign-up. Please login normally.";
        header("Location: login.php");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO users (email, provider) VALUES (?, 'google')");
    $stmt->execute([$email]);

    $user_id = $conn->lastInsertId();

    $stmt = $conn->prepare("
        INSERT INTO user_profiles (user_id, firstname, lastname) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user_id, $firstname, $lastname]);

    $stmt = $conn->prepare("
        INSERT INTO user_providers (user_id, provider, provider_id) 
        VALUES (?, 'google', ?)
    ");
    $stmt->execute([$user_id, $google_id]);

} else {
    $user_id = $user['id'];
}

/* ---------------------------
   5. LOGIN USER
---------------------------- */
// GENERATE OTP
$otp = rand(100000, 999999);

// SAVE OTP SESSION
$_SESSION['otp'] = $otp;
$_SESSION['otp_expiry'] = time() + 300;
$_SESSION['otp_user'] = $user_id;

// SEND OTP
require 'send_otp.php';

if (sendOTP($email, $otp)) {

    header("Location: verify_otp.php");
    exit();

} else {

    $_SESSION['login_error'] = "Failed to send OTP.";

    header("Location: login.php");
    exit();

}
