<?php
require_once 'includes/functions.php';

// Redirect if not logged in (before any HTML output)
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = "Whispers";
require_once 'includes/header.php';

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

// Mark all as read when visiting this page
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([$userId]);

// Fetch notifications
$stmt = $conn->prepare("
    SELECT n.*, u.username, u.profile_pic 
    FROM notifications n
    JOIN users u ON n.sender_id = u.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getNotificationMessage($type) {
    switch ($type) {
        case 'friend_request': return 'asked to connect with our world.';
        case 'friend_accept': return 'connected with our world.';
        case 'like': return 'felt an echo on your memory.';
        case 'comment': return 'shared a thought on your memory.';
        case 'message': return 'sent you a private letter.';
        default: return 'sent you a whisper.';
    }
}

function getNotificationIcon($type) {
    switch ($type) {
        case 'friend_request': return 'link';
        case 'friend_accept': return 'favorite';
        case 'like': return 'favorite';
        case 'comment': return 'chat_bubble';
        case 'message': return 'mail';
        default: return 'notifications';
    }
}

function getNotificationColor($type) {
    switch ($type) {
        case 'friend_request': return 'text-primary';
        case 'friend_accept': return 'text-red-500';
        case 'like': return 'text-primary';
        case 'comment': return 'text-green-500';
        case 'message': return 'text-blue-500';
        default: return 'text-slate-400';
    }
}
?>

<div class="max-w-4xl mx-auto px-4 md:px-8 py-8">
    <!-- Header -->
    <div class="mb-12">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-5xl md:text-6xl font-display text-primary mb-2" style="font-family: 'Great Vibes', cursive;">
                    Whispers
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-lg">Echoes from your connections</p>
            </div>
            <div class="relative">
                <button class="p-3 bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-full border border-primary/10 shadow-lg hover:scale-105 transition-all"
                        onclick="document.getElementById('settingsMenu').classList.toggle('hidden')">
                    <span class="material-icons-round text-slate-600 dark:text-slate-300">more_vert</span>
                </button>
                <div id="settingsMenu" class="hidden absolute right-0 mt-2 w-56 bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl border border-primary/10 shadow-2xl overflow-hidden z-10">
                    <a href="#" class="block px-4 py-3 hover:bg-primary/10 transition-colors flex items-center gap-3">
                        <span class="material-icons-round text-sm">done_all</span>
                        Mark all as read
                    </a>
                    <a href="settings.php" class="block px-4 py-3 hover:bg-primary/10 transition-colors flex items-center gap-3">
                        <span class="material-icons-round text-sm">settings</span>
                        Notification Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl overflow-hidden">
        <?php if (empty($notifications)): ?>
            <div class="p-12 text-center">
                <span class="material-icons-round text-6xl text-slate-200 dark:text-slate-700 mb-4">air</span>
                <h3 class="text-xl font-bold mb-2">No whispers yet</h3>
                <p class="text-slate-500">When someone interacts with you, you'll see it here</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-100 dark:divide-white/5">
                <?php foreach ($notifications as $n): ?>
                    <a href="<?php echo ($n['type'] == 'friend_request') ? 'friends.php' : 'feed.php#post-'.$n['entity_id']; ?>" 
                       class="flex items-start gap-4 p-6 hover:bg-primary/5 transition-all group <?php echo !$n['is_read'] ? 'bg-primary/5' : ''; ?>">
                        <div class="relative flex-shrink-0">
                            <img src="<?php echo getProfilePic($n['profile_pic']); ?>" 
                                 class="w-14 h-14 rounded-full border-2 border-white dark:border-slate-800 object-cover shadow-md group-hover:scale-110 transition-transform">
                            <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-white dark:bg-slate-800 rounded-full flex items-center justify-center shadow-md">
                                <span class="material-icons-round text-xs <?php echo getNotificationColor($n['type']); ?>">
                                    <?php echo getNotificationIcon($n['type']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-slate-800 dark:text-white mb-1">
                                <span class="font-bold"><?php echo htmlspecialchars($n['username']); ?></span>
                                <span class="text-slate-600 dark:text-slate-300"> <?php echo getNotificationMessage($n['type']); ?></span>
                            </p>
                            <div class="flex items-center gap-2 text-xs text-primary font-bold uppercase tracking-wider">
                                <span class="material-icons-round text-xs">schedule</span>
                                <?php echo getTimeAgo($n['created_at']); ?>
                            </div>
                        </div>
                        <?php if (!$n['is_read']): ?>
                            <div class="w-2 h-2 bg-primary rounded-full flex-shrink-0 mt-2"></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('settingsMenu');
    const button = event.target.closest('button');
    if (!button && !menu.contains(event.target)) {
        menu.classList.add('hidden');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
