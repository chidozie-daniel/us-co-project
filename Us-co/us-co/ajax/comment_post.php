<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'];
$postId = $_POST['post_id'] ?? null;
$commentText = $_POST['comment'] ?? '';

if (!$postId || empty($commentText)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Insert comment
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$postId, $userId, $commentText]);
    $commentId = $conn->lastInsertId();

    // Get comment count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
    $stmt->execute([$postId]);
    $count = $stmt->fetchColumn();

    // Notify post owner
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $ownerId = $stmt->fetchColumn();

    if ($ownerId && $ownerId != $userId) {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, sender_id, type, entity_id) VALUES (?, ?, 'comment', ?)");
        $stmt->execute([$ownerId, $userId, $postId]);
    }

    echo json_encode([
        'success' => true, 
        'count' => $count,
        'comment_id' => $commentId
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
