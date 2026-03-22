<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'];
$content = $_POST['content'] ?? '';
$visibility = $_POST['visibility'] ?? 'public';

if (empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Post content cannot be empty']);
    exit();
}

try {
    $conn = getDBConnection();
    $mediaPath = null;
    $mediaType = $_POST['media_type'] ?? 'none'; // Default to none
    $feeling = $_POST['feeling'] ?? null;
    $feelingIcon = $_POST['feeling_icon'] ?? null;

    // Handle File Uploads (Image or Video)
    if (isset($_FILES['media']) && $_FILES['media']['error'] === 0) {
        $uploadDir = '../uploads/posts/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileInfo = pathinfo($_FILES['media']['name']);
        $extension = strtolower($fileInfo['extension']);
        $filename = time() . '_' . uniqid() . '.' . $extension;
        $fullPath = $uploadDir . $filename;
        
        // Simple validation
        $validImageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $validVideoExts = ['mp4', 'webm', 'ogg'];

        if (in_array($extension, $validImageExts)) {
            $mediaType = 'image';
        } elseif (in_array($extension, $validVideoExts)) {
             $mediaType = 'video';
        } else {
             throw new Exception("Invalid file type.");
        }

        if (move_uploaded_file($_FILES['media']['tmp_name'], $fullPath)) {
            $mediaPath = 'uploads/posts/' . $filename;
        } else {
            throw new Exception("Failed to save file.");
        }
    } 
    // Handle 'image' input for backward compatibility if 'media' not sent
    elseif (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
         $uploadDir = '../uploads/posts/';
         if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
         $filename = time() . '_' . $_FILES['image']['name'];
         if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
             $mediaPath = 'uploads/posts/' . $filename;
             $mediaType = 'image';
         }
    }


    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, media_path, media_type, visibility, feeling, feeling_icon) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $content, $mediaPath, $mediaType, $visibility, $feeling, $feelingIcon]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
