<?php
require_once 'functions.php';

// Handle user registration
function registerUser($username, $email, $password, $confirmPassword) {
    $errors = [];
    
    // Rate limiting check
    if (!checkRateLimit('register', 3, 600)) {
        $errors[] = "Too many registration attempts. Please try again in 10 minutes.";
        return ['success' => false, 'errors' => $errors];
    }
    
    // Enhanced validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (!validateUsername($username)) {
        $errors[] = "Username must be 3-20 characters and contain only letters, numbers, and underscores";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!validateEmail($email)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (!validatePassword($password)) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords don't match";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    try {
        $conn = getDBConnection();
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username or email already exists";
        } else {
            // Hash password with secure algorithm
            $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
            
            // Insert user with prepared statement
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashedPassword])) {
                $userId = $conn->lastInsertId();
                
                // Auto-login after registration
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['profile_pic'] = 'default.jpg'; // New registration
                $_SESSION['login_time'] = time();
                
                return ['success' => true, 'user_id' => $userId];
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        $errors[] = "Registration failed due to server error. Please try again.";
    }
    
    return ['success' => false, 'errors' => $errors];
}

// Handle user login
function loginUser($username, $password) {
    $errors = [];
    
    // Rate limiting check
    if (!checkRateLimit('login', 5, 300)) {
        $errors[] = "Too many login attempts. Please try again in 5 minutes.";
        return ['success' => false, 'errors' => $errors];
    }
    
    // Input validation
    if (empty($username)) {
        $errors[] = "Username or email is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    try {
        $conn = getDBConnection();
        
        // Check if user exists (by username or email)
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_pic'] = $user['profile_pic'] ?? 'default.jpg';
            $_SESSION['login_time'] = time();
            
            // Clear login rate limit
            unset($_SESSION['rate_limit']['login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')]);
            
            return ['success' => true];
        } else {
            $errors[] = "Invalid username or password";
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $errors[] = "Login failed due to server error. Please try again.";
    }
    
    return ['success' => false, 'errors' => $errors];
}

// Handle logout
function logoutUser() {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Update user profile
function updateProfile($userId, $data) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $conn = getDBConnection();
    
    // Build update query
    $fields = [];
    $params = [];
    
    // List of allowed fields to update
    $allowedFields = [
        'bio', 'location', 'occupation', 'education', 
        'hobbies', 'relationship_status', 'anniversary_date'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            // Anniversary date might be null if empty
            if ($field === 'anniversary_date' && empty($data[$field])) {
                 $params[] = null;
            } else {
                 $params[] = sanitize($data[$field]);
            }
        }
    }
    
    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $upload = uploadFile($_FILES['profile_pic'], 'profile');
        if ($upload['success']) {
            $fields[] = "profile_pic = ?";
            $params[] = basename($upload['path']);
            
            // Delete old profile pic if it's not default (optional optimization)
        }
    }

    // Handle cover photo upload
    if (isset($_FILES['cover_pic']) && $_FILES['cover_pic']['error'] == 0) {
        $upload = uploadFile($_FILES['cover_pic'], 'cover');
        if ($upload['success']) {
            $fields[] = "cover_pic = ?";
            $params[] = basename($upload['path']);
        }
    }
    
    if (!empty($fields)) {
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute($params);
    }
    
    return true; // Return true if nothing to update but no error
}
?>