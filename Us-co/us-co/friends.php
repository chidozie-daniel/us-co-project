<?php
require_once 'includes/functions.php';

// Redirect if not logged in (before any HTML output)
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = "Our Connections";
require_once 'includes/header.php';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get friend requests
$stmt = $conn->prepare("
    SELECT fr.*, u.username, u.profile_pic 
    FROM friend_requests fr 
    JOIN users u ON fr.sender_id = u.id 
    WHERE fr.receiver_id = ? AND fr.status = 'pending'
");
$stmt->execute([$userId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get friends list
$stmt = $conn->prepare("
    SELECT u.id, u.username, u.profile_pic, u.location
    FROM users u
    JOIN friends f ON (u.id = f.user_id1 OR u.id = f.user_id2)
    WHERE (f.user_id1 = ? OR f.user_id2 = ?) AND u.id != ?
");
$stmt->execute([$userId, $userId, $userId]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get friend suggestions (users who are not friends and no pending requests)
$stmt = $conn->prepare("
    SELECT id, username, profile_pic, location 
    FROM users 
    WHERE id != ? 
    AND id NOT IN (
        SELECT CASE WHEN user_id1 = ? THEN user_id2 ELSE user_id1 END 
        FROM friends WHERE user_id1 = ? OR user_id2 = ?
    )
    AND id NOT IN (
        SELECT receiver_id FROM friend_requests WHERE sender_id = ?
    )
    AND id NOT IN (
        SELECT sender_id FROM friend_requests WHERE receiver_id = ?
    )
    LIMIT 6
");
$stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
$suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
    <!-- Header -->
    <div class="mb-12">
        <h1 class="text-5xl md:text-6xl font-display text-primary mb-2" style="font-family: 'Great Vibes', cursive;">
            Our Connections
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-lg">The hearts we've linked along the way</p>
    </div>

    <!-- Friend Requests -->
    <?php if (!empty($requests)): ?>
    <div class="mb-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold flex items-center gap-3">
                <span class="material-icons-round text-primary">person_add</span>
                Connection Requests
            </h2>
            <span class="px-4 py-1.5 bg-primary/10 text-primary rounded-full text-sm font-bold">
                <?php echo count($requests); ?>
            </span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-6">
            <?php foreach ($requests as $req): ?>
                <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-lg overflow-hidden group hover:shadow-2xl transition-all">
                    <div class="p-6 text-center">
                        <img src="<?php echo getProfilePic($req['profile_pic']); ?>" 
                             class="w-24 h-24 rounded-full border-4 border-white dark:border-slate-800 object-cover shadow-lg mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <h3 class="font-bold text-lg mb-4"><?php echo htmlspecialchars($req['username']); ?></h3>
                        <div class="flex gap-2">
                            <button onclick="respondRequest(<?php echo $req['id']; ?>, 'accepted')" 
                                    class="flex-1 py-3 bg-primary text-white rounded-full font-bold text-sm shadow-lg shadow-primary/20 hover:scale-105 transition-all">
                                Confirm
                            </button>
                            <button onclick="respondRequest(<?php echo $req['id']; ?>, 'rejected')" 
                                    class="flex-1 py-3 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 rounded-full font-bold text-sm hover:bg-slate-200 dark:hover:bg-white/10 transition-all">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Friends List -->
    <div class="mb-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold flex items-center gap-3">
                <span class="material-icons-round text-primary">favorite</span>
                All Connections
            </h2>
            <span class="text-slate-500 text-sm"><?php echo count($friends); ?> hearts linked</span>
        </div>
        <?php if (empty($friends)): ?>
            <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl p-12 text-center">
                <span class="material-icons-round text-6xl text-slate-200 dark:text-slate-700 mb-4">people_outline</span>
                <h3 class="text-xl font-bold mb-2">No connections yet</h3>
                <p class="text-slate-500 mb-6">Discover new people and start building your network!</p>
                <a href="explore.php" class="inline-flex items-center gap-2 px-8 py-3 bg-primary text-white rounded-full font-bold shadow-lg shadow-primary/20 hover:scale-105 transition-all">
                    <span class="material-icons-round">explore</span>
                    Explore Hearts
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-6">
                <?php foreach ($friends as $friend): ?>
                    <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-lg overflow-hidden group hover:shadow-2xl hover:scale-[1.02] transition-all">
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <img src="<?php echo getProfilePic($friend['profile_pic']); ?>" 
                                     class="w-16 h-16 rounded-full border-2 border-white dark:border-slate-800 object-cover shadow-md group-hover:scale-110 transition-transform">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-lg mb-1 truncate">
                                        <a href="profile.php?id=<?php echo $friend['id']; ?>" 
                                           class="text-slate-800 dark:text-white hover:text-primary transition-colors">
                                            <?php echo htmlspecialchars($friend['username']); ?>
                                        </a>
                                    </h3>
                                    <div class="flex items-center gap-2 text-sm text-slate-500">
                                        <span class="material-icons-round text-xs">place</span>
                                        <?php echo htmlspecialchars($friend['location'] ?? 'Everest Network'); ?>
                                    </div>
                                </div>
                                <div class="relative">
                                    <button class="p-2 hover:bg-slate-100 dark:hover:bg-white/5 rounded-full transition-colors"
                                            onclick="document.getElementById('menu-<?php echo $friend['id']; ?>').classList.toggle('hidden')">
                                        <span class="material-icons-round text-slate-400">more_vert</span>
                                    </button>
                                    <div id="menu-<?php echo $friend['id']; ?>" class="hidden absolute right-0 mt-2 w-48 bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl border border-primary/10 shadow-2xl overflow-hidden z-10">
                                        <a href="profile.php?id=<?php echo $friend['id']; ?>" class="block px-4 py-3 hover:bg-primary/10 transition-colors flex items-center gap-3">
                                            <span class="material-icons-round text-sm">person</span>
                                            View Profile
                                        </a>
                                        <button onclick="removeFriend(<?php echo $friend['id']; ?>)" class="w-full text-left px-4 py-3 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 transition-colors flex items-center gap-3">
                                            <span class="material-icons-round text-sm">person_remove</span>
                                            Remove Connection
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Suggestions -->
    <?php if (!empty($suggestions)): ?>
    <div>
        <div class="mb-6">
            <h2 class="text-2xl font-bold flex items-center gap-3">
                <span class="material-icons-round text-primary">auto_awesome</span>
                People We Might Know
            </h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-6">
            <?php foreach ($suggestions as $sug): ?>
                <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-lg overflow-hidden group hover:shadow-2xl hover:scale-[1.02] transition-all">
                    <div class="p-6 text-center">
                        <img src="<?php echo getProfilePic($sug['profile_pic']); ?>" 
                             class="w-24 h-24 rounded-full border-4 border-white dark:border-slate-800 object-cover shadow-lg mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <h3 class="font-bold text-lg mb-1"><?php echo htmlspecialchars($sug['username']); ?></h3>
                        <p class="text-sm text-slate-500 mb-4"><?php echo htmlspecialchars($sug['location'] ?? 'Suggested User'); ?></p>
                        <button onclick="sendRequest(<?php echo $sug['id']; ?>, this)" 
                                class="w-full py-3 bg-primary text-white rounded-full font-bold text-sm shadow-lg shadow-primary/20 hover:scale-105 transition-all flex items-center justify-center gap-2">
                            <span class="material-icons-round text-sm">person_add</span>
                            Connect
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function sendRequest(receiverId, btn) {
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-icons-round text-sm animate-spin">sync</span> Sending...';

    fetch('ajax/send_friend_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `receiver_id=${receiverId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.className = 'w-full py-3 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400 rounded-full font-bold text-sm flex items-center justify-center gap-2 cursor-not-allowed';
            btn.innerHTML = '<span class="material-icons-round text-sm">schedule</span> Request Sent';
        } else {
            alert(data.error || 'Failed to send request');
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    });
}

function respondRequest(requestId, status) {
    fetch('ajax/accept_friend_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `request_id=${requestId}&status=${status}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to respond to request');
        }
    });
}

function removeFriend(friendId) {
    if (confirm('Are you sure you want to unfriend this person?')) {
        fetch('ajax/remove_friend.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `friend_id=${friendId}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to remove friend');
            }
        });
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('button')) {
        document.querySelectorAll('[id^="menu-"]').forEach(menu => menu.classList.add('hidden'));
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
