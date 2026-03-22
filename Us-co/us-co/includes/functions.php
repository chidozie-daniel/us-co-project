<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

// Sanitize input data
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Escape for HTML output
if (!function_exists('esc')) {
    function esc($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// Escape for HTML output + newlines
if (!function_exists('escNl')) {
    function escNl($text) {
        return nl2br(esc($text));
    }
}

// Upload file with validation
function uploadFile($file, $type = 'image') {
    try {
        // Check if file was uploaded
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'error' => 'No file uploaded'];
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            ];
            return ['success' => false, 'error' => $errorMessages[$file['error']] ?? 'Unknown upload error'];
        }
        
        if ($type == 'profile') {
            $uploadDir = 'uploads/profile_pics/';
        } elseif ($type == 'cover') {
            $uploadDir = 'uploads/covers/';
        } else {
            $uploadDir = 'uploads/gallery/';
        }
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'error' => 'Failed to create upload directory'];
            }
        }
        
        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            return ['success' => false, 'error' => 'Upload directory is not writable'];
        }
        
        $fileName = time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        
        // Validate file
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'webp'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($fileType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)];
        }
        
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File too large. Maximum size: 10MB'];
        }
        
        // Additional security checks
        if (!getimagesize($file['tmp_name']) && !in_array($fileType, ['mp4', 'mov'])) {
            return ['success' => false, 'error' => 'Invalid image file'];
        }
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => true, 'path' => $targetPath];
        } else {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }
        
    } catch (Exception $e) {
        error_log("Upload error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Upload failed due to server error'];
    }
}

// Enhanced validation functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function validatePassword($password) {
    return strlen($password) >= 8;
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting
function checkRateLimit($action, $limit = 5, $window = 300) {
    $key = $action . '_' . ($_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR']);
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = ['count' => 0, 'start' => time()];
    }
    
    $rateData = $_SESSION['rate_limit'][$key];
    
    // Reset window if expired
    if (time() - $rateData['start'] > $window) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'start' => time()];
        return true;
    }
    
    // Check limit
    if ($rateData['count'] >= $limit) {
        return false;
    }
    
    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}

// Get Valentine's Day countdown
function getValentinesCountdown() {
    $currentYear = date('Y');
    $valentinesDay = strtotime("February 14, $currentYear");
    $now = time();
    
    if ($valentinesDay < $now) {
        $currentYear++;
        $valentinesDay = strtotime("February 14, $currentYear");
    }
    
    $diff = $valentinesDay - $now;
    $days = floor($diff / (60 * 60 * 24));
    $hours = floor(($diff % (60 * 60 * 24)) / (60 * 60));
    
    return [
        'days' => $days,
        'hours' => $hours,
        'timestamp' => $valentinesDay
    ];
}

// Format date nicely
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Get relative time (e.g., 2 hours ago)
function getTimeAgo($timestamp) {
    $time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return "Just now";
    if ($diff < 3600) return floor($diff / 60) . "m ago";
    if ($diff < 86400) return floor($diff / 3600) . "h ago";
    if ($diff < 604800) return floor($diff / 86400) . "d ago";
    
    return date('M j', $time);
}

// Create notification
function createNotification($userId, $senderId, $type, $entityId = null) {
    if ($userId == $senderId) return false;
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, sender_id, type, entity_id) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$userId, $senderId, $type, $entityId]);
}

// Redirect helper
function redirect($url, $statusCode = 302) {
    header("Location: $url", true, $statusCode);
    exit();
}

// Get relationship duration
function getRelationshipDuration($anniversary) {
    if (!$anniversary) return "Not set";
    
    $anniversary = new DateTime($anniversary);
    $today = new DateTime();
    $interval = $today->diff($anniversary);
    
    return $interval->format('%y years, %m months');
}

// Get profile picture path with fallback
function getProfilePic($user) {
    $pic = (is_array($user) ? $user['profile_pic'] : $user) ?: 'default.jpg';
    return 'uploads/profile_pics/' . $pic;
}

// Get cover picture path with fallback
function getCoverPic($user) {
    $pic = (is_array($user) ? $user['cover_pic'] : $user) ?: 'cover_default.jpg';
    return 'uploads/covers/' . $pic;
}

// Spotify helpers
function extractSpotifyPlaylistId($value) {
    if (!$value) return null;
    $value = trim($value);

    // https://open.spotify.com/playlist/{id}
    if (preg_match('#spotify\.com/playlist/([a-zA-Z0-9]+)#i', $value, $m)) {
        return $m[1];
    }

    // spotify:playlist:{id}
    if (preg_match('#spotify:playlist:([a-zA-Z0-9]+)#i', $value, $m)) {
        return $m[1];
    }

    return null;
}

function normalizeSpotifyPlaylistUrl($value) {
    $playlistId = extractSpotifyPlaylistId($value);
    if (!$playlistId) return null;
    return 'https://open.spotify.com/playlist/' . $playlistId;
}

function getSpotifyEmbedUrl($value) {
    $playlistId = extractSpotifyPlaylistId($value);
    if (!$playlistId) return null;
    return 'https://open.spotify.com/embed/playlist/' . $playlistId;
}
?>
