<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['unread' => 0]);
    exit();
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

// Count pending friend requests
$stmt = $conn->prepare("SELECT COUNT(*) FROM friend_requests WHERE receiver_id = ? AND status = 'pending'");
$stmt->execute([$userId]);
$unreadRequests = $stmt->fetchColumn();

// Count unread messages (assuming there's a status column or similar)
// For now, let's just use friend requests as a proxy or just return a combined count
// If messages table has a 'read_status' or similar, we should use it.
// Checking schema for messages...
/*
$stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadMessages = $stmt->fetchColumn();
*/
$unreadMessages = 0; // Placeholder until schema confirmed

echo json_encode([
    'unread' => (int)$unreadRequests + (int)$unreadMessages,
    'friend_requests' => (int)$unreadRequests,
    'messages' => (int)$unreadMessages
]);
