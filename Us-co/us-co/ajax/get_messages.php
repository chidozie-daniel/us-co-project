<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$otherUserId = $_GET['user_id'] ?? null;
$lastMsgId = $_GET['last_id'] ?? 0;
$currentUserId = $_SESSION['user_id'];

if (!$otherUserId) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit();
}

try {
    $conn = getDBConnection();

    // Mark messages as read
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $stmt->execute([$otherUserId, $currentUserId]);

    // Fetch messages
    // If last_id is provided, only fetch newer messages
    $sql = "
        SELECT m.*, u.username as sender_name, u.profile_pic as sender_pic
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?))
    ";
    
    $params = [$currentUserId, $otherUserId, $otherUserId, $currentUserId];

    if ($lastMsgId > 0) {
        $sql .= " AND m.id > ?";
        $params[] = $lastMsgId;
    }

    $sql .= " ORDER BY m.sent_at ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for JS
    $formattedMessages = [];
    foreach ($messages as $m) {
        $formattedMessages[] = [
            'id' => $m['id'],
            'sender_id' => $m['sender_id'],
            'message' => nl2br(htmlspecialchars($m['message'])),
            'sent_at' => date('g:i A', strtotime($m['sent_at'])),
            'is_me' => ($m['sender_id'] == $currentUserId),
            'sender_pic' => basename(getProfilePic($m['sender_pic'])),
            'is_read' => $m['is_read']
        ];
    }

    echo json_encode(['success' => true, 'messages' => $formattedMessages]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
