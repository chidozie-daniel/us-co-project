<?php
// Temporary database connectivity check for production hosting.
// Remove this file after fixing configuration.

define('SKIP_DB_AUTO_INIT', true);
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "DB connectivity check\n";
echo "---------------------\n";
echo "Host: " . DB_HOST . "\n";
echo "User: " . DB_USER . "\n";
echo "Name: " . DB_NAME . "\n";
echo "PHP: " . PHP_VERSION . "\n\n";

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,
        ]
    );
    $stmt = $conn->query("SELECT NOW() AS server_time");
    $row = $stmt->fetch();
    echo "SUCCESS: Connected.\n";
    echo "Server time: " . ($row['server_time'] ?? 'unknown') . "\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n\n";
    echo "Checklist:\n";
    echo "1) MySQL hostname must match your InfinityFree panel exactly.\n";
    echo "2) Username must match exactly.\n";
    echo "3) Database name is case sensitive.\n";
    echo "4) Password should be the hosting account password (not Client Area password).\n";
    echo "5) If changed recently, wait a few minutes and retry.\n";
}
