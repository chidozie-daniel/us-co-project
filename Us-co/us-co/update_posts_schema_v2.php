<?php
require_once 'includes/functions.php';
$conn = getDBConnection();

$columns = $conn->query("SHOW COLUMNS FROM posts")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('media_type', $columns)) {
    try {
        $conn->exec("ALTER TABLE posts ADD COLUMN media_type ENUM('image', 'video', 'none') DEFAULT 'none'");
        echo "Added media_type column.<br>";
    } catch (PDOException $e) { echo "Error adding media_type: " . $e->getMessage() . "<br>"; }
} else {
    echo "media_type column already exists.<br>";
}

if (!in_array('feeling', $columns)) {
    try {
        $conn->exec("ALTER TABLE posts ADD COLUMN feeling VARCHAR(50) DEFAULT NULL");
        echo "Added feeling column.<br>";
    } catch (PDOException $e) { echo "Error adding feeling: " . $e->getMessage() . "<br>"; }
} else {
    echo "feeling column already exists.<br>";
}

if (!in_array('feeling_icon', $columns)) {
    try {
        $conn->exec("ALTER TABLE posts ADD COLUMN feeling_icon VARCHAR(50) DEFAULT NULL");
        echo "Added feeling_icon column.<br>";
    } catch (PDOException $e) { echo "Error adding feeling_icon: " . $e->getMessage() . "<br>"; }
} else {
    echo "feeling_icon column already exists.<br>";
}
?>
