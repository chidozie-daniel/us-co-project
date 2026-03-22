<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Get photo ID
$photoId = $_GET['id'] ?? null;

if (!$photoId || !is_numeric($photoId)) {
    echo json_encode(['success' => false, 'error' => 'Invalid photo ID']);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Get photo details and verify ownership
    $stmt = $conn->prepare("SELECT * FROM gallery WHERE id = ? AND user_id = ?");
    $stmt->execute([$photoId, $_SESSION['user_id']]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$photo) {
        echo json_encode(['success' => false, 'error' => 'Photo not found or access denied']);
        exit();
    }
    
    // Delete photo from database
    $stmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
    $stmt->execute([$photoId]);
    
    // Delete physical file
    if (file_exists($photo['image_path'])) {
        unlink($photo['image_path']);
    }
    
    echo json_encode(['success' => true, 'message' => 'Photo deleted successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
