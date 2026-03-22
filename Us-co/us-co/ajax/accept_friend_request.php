<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$requestId = $_POST['request_id'] ?? null;
$status = $_POST['status'] ?? null; // 'accepted' or 'rejected'

if (!$requestId || !in_array($status, ['accepted', 'rejected'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Get request details
    $stmt = $conn->prepare("SELECT * FROM friend_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt->execute([$requestId, $_SESSION['user_id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode(['success' => false, 'error' => 'Request not found']);
        exit();
    }
    
    if ($status === 'accepted') {
        // Add to friends table
        $stmt = $conn->prepare("INSERT INTO friends (user_id1, user_id2) VALUES (?, ?)");
        $stmt->execute([$request['sender_id'], $request['receiver_id']]);
        
        // Update request status
        $stmt = $conn->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$requestId]);
        
        // Notify sender
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, sender_id, type) VALUES (?, ?, 'friend_accept')");
        $stmt->execute([$request['sender_id'], $_SESSION['user_id']]);
    } else {
        // Delete request
        $stmt = $conn->prepare("DELETE FROM friend_requests WHERE id = ?");
        $stmt->execute([$requestId]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
