<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$senderId = $_SESSION['user_id'];
$receiverId = $_POST['receiver_id'] ?? null;

if (!$receiverId) {
    echo json_encode(['success' => false, 'error' => 'Receiver ID required']);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Check if request already exists
    $stmt = $conn->prepare("SELECT id FROM friend_requests WHERE 
        (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    $stmt->execute([$senderId, $receiverId, $receiverId, $senderId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'error' => 'Request already exists']);
        exit();
    }
    
    // Send request
    $stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)");
    $stmt->execute([$senderId, $receiverId]);
    
    // Create notification
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, sender_id, type) VALUES (?, ?, 'friend_request')");
    $stmt->execute([$receiverId, $senderId]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
