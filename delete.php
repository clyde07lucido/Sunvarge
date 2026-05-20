<?php
session_start();
include "db.php";

// 🔐 Must be logged in (important security fix)
if (!isset($_SESSION['authenticated'])) {
    header("Location: login.php");
    exit();
}

// 🔐 Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request.");
}

$id = (int) $_GET['id'];

// 🔐 Delete user (cascade handles other tables)
$stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
$stmt->execute([':id' => $id]);

header("Location: dashboard.php");
exit();
?>