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

$receiverId = $_POST['receiver_id'] ?? null;
$messageContent = sanitize($_POST['message'] ?? '');
$senderId = $_SESSION['user_id'];

if (!$receiverId || !$messageContent) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Insert message
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $result = $stmt->execute([$senderId, $receiverId, $messageContent]);
    
    if ($result) {
        $msgId = $conn->lastInsertId();
        
        // Notify receiver (optional hook)
        // createNotification($receiverId, $senderId, 'message'); 
        
        // Fetch the inserted message to return (for confirming it looks right on FE immediately)
        // Or mostly just success is enough, but returning data is nicer.
        echo json_encode([
            'success' => true, 
            'message_id' => $msgId,
            'sent_at' => date('Y-m-d H:i:s') 
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database insert failed']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
