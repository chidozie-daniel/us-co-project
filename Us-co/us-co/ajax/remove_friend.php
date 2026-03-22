<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$friendId = $_POST['friend_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$friendId) {
    echo json_encode(['success' => false, 'error' => 'Friend ID required']);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Remove from friends table
    $stmt = $conn->prepare("DELETE FROM friends WHERE (user_id1 = ? AND user_id2 = ?) OR (user_id1 = ? AND user_id2 = ?)");
    $stmt->execute([$userId, $friendId, $friendId, $userId]);
    
    // Remove any related accepted requests
    $stmt = $conn->prepare("DELETE FROM friend_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    $stmt->execute([$userId, $friendId, $friendId, $userId]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
