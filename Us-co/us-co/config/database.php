<?php
// Database configuration
define('DB_HOST', 'sql112.infinityfree.com'); 
define('DB_USER', 'if0_41153201'); 
define('DB_PASS', 'OG2Qf178XhTuvC2'); 
define('DB_NAME', 'if0_41153201_everest');

// Establish database connection
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10
            ]
        );
        return $conn;
    } catch(PDOException $e) {
        throw $e;
    }
}

// Create tables if they don't exist
function createTables() {
    $conn = getDBConnection();
    
    // Users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        profile_pic VARCHAR(255) DEFAULT 'default.jpg',
        cover_pic VARCHAR(255) DEFAULT 'cover_default.jpg',
        bio TEXT,
        location VARCHAR(100),
        occupation VARCHAR(100),
        education VARCHAR(100),
        hobbies TEXT,
        relationship_status VARCHAR(50),
        anniversary_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);

    // Add columns if they don't exist (using ALTER TABLE for existing users)
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN cover_pic VARCHAR(255) DEFAULT 'cover_default.jpg' AFTER profile_pic");
    } catch(Exception $e) {}
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN location VARCHAR(100) AFTER bio");
    } catch(Exception $e) {}
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN occupation VARCHAR(100) AFTER location");
    } catch(Exception $e) {}
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN education VARCHAR(100) AFTER occupation");
    } catch(Exception $e) {}
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN hobbies TEXT AFTER education");
    } catch(Exception $e) {}
    
    // Gallery table
    $sql = "CREATE TABLE IF NOT EXISTS gallery (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        image_path VARCHAR(255) NOT NULL,
        caption TEXT,
        album VARCHAR(100),
        tags TEXT,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    
    // Articles table
    $sql = "CREATE TABLE IF NOT EXISTS articles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        category VARCHAR(50),
        tags TEXT,
        views INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    
    // Messages table
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT,
        receiver_id INT,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    
    // Posts table (for timeline)
    $sql = "CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        content TEXT NOT NULL,
        media_path VARCHAR(255),
        media_type VARCHAR(20) DEFAULT 'none',
        visibility ENUM('public', 'friends', 'private') DEFAULT 'public',
        feeling VARCHAR(50),
        feeling_icon VARCHAR(10),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);

    // Add columns to posts if they don't exist
    try {
        $conn->exec("ALTER TABLE posts ADD COLUMN visibility ENUM('public', 'friends', 'private') DEFAULT 'public' AFTER media_path");
    } catch(Exception $e) {}
    try {
        $conn->exec("ALTER TABLE posts ADD COLUMN media_type VARCHAR(20) DEFAULT 'none' AFTER media_path");
    } catch(Exception $e) {}
    try {
        $conn->exec("ALTER TABLE posts ADD COLUMN feeling VARCHAR(50) AFTER visibility");
    } catch(Exception $e) {}
    try {
        $conn->exec("ALTER TABLE posts ADD COLUMN feeling_icon VARCHAR(10) AFTER feeling");
    } catch(Exception $e) {}

    // Friends table
    $sql = "CREATE TABLE IF NOT EXISTS friends (
        user_id1 INT,
        user_id2 INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id1, user_id2),
        FOREIGN KEY (user_id1) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id2) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);

    // Friend Requests table
    $sql = "CREATE TABLE IF NOT EXISTS friend_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT,
        receiver_id INT,
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);

    // Post Likes table
    $sql = "CREATE TABLE IF NOT EXISTS post_likes (
        post_id INT,
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (post_id, user_id),
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);

    // Notifications table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        sender_id INT,
        type VARCHAR(50) NOT NULL,
        entity_id INT,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    
    // Comments table
    $sql = "CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT,
        user_id INT,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    
    // Events table
    $sql = "CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        event_time TIME,
        reminder_sent BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    
    // Valentine Letters table
    $sql = "CREATE TABLE IF NOT EXISTS valentine_letters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT,
        recipient VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        anonymous BOOLEAN DEFAULT FALSE,
        gift_type VARCHAR(50),
        gift_message TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);

    // Valentine Thoughts table (for guest reflections)
    $sql = "CREATE TABLE IF NOT EXISTS valentine_thoughts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        author_name VARCHAR(100),
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    
    // Stories table
    $sql = "CREATE TABLE IF NOT EXISTS stories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        media_path VARCHAR(255) NOT NULL,
        media_type VARCHAR(10) NOT NULL,
        caption TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);

    // Ensure compatibility with strict SQL modes (avoid invalid implicit defaults)
    try {
        $conn->exec("ALTER TABLE stories MODIFY expires_at TIMESTAMP NULL DEFAULT NULL");
    } catch(Exception $e) {}
    
    $conn = null;
}

// Auto-create tables only when explicitly enabled.
if (defined('DB_AUTO_INIT') && DB_AUTO_INIT === true && (!defined('SKIP_DB_AUTO_INIT') || SKIP_DB_AUTO_INIT !== true)) {
    createTables();
}
?>
