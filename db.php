<?php
require_once __DIR__ . '/env.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '5432';
$dbname = $_ENV['DB_NAME'] ?? '';
$user = $_ENV['DB_USER'] ?? '';
$password = $_ENV['DB_PASS'] ?? '';

try {
    $conn = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
?>
