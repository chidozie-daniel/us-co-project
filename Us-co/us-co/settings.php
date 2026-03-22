<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect if not logged in (before any HTML output)
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = "Our Settings";
require_once 'includes/header.php';

$user = getCurrentUser();
$success = '';
$error = '';
$spotifyEmbedUrl = null;

// Ensure spotify_playlist_url column exists
try {
    $conn = getDBConnection();
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'spotify_playlist_url'");
    if ($stmt->rowCount() === 0) {
        $conn->exec("ALTER TABLE users ADD COLUMN spotify_playlist_url VARCHAR(255) DEFAULT NULL");
    }
} catch (Exception $e) {
    // Keep page usable even if migration step fails.
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (password_verify($currentPassword, $user['password'])) {
        if ($newPassword === $confirmPassword) {
            if (strlen($newPassword) >= 6) {
                $conn = getDBConnection();
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                
                if ($stmt->execute([$hashedPassword, $_SESSION['user_id']])) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to update password.';
                }
            } else {
                $error = 'New password must be at least 6 characters.';
            }
        } else {
            $error = 'New passwords do not match.';
        }
    } else {
        $error = 'Current password is incorrect.';
    }
}

// Handle email change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_email'])) {
    $newEmail = sanitize($_POST['new_email']);
    $password = $_POST['password'];
    
    if (password_verify($password, $user['password'])) {
        if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $conn = getDBConnection();
            
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() === 0) {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                if ($stmt->execute([$newEmail, $_SESSION['user_id']])) {
                    $success = 'Email updated successfully!';
                    $user = getCurrentUser();
                } else {
                    $error = 'Failed to update email.';
                }
            } else {
                $error = 'This email is already in use.';
            }
        } else {
            $error = 'Invalid email format.';
        }
    } else {
        $error = 'Password is incorrect.';
    }
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $password = $_POST['delete_password'];
    $confirmation = $_POST['delete_confirmation'];
    
    if ($confirmation === 'DELETE' && password_verify($password, $user['password'])) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        
        if ($stmt->execute([$_SESSION['user_id']])) {
            session_destroy();
            header("Location: index.php?deleted=true");
            exit();
        } else {
            $error = 'Failed to delete account.';
        }
    } else {
        $error = 'Invalid password or confirmation text.';
    }
}

// Handle Spotify playlist save/remove
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_spotify_playlist'])) {
    $spotifyInput = sanitize($_POST['spotify_playlist_url'] ?? '');
    $normalizedUrl = normalizeSpotifyPlaylistUrl($spotifyInput);

    if (!$normalizedUrl) {
        $error = 'Please enter a valid Spotify playlist link.';
    } else {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("UPDATE users SET spotify_playlist_url = ? WHERE id = ?");
            if ($stmt->execute([$normalizedUrl, $_SESSION['user_id']])) {
                $success = 'Spotify playlist linked successfully!';
                $user = getCurrentUser();
            } else {
                $error = 'Failed to save Spotify playlist.';
            }
        } catch (Exception $e) {
            $error = 'Could not save Spotify playlist right now.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_spotify_playlist'])) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE users SET spotify_playlist_url = NULL WHERE id = ?");
        if ($stmt->execute([$_SESSION['user_id']])) {
            $success = 'Spotify playlist removed.';
            $user = getCurrentUser();
        } else {
            $error = 'Failed to remove Spotify playlist.';
        }
    } catch (Exception $e) {
        $error = 'Could not remove Spotify playlist right now.';
    }
}

$spotifyEmbedUrl = getSpotifyEmbedUrl($user['spotify_playlist_url'] ?? '');
?>

