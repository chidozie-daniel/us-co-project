<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    // Add relationship_status
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN relationship_status VARCHAR(50) DEFAULT 'Single'");
        echo "Added relationship_status column.\n";
    } catch (PDOException $e) {
        echo "relationship_status column might already exist or error: " . $e->getMessage() . "\n";
    }

    // Add anniversary_date
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN anniversary_date DATE DEFAULT NULL");
        echo "Added anniversary_date column.\n";
    } catch (PDOException $e) {
        echo "anniversary_date column might already exist or error: " . $e->getMessage() . "\n";
    }
    
    // Add cover_pic if missing (just to be safe, though check_users_cols showed it)
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN cover_pic VARCHAR(255) DEFAULT 'cover_default.jpg'");
        echo "Added cover_pic column.\n";
    } catch (PDOException $e) {
        echo "cover_pic column might already exist.\n";
    }

    // Add spotify_playlist_url
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN spotify_playlist_url VARCHAR(255) DEFAULT NULL");
        echo "Added spotify_playlist_url column.\n";
    } catch (PDOException $e) {
        echo "spotify_playlist_url column might already exist.\n";
    }

} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}
?>
