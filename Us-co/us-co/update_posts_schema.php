<?php
require_once 'includes/functions.php';
$conn = getDBConnection();

try {
    // Add media_type column
    $conn->exec("ALTER TABLE posts ADD COLUMN media_type ENUM('image', 'video', 'none') DEFAULT 'none'");
    echo "Added media_type column.<br>";

    // Add feeling column
    $conn->exec("ALTER TABLE posts ADD COLUMN feeling VARCHAR(50) DEFAULT NULL");
    echo "Added feeling column.<br>";

    // Add feeling_icon column
    $conn->exec("ALTER TABLE posts ADD COLUMN feeling_icon VARCHAR(50) DEFAULT NULL");
    echo "Added feeling_icon column.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
