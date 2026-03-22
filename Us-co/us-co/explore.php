<?php
require_once 'includes/functions.php';

// Redirect if not logged in (before any HTML output)
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = "Find Connections";
require_once 'includes/header.php';

$query = $_GET['q'] ?? '';
$conn = getDBConnection();
$userId = $_SESSION['user_id'];

$results = [];
if (!empty($query)) {
    $searchTerm = "%$query%";
    $stmt = $conn->prepare("
        SELECT id, username, profile_pic, bio, location,
               (SELECT status FROM friend_requests WHERE 
                (sender_id = ? AND receiver_id = users.id) OR (sender_id = users.id AND receiver_id = ?) 
                LIMIT 1) as request_status,
               (SELECT 1 FROM friends WHERE 
                (user_id1 = ? AND user_id2 = users.id) OR (user_id1 = users.id AND user_id2 = ?)) as is_friend
        FROM users 
        WHERE (username LIKE ? OR bio LIKE ? OR location LIKE ?) AND id != ?
    ");
    $stmt->execute([$userId, $userId, $userId, $userId, $searchTerm, $searchTerm, $searchTerm, $userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Trending or Suggested users
    $stmt = $conn->prepare("SELECT id, username, profile_pic, bio, location FROM users WHERE id != ? LIMIT 12");
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="w-full max-w-none px-3 md:px-5 py-12">
    <!-- Header Section -->
    <div class="mb-12">
        <h1 class="text-5xl md:text-6xl font-display text-primary mb-2" style="font-family: 'Great Vibes', cursive;">
            Expand Our Circle
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-lg">Discover and connect with amazing souls</p>
    </div>
    
    <!-- Search Bar -->
    <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl p-10 mb-12">
        <form action="explore.php" method="GET">
            <div class="relative group">
                <span class="material-icons-round absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors text-2xl">
                    search
                </span>
                <input type="text" 
                       name="q" 
                       placeholder="Search for friends to invite..." 
                       value="<?php echo htmlspecialchars($query); ?>"
                       class="w-full pl-16 pr-40 py-5 bg-slate-100 dark:bg-white/5 border-none rounded-full focus:ring-2 focus:ring-primary text-lg transition-all">
                <button type="submit" 
                        class="absolute right-2 top-1/2 -translate-y-1/2 px-8 py-3 bg-primary text-white rounded-full font-bold shadow-lg shadow-primary/20 hover:scale-105 transition-all">
                    Search
                </button>
            </div>
        </form>
    </div>

    <!-- Results Header -->
    <div class="mb-6">
        <?php if (!empty($query)): ?>
            <h2 class="text-2xl font-bold flex items-center gap-3">
                <span class="material-icons-round text-primary">search</span>
                Search results for "<?php echo htmlspecialchars($query); ?>"
            </h2>
        <?php else: ?>
            <h2 class="text-2xl font-bold flex items-center gap-3">
                <span class="material-icons-round text-primary">auto_awesome</span>
                Suggested for you
            </h2>
        <?php endif; ?>
    </div>

    <!-- User Cards Grid -->
    <div class="explore-results-grid grid gap-8 xl:gap-10">
        <?php foreach ($results as $user): ?>
            <div class="w-full bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-lg group hover:shadow-2xl hover:scale-[1.01] transition-all">
                <div class="p-7 xl:p-9">
                    <div class="flex items-start gap-5 mb-6">
                        <div class="relative">
                            <img src="<?php echo getProfilePic($user['profile_pic']); ?>" 
                                 class="w-24 h-24 xl:w-28 xl:h-28 rounded-full border-4 border-white dark:border-slate-800 object-cover shadow-lg group-hover:scale-110 transition-transform">
                            <div class="absolute -bottom-1 -right-1 w-7 h-7 bg-green-500 border-4 border-white dark:border-slate-800 rounded-full"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-xl mb-2 truncate">
                                <a href="profile.php?id=<?php echo $user['id']; ?>" 
                                   class="text-slate-800 dark:text-white hover:text-primary transition-colors">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </a>
                            </h3>
                            <div class="flex items-center gap-2 text-base text-slate-500 truncate">
                                <span class="material-icons-round text-sm">place</span>
                                <?php echo htmlspecialchars($user['location'] ?? 'Community Member'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($user['bio'])): ?>
                        <p class="text-base text-slate-600 dark:text-slate-300 mb-6 line-clamp-2 leading-relaxed">
                            <?php echo htmlspecialchars($user['bio']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="pt-6 border-t border-slate-100 dark:border-white/5">
                        <?php if (isset($user['is_friend']) && $user['is_friend']): ?>
                            <button class="w-full py-4 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 rounded-full font-bold text-base flex items-center justify-center gap-2 cursor-not-allowed">
                                <span class="material-icons-round">check_circle</span>
                                Friends
                            </button>
                        <?php elseif (isset($user['request_status']) && $user['request_status'] === 'pending'): ?>
                            <button class="w-full py-4 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400 rounded-full font-bold text-base flex items-center justify-center gap-2 cursor-not-allowed">
                                <span class="material-icons-round">schedule</span>
                                Request Sent
                            </button>
                        <?php else: ?>
                            <button onclick="sendRequest(<?php echo $user['id']; ?>, this)" 
                                    class="w-full py-4 bg-primary text-white rounded-full font-bold text-base shadow-lg shadow-primary/20 hover:scale-105 transition-all flex items-center justify-center gap-2">
                                <span class="material-icons-round">person_add</span>
                                Connect
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($results) && !empty($query)): ?>
            <div class="col-span-full">
                <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl p-12 text-center">
                    <span class="material-icons-round text-6xl text-slate-200 dark:text-slate-700 mb-4">search_off</span>
                    <h3 class="text-xl font-bold mb-2">No users found</h3>
                    <p class="text-slate-500">Try searching with different keywords</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.explore-results-grid {
    grid-template-columns: repeat(auto-fit, minmax(520px, 1fr));
}

@media (max-width: 767.98px) {
    .explore-results-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function sendRequest(receiverId, btn) {
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
            btn.innerHTML = '<span class="material-icons-round text-sm">person_add</span> Connect';
        }
    })
    .catch(err => {
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<span class="material-icons-round text-sm">person_add</span> Connect';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
