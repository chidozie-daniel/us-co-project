<?php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$storyId = $_GET['id'] ?? 0;

if ($storyId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT s.*, u.username 
        FROM stories s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.id = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$storyId]);
    $story = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($story) {
        header('Content-Type: application/json');
        echo json_encode($story);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Story not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Story ID required']);
}
?>
