<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/news_widget.php';

// Redirect if not logged in (before any HTML output)
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = "Dashboard";

$user = getCurrentUser();
$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get friend count
$stmt = $conn->prepare("SELECT COUNT(*) FROM friends WHERE user_id1 = ? OR user_id2 = ?");
$stmt->execute([$userId, $userId]);
$friendCount = $stmt->fetchColumn();

// Get recent feed
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.profile_pic,
           (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ? 
    OR p.user_id IN (SELECT CASE WHEN user_id1 = ? THEN user_id2 ELSE user_id1 END FROM friends WHERE user_id1 = ? OR user_id2 = ?)
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->execute([$userId, $userId, $userId, $userId]);
$recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Now include header after all redirects are handled
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<header class="mb-16">
    <div class="relative overflow-hidden bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl p-10 lg:p-16 flex flex-col md:flex-row items-center gap-12 shadow-xl border border-primary/10">
        <div class="absolute top-0 right-0 w-80 h-80 bg-primary/5 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-40 h-40 bg-primary/5 rounded-full -ml-16 -mb-16"></div>
        
        <div class="relative flex -space-x-6">
            <img class="w-28 h-28 lg:w-40 lg:h-40 rounded-full border-4 border-white dark:border-slate-800 object-cover shadow-2xl" src="<?php echo getProfilePic($user); ?>">
            <div class="absolute -bottom-3 -right-3 bg-primary text-white p-3 rounded-full shadow-lg">
                <span class="material-icons-round text-lg">favorite</span>
            </div>
        </div>
        
        <div class="flex-1 text-center md:text-left">
            <h1 class="text-4xl lg:text-5xl font-bold mb-4">Welcome back, <?php echo htmlspecialchars($user['username']); ?></h1>
            <p class="text-slate-500 dark:text-slate-400 text-xl mb-10">"The best thing to hold onto in life is each other."</p>
            <div class="flex flex-wrap gap-6 justify-center md:justify-start">
                <div class="bg-primary/10 border border-primary/20 px-8 py-4 rounded-full flex items-center gap-4 shadow-sm">
                    <span class="material-icons-round text-primary text-2xl">event_repeat</span>
                    <span class="font-bold text-primary text-lg"><?php echo $friendCount; ?> Connections</span>
                </div>
                <div class="bg-white/50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-8 py-4 rounded-full flex items-center gap-4 shadow-sm">
                    <span class="material-icons-round text-primary text-2xl">auto_awesome</span>
                    <span class="font-bold text-lg"><?php echo count($recentPosts); ?> Echoes Shared</span>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Dashboard Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <?php $spotifyEmbedUrl = getSpotifyEmbedUrl($user['spotify_playlist_url'] ?? ''); ?>
    
    <!-- Shared Calendar Widget (Mocking Events) -->
    <section class="lg:col-span-1 bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl p-10 shadow-xl border border-primary/10 min-h-[500px] flex flex-col">
        <div class="flex items-center justify-between mb-10">
            <h2 class="text-2xl font-bold flex items-center gap-3">
                <span class="material-icons-round text-primary text-3xl">calendar_month</span>
                Upcoming Dates
            </h2>
            <button class="p-3 hover:bg-primary/10 rounded-full text-primary transition-all hover:scale-110">
                <span class="material-icons-round">add</span>
            </button>
        </div>
        <div class="space-y-6 flex-1">
            <div class="flex items-start gap-6 p-8 bg-primary/5 rounded-2xl border-l-4 border-primary shadow-sm hover:shadow-md transition-all">
                <div class="text-center min-w-[5rem]">
                    <p class="text-xs uppercase font-bold text-primary tracking-wider"><?php echo date('M', strtotime('+1 day')); ?></p>
                    <p class="text-4xl font-bold mt-2"><?php echo date('d', strtotime('+1 day')); ?></p>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-xl mb-2">Moments Together</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Plan a special memory</p>
                </div>
            </div>
            <!-- More dynamic items could go here -->
        </div>
    </section>

    <!-- Love Notes Board -->
    <section class="lg:col-span-1 bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl p-10 shadow-xl border border-primary/10 flex flex-col min-h-[500px]">
        <div class="flex items-center justify-between mb-10">
            <h2 class="text-2xl font-bold flex items-center gap-3">
                <span class="material-icons-round text-primary text-3xl">forum</span>
                Love Notes
            </h2>
            <a href="messages.php" class="text-xs text-primary bg-primary/10 px-5 py-2.5 rounded-full font-bold hover:bg-primary hover:text-white transition-all">VIEW ALL</a>
        </div>
        
        <div class="flex-1 space-y-6 overflow-y-auto mb-8 pr-2 custom-scrollbar">
            <?php
            // Fetch some recent messages
            $msgStmt = $conn->prepare("
                SELECT m.*, u.username, u.profile_pic 
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                WHERE m.receiver_id = ? 
                ORDER BY m.sent_at DESC 
                LIMIT 5
            ");
            $msgStmt->execute([$userId]);
            $notes = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($notes)): ?>
                <div class="text-center py-16 text-slate-400">
                    <span class="material-icons-round text-6xl mb-4">history_edu</span>
                    <p class="text-base italic leading-relaxed">No love notes yet.<br>Start the conversation!</p>
                </div>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <div class="bg-blush/30 dark:bg-primary/10 p-6 rounded-2xl rounded-tl-none border-l-4 border-primary shadow-sm hover:shadow-md transition-all">
                        <p class="text-base mb-4 italic leading-relaxed">"<?php echo htmlspecialchars(substr($note['message'], 0, 100)) . (strlen($note['message']) > 100 ? '...' : ''); ?>"</p>
                        <p class="text-sm text-right text-slate-400">— From <?php echo $note['username']; ?>, <?php echo getTimeAgo($note['sent_at']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <a href="messages.php" class="relative mt-auto block">
            <div class="w-full pl-8 pr-16 py-5 rounded-full border-none bg-slate-100 dark:bg-white/5 text-slate-400 text-base cursor-pointer hover:bg-slate-200 dark:hover:bg-white/10 transition-all">
                Write a note...
            </div>
            <span class="absolute right-2 top-2 w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white shadow-lg">
                <span class="material-icons-round">send</span>
            </span>
        </a>
    </section>

    <!-- Shared Lists Widget -->
    <section class="lg:col-span-1 bg-white dark:bg-white/5 rounded-xl p-6 shadow-sm border border-primary/5">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold flex items-center gap-2">
                <span class="material-icons-round text-primary">format_list_bulleted</span>
                To-Do Together
            </h2>
        </div>
        <div class="space-y-3">
            <p class="text-sm text-slate-500 italic px-3">Our shared goals and tasks will appear here.</p>
            <button class="w-full mt-4 flex items-center justify-center gap-2 py-2 border-2 border-dashed border-slate-200 dark:border-white/10 rounded-lg text-slate-400 hover:border-primary/50 hover:text-primary transition-all text-sm font-medium">
                <span class="material-icons-round text-sm">add</span>
                Add Item
            </button>
        </div>
    </section>

    <!-- Spotify Playlist -->
    <section class="lg:col-span-1 bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl p-6 shadow-sm border border-primary/10">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold flex items-center gap-2">
                <span class="material-icons-round text-primary">music_note</span>
                Spotify Playlist
            </h2>
            <a href="settings.php" class="text-xs text-primary font-bold uppercase tracking-wider">Manage</a>
        </div>
        <?php if ($spotifyEmbedUrl): ?>
            <div class="rounded-2xl overflow-hidden border border-primary/10">
                <iframe
                    src="<?php echo htmlspecialchars($spotifyEmbedUrl); ?>"
                    width="100%"
                    height="352"
                    frameborder="0"
                    allowfullscreen=""
                    allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                    loading="lazy"
                ></iframe>
            </div>
        <?php else: ?>
            <div class="rounded-2xl border border-dashed border-primary/20 p-6 text-center">
                <p class="text-slate-500 text-sm mb-4">No playlist linked yet.</p>
                <a href="settings.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary text-white font-bold text-sm">
                    <span class="material-icons-round text-base">add</span>
                    Link Spotify Playlist
                </a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Memory Lane Carousel (Dynamic Recent Posts) -->
    <section class="lg:col-span-3 bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl p-10 shadow-xl border border-primary/10">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h2 class="text-3xl font-bold flex items-center gap-3">
                    <span class="material-icons-round text-primary text-4xl">auto_awesome</span>
                    Memory Lane
                </h2>
                <p class="text-slate-500 dark:text-slate-400 mt-2 text-lg">Reflecting on our journey together</p>
            </div>
            <a href="gallery.php" class="text-primary font-bold text-lg flex items-center gap-2 hover:underline">
                View all <span class="material-icons-round">chevron_right</span>
            </a>
        </div>
        <div class="flex gap-8 overflow-x-auto pb-6 custom-scrollbar">
            <?php if (empty($recentPosts)): ?>
                 <div class="w-full py-16 text-center text-slate-400">
                    <span class="material-icons-round text-6xl mb-4">photo_library</span>
                    <p class="text-lg">No memories shared yet. Start your journey!</p>
                 </div>
            <?php else: ?>
                <?php foreach ($recentPosts as $post): ?>
                    <?php if ($post['media_path']): ?>
                    <div class="min-w-[320px] group cursor-pointer">
                        <div class="relative h-64 mb-4 overflow-hidden rounded-2xl bg-slate-100 shadow-lg">
                            <?php if ($post['media_type'] === 'video'): ?>
                                <video src="<?php echo $post['media_path']; ?>" class="w-full h-full object-cover"></video>
                            <?php else: ?>
                                <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" src="<?php echo $post['media_path']; ?>">
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex items-end p-5">
                                <span class="text-white text-sm font-bold"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                            </div>
                        </div>
                        <h3 class="font-bold text-lg mb-1 truncate"><?php echo htmlspecialchars($post['username']); ?>'s Moment</h3>
                        <p class="text-base text-slate-500 truncate"><?php echo htmlspecialchars(substr($post['content'], 0, 50)); ?></p>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

</div>

<style>
.custom-scrollbar::-webkit-scrollbar { height: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(238, 43, 140, 0.1); border-radius: 10px; }
</style>

<?php require_once 'includes/footer.php'; ?>