<div class="max-w-6xl mx-auto px-4 md:px-8 py-8">
    <!-- Header -->
    <div class="mb-12">
        <h1 class="text-5xl md:text-6xl font-display text-primary mb-2" style="font-family: 'Great Vibes', cursive;">
            Our Settings
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-lg">Manage your account preferences</p>
    </div>
    
    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-r-2xl p-6 mb-8">
            <div class="flex items-start gap-3">
                <span class="material-icons-round text-green-500">check_circle</span>
                <p class="text-green-800 dark:text-green-200"><?php echo $success; ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-r-2xl p-6 mb-8">
            <div class="flex items-start gap-3">
                <span class="material-icons-round text-red-500">error</span>
                <p class="text-red-800 dark:text-red-200"><?php echo $error; ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Main Settings -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Change Password -->
            <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl overflow-hidden">
                <div class="p-6 border-b border-slate-100 dark:border-white/5">
                    <h2 class="text-xl font-bold flex items-center gap-3">
                        <span class="material-icons-round text-primary">lock</span>
                        Change Password
                    </h2>
                </div>
                <div class="p-6">
                    <form method="POST" action="" class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold mb-2">Current Password</label>
                            <input type="password" name="current_password" required
                                   class="w-full px-4 py-3 bg-slate-100 dark:bg-white/5 border-none rounded-2xl focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2">New Password</label>
                            <input type="password" name="new_password" required
                                   class="w-full px-4 py-3 bg-slate-100 dark:bg-white/5 border-none rounded-2xl focus:ring-2 focus:ring-primary">
                            <p class="text-xs text-slate-500 mt-1">Minimum 6 characters</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" required
                                   class="w-full px-4 py-3 bg-slate-100 dark:bg-white/5 border-none rounded-2xl focus:ring-2 focus:ring-primary">
                        </div>
                        <button type="submit" name="change_password"
                                class="px-8 py-3 bg-primary text-white rounded-full font-bold shadow-lg shadow-primary/20 hover:scale-105 transition-all flex items-center gap-2">
                            <span class="material-icons-round text-sm">save</span>
                            Change Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Email -->
            <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl overflow-hidden">
                <div class="p-6 border-b border-slate-100 dark:border-white/5">
                    <h2 class="text-xl font-bold flex items-center gap-3">
                        <span class="material-icons-round text-primary">email</span>
                        Change Email
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-slate-500 mb-6">Current email: <strong class="text-slate-800 dark:text-white"><?php echo htmlspecialchars($user['email']); ?></strong></p>
                    <form method="POST" action="" class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold mb-2">New Email Address</label>
                            <input type="email" name="new_email" required
                                   class="w-full px-4 py-3 bg-slate-100 dark:bg-white/5 border-none rounded-2xl focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2">Confirm Password</label>
                            <input type="password" name="password" required
                                   class="w-full px-4 py-3 bg-slate-100 dark:bg-white/5 border-none rounded-2xl focus:ring-2 focus:ring-primary">
                        </div>
                        <button type="submit" name="change_email"
                                class="px-8 py-3 bg-primary text-white rounded-full font-bold shadow-lg shadow-primary/20 hover:scale-105 transition-all flex items-center gap-2">
                            <span class="material-icons-round text-sm">save</span>
                            Update Email
                        </button>
                    </form>
                </div>
            </div>

            <!-- Spotify Playlist -->
            <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl overflow-hidden">
                <div class="p-6 border-b border-slate-100 dark:border-white/5">
                    <h2 class="text-xl font-bold flex items-center gap-3">
                        <span class="material-icons-round text-primary">music_note</span>
                        Spotify Playlist
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <p class="text-slate-500">Add your Spotify playlist link so it appears in your account after login.</p>
                    <form method="POST" action="" class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold mb-2">Playlist Link</label>
                            <input
                                type="url"
                                name="spotify_playlist_url"
                                placeholder="https://open.spotify.com/playlist/..."
                                value="<?php echo htmlspecialchars($user['spotify_playlist_url'] ?? ''); ?>"
                                class="w-full px-4 py-3 bg-slate-100 dark:bg-white/5 border-none rounded-2xl focus:ring-2 focus:ring-primary"
                            >
                            <p class="text-xs text-slate-500 mt-1">Supported: Spotify playlist URL or spotify:playlist:ID</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button
                                type="submit"
                                name="save_spotify_playlist"
                                class="px-8 py-3 bg-primary text-white rounded-full font-bold shadow-lg shadow-primary/20 hover:scale-105 transition-all flex items-center gap-2"
                            >
                                <span class="material-icons-round text-sm">save</span>
                                Save Playlist
                            </button>
                            <button
                                type="submit"
                                name="remove_spotify_playlist"
                                class="px-8 py-3 bg-slate-200 text-slate-700 rounded-full font-bold hover:scale-105 transition-all flex items-center gap-2"
                            >
                                <span class="material-icons-round text-sm">delete</span>
                                Remove Playlist
                            </button>
                        </div>
                    </form>
                    <?php if ($spotifyEmbedUrl): ?>
                        <div class="rounded-2xl overflow-hidden border border-primary/10 shadow-sm">
                            <iframe
                                src="<?php echo htmlspecialchars($spotifyEmbedUrl); ?>"
                                width="100%"
                                height="152"
                                frameborder="0"
                                allowfullscreen=""
                                allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                                loading="lazy"
                            ></iframe>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Delete Account -->
            <div class="bg-red-50/50 dark:bg-red-900/10 backdrop-blur-xl rounded-3xl border-2 border-red-500/50 shadow-xl overflow-hidden">
                <div class="p-6 bg-red-500 text-white">
                    <h2 class="text-xl font-bold flex items-center gap-3">
                        <span class="material-icons-round">warning</span>
                        Delete Account
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-red-800 dark:text-red-200 mb-6"><strong>Warning:</strong> This action cannot be undone. All your data will be permanently deleted.</p>
                    <button type="button" onclick="document.getElementById('deleteModal').classList.remove('hidden')"
                            class="px-8 py-3 bg-red-500 text-white rounded-full font-bold shadow-lg hover:scale-105 transition-all flex items-center gap-2">
                        <span class="material-icons-round text-sm">delete_forever</span>
                        Delete My Account
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Account Info -->
            <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-primary/10 to-primary/5 border-b border-primary/10">
                    <h3 class="font-bold flex items-center gap-2">
                        <span class="material-icons-round text-primary">info</span>
                        Our Journey Info
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Username</p>
                        <p class="font-bold"><?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                    <div class="border-t border-slate-100 dark:border-white/5 pt-4">
                        <p class="text-xs text-slate-500 mb-1">Email Address</p>
                        <p class="font-bold"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="border-t border-slate-100 dark:border-white/5 pt-4">
                        <p class="text-xs text-slate-500 mb-1">Journey Began</p>
                        <p class="font-bold"><?php echo formatDate($user['created_at']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl overflow-hidden">
                <div class="p-6 border-b border-slate-100 dark:border-white/5">
                    <h3 class="font-bold flex items-center gap-2">
                        <span class="material-icons-round text-primary">link</span>
                        Quick Links
                    </h3>
                </div>
                <div class="p-6 space-y-2">
                    <a href="profile.php" class="block px-4 py-3 bg-slate-100 dark:bg-white/5 rounded-2xl hover:bg-primary/10 transition-all flex items-center gap-3">
                        <span class="material-icons-round text-sm">person</span>
                        Edit Profile
                    </a>
                    <a href="dashboard.php" class="block px-4 py-3 bg-slate-100 dark:bg-white/5 rounded-2xl hover:bg-primary/10 transition-all flex items-center gap-3">
                        <span class="material-icons-round text-sm">home</span>
                        Dashboard
                    </a>
                    <a href="privacy.php" class="block px-4 py-3 bg-slate-100 dark:bg-white/5 rounded-2xl hover:bg-primary/10 transition-all flex items-center gap-3">
                        <span class="material-icons-round text-sm">shield</span>
                        Privacy Policy
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">
        <div class="p-6 bg-red-500 text-white">
            <h3 class="text-xl font-bold flex items-center gap-3">
                <span class="material-icons-round">warning</span>
                Confirm Account Deletion
            </h3>
        </div>
        <form method="POST" action="">
            <div class="p-6 space-y-6">
                <p class="text-red-600 dark:text-red-400"><strong>This action is permanent and cannot be undone!</strong></p>
                <p class="text-slate-600 dark:text-slate-300">All your data including photos, articles, and messages will be permanently deleted.</p>
                
                <div>
                    <label class="block text-sm font-bold mb-2">Enter your password</label>
                    <input type="password" name="delete_password" required
                           class="w-full px-4 py-3 bg-slate-100 dark:bg-white/5 border-none rounded-2xl focus:ring-2 focus:ring-red-500">
                </div>
                
                <div>
                    <label class="block text-sm font-bold mb-2">Type <strong>DELETE</strong> to confirm</label>
                    <input type="text" name="delete_confirmation" required
                           class="w-full px-4 py-3 bg-slate-100 dark:bg-white/5 border-none rounded-2xl focus:ring-2 focus:ring-red-500">
                </div>
            </div>
            <div class="p-6 bg-slate-50 dark:bg-slate-900/50 flex gap-3">
                <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden')"
                        class="flex-1 px-6 py-3 bg-slate-200 dark:bg-slate-700 rounded-full font-bold hover:scale-105 transition-all">
                    Cancel
                </button>
                <button type="submit" name="delete_account"
                        class="flex-1 px-6 py-3 bg-red-500 text-white rounded-full font-bold hover:scale-105 transition-all flex items-center justify-center gap-2">
                    <span class="material-icons-round text-sm">delete_forever</span>
                    Delete Account
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
