<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'];
$postId = $_POST['post_id'] ?? null;

if (!$postId) {
    echo json_encode(['success' => false, 'error' => 'Post ID required']);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Check if liked
    $stmt = $conn->prepare("SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
    $liked = $stmt->rowCount() > 0;

    if ($liked) {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $userId]);
        $action = 'unliked';
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$postId, $userId]);
        $action = 'liked';
        
        // Notify post owner
        $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $ownerId = $stmt->fetchColumn();
        
        if ($ownerId && $ownerId != $userId) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, sender_id, type, entity_id) VALUES (?, ?, 'like', ?)");
            $stmt->execute([$ownerId, $userId, $postId]);
        }
    }

    // Get new count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
    $stmt->execute([$postId]);
    $count = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'action' => $action, 'count' => $count]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
