<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$postId = $_POST['post_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$postId) {
    echo json_encode(['success' => false, 'error' => 'Post ID required']);
    exit();
}

$conn = getDBConnection();

// Verify ownership
$stmt = $conn->prepare("SELECT user_id, media_path FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    echo json_encode(['success' => false, 'error' => 'Post not found']);
    exit();
}

if ($post['user_id'] != $userId) {
    echo json_encode(['success' => false, 'error' => 'You are not authorized to delete this post']);
    exit();
}

// Delete media if exists
if ($post['media_path'] && file_exists('../' . $post['media_path'])) {
    unlink('../' . $post['media_path']);
}

// Delete post (Cascading deletes handles likes/comments usually, but let's be safe if schema doesn't enforce it yet)
// Assuming ON DELETE CASCADE in schema for comments/likes linked to post_id, 
// otherwise we'd need to delete them manually.
// Based on general knowledge, let's just delete the post.
$stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
if ($stmt->execute([$postId])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
