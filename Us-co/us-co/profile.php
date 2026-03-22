<?php
require_once 'includes/functions.php';

$profileId = $_GET['id'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

if (!$userId && !$profileId) {
    redirect('login.php');
}

$page_title = "Profile";
require_once 'includes/header.php';
require_once 'includes/auth.php';

$conn = getDBConnection();

// Determine whose profile we're looking at
$targetUserId = $profileId ? $profileId : ($userId ?? null);
$isOwnProfile = ($userId && $targetUserId == $userId);

// Fetch target user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$targetUserId]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$targetUser) {
    echo '<div class="container py-5 text-center"><h3>User not found.</h3><a href="feed.php">Back to home</a></div>';
    include 'includes/footer.php';
    exit();
}

// Check friendship status
$friendStatus = 'none';
if (!$isOwnProfile && $userId) {
    $stmt = $conn->prepare("SELECT * FROM friends WHERE (user_id1 = ? AND user_id2 = ?) OR (user_id1 = ? AND user_id2 = ?)");
    $stmt->execute([$userId, $targetUserId, $targetUserId, $userId]);
    if ($stmt->rowCount() > 0) {
        $friendStatus = 'friends';
    } else {
        $stmt = $conn->prepare("SELECT * FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->execute([$userId, $targetUserId]);
        if ($stmt->rowCount() > 0) {
            $friendStatus = 'sent';
        } else {
            $stmt = $conn->prepare("SELECT * FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
            $stmt->execute([$targetUserId, $userId]);
            if ($stmt->rowCount() > 0) {
                $friendStatus = 'received';
            }
        }
    }
}

// Handle profile update
$success = '';
$error = '';
if ($isOwnProfile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'bio' => $_POST['bio'] ?? '',
        'location' => $_POST['location'] ?? '',
        'occupation' => $_POST['occupation'] ?? '',
        'education' => $_POST['education'] ?? '',
        'hobbies' => $_POST['hobbies'] ?? '',
        'relationship_status' => $_POST['relationship_status'] ?? '',
        'anniversary_date' => $_POST['anniversary_date'] ?? ''
    ];
    
    if (updateProfile($_SESSION['user_id'], $data)) {
        $success = 'Profile updated successfully!';
        // Refresh data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$targetUserId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = 'Failed to update profile.';
    }
}
?>

<div class="relative mb-12">
    <!-- Cover Photo -->
    <div class="h-64 md:h-96 w-full relative overflow-hidden rounded-b-[3rem] shadow-2xl">
        <img src="<?php echo getCoverPic($targetUser); ?>" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
        
        <?php if ($isOwnProfile): ?>
            <button class="absolute bottom-6 right-6 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white px-6 py-2.5 rounded-full text-sm font-bold flex items-center gap-2 transition-all border border-white/30" data-bs-toggle="modal" data-bs-target="#updateCoverModal">
                <span class="material-icons-round text-lg">photo_camera</span>
                Update Cover
            </button>
        <?php endif; ?>
    </div>

    <!-- Profile Header Content -->
    <div class="profile-shell -mt-24 relative z-10">
        <div class="flex flex-col md:flex-row items-center md:items-end gap-8">
            <!-- Profile Picture -->
            <div class="relative group">
                <div class="w-44 h-44 rounded-full border-[6px] border-white shadow-2xl overflow-hidden bg-white">
                    <img src="<?php echo getProfilePic($targetUser); ?>" class="w-full h-full object-cover">
                </div>
                <?php if ($isOwnProfile): ?>
                    <button class="absolute bottom-2 right-2 w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center border-4 border-white shadow-lg hover:scale-110 transition-transform" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <span class="material-icons-round text-sm">edit</span>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Profile Identity -->
            <div class="flex-1 text-center md:text-left pb-4">
                <h1 class="text-6xl font-display text-white md:text-slate-800 dark:md:text-white drop-shadow-lg md:drop-shadow-none" style="font-family: 'Great Vibes', cursive;">
                    <?php echo htmlspecialchars($targetUser['username']); ?>
                </h1>
                <p class="text-slate-500 font-medium mt-2 max-w-2xl"><?php echo htmlspecialchars($targetUser['bio'] ?? 'Crafting a beautiful story...'); ?></p>
                
                <div class="flex flex-wrap justify-center md:justify-start gap-6 mt-6">
                    <?php if ($targetUser['location']): ?>
                        <div class="flex items-center gap-2 text-slate-400 font-bold uppercase tracking-widest text-[10px]">
                            <span class="material-icons-round text-primary text-lg">place</span>
                            <?php echo htmlspecialchars($targetUser['location']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($targetUser['occupation']): ?>
                        <div class="flex items-center gap-2 text-slate-400 font-bold uppercase tracking-widest text-[10px]">
                            <span class="material-icons-round text-primary text-lg">work</span>
                            <?php echo htmlspecialchars($targetUser['occupation']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2 text-slate-400 font-bold uppercase tracking-widest text-[10px]">
                        <span class="material-icons-round text-primary text-lg">calendar_today</span>
                        Joined <?php echo date('M Y', strtotime($targetUser['created_at'])); ?>
                    </div>
                </div>
            </div>

            <!-- Profile Actions -->
            <div class="pb-6">
                <?php if ($isOwnProfile): ?>
                    <button class="bg-primary hover:bg-primary-hover text-white px-8 py-3 rounded-full font-bold shadow-xl shadow-primary/20 transition-all flex items-center gap-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <span class="material-icons-round text-lg">settings</span>
                        Edit Profile
                    </button>
                <?php else: ?>
                    <div class="flex gap-4">
                        <?php if ($friendStatus === 'friends'): ?>
                            <div class="relative group">
                                <button class="bg-green-50 text-green-600 px-8 py-3 rounded-full font-bold border border-green-200 flex items-center gap-2">
                                    <span class="material-icons-round text-lg">check_circle</span>
                                    Friends
                                </button>
                            </div>
                            <a href="messages.php?user=<?php echo $targetUserId; ?>" class="bg-primary hover:bg-primary-hover text-white px-8 py-3 rounded-full font-bold shadow-xl shadow-primary/20 transition-all flex items-center gap-2">
                                <span class="material-icons-round text-lg">chat_bubble</span>
                                Message
                            </a>
                        <?php elseif ($friendStatus === 'sent'): ?>
                            <button class="bg-slate-100 text-slate-400 px-8 py-3 rounded-full font-bold cursor-not-allowed">Request Sent</button>
                        <?php elseif ($friendStatus === 'received'): ?>
                            <button onclick="respondRequest(<?php echo $targetUserId; ?>, 'accepted')" class="bg-success text-white px-8 py-3 rounded-full font-bold shadow-lg">Accept Request</button>
                        <?php else: ?>
                            <button onclick="sendRequest(<?php echo $targetUserId; ?>, this)" class="bg-primary text-white px-8 py-3 rounded-full font-bold shadow-xl shadow-primary/20 hover:scale-105 transition-all flex items-center gap-2">
                                <span class="material-icons-round text-lg">person_add</span>
                                Connect
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="profile-shell pb-12">
    <div class="profile-layout grid grid-cols-1 gap-8 xl:gap-10">
        <!-- Sidebar -->
        <div class="space-y-8">
            <!-- About Card -->
            <div class="bg-white/50 dark:bg-background-dark/50 backdrop-blur-xl border border-primary/10 rounded-3xl p-8 shadow-xl">
                <h2 class="text-3xl font-display text-primary mb-6" style="font-family: 'Great Vibes', cursive;">About Soul</h2>
                <div class="space-y-6">
                    <?php if ($targetUser['occupation']): ?>
                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-primary/5 border border-primary/5">
                            <span class="material-icons-round text-primary bg-white dark:bg-black/20 p-2 rounded-xl shadow-sm">work</span>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Profession</span>
                                <span class="text-slate-700 dark:text-slate-200 font-medium"><?php echo htmlspecialchars($targetUser['occupation']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($targetUser['education']): ?>
                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-primary/5 border border-primary/5">
                            <span class="material-icons-round text-primary bg-white dark:bg-black/20 p-2 rounded-xl shadow-sm">school</span>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Education</span>
                                <span class="text-slate-700 dark:text-slate-200 font-medium"><?php echo htmlspecialchars($targetUser['education']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($targetUser['relationship_status']): ?>
                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-primary/5 border border-primary/5">
                            <span class="material-icons-round text-red-400 bg-white dark:bg-black/20 p-2 rounded-xl shadow-sm">favorite</span>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Love Status</span>
                                <span class="text-slate-700 dark:text-slate-200 font-medium"><?php echo htmlspecialchars($targetUser['relationship_status']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($targetUser['hobbies']): ?>
                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-primary/5 border border-primary/5">
                            <span class="material-icons-round text-sage bg-white dark:bg-black/20 p-2 rounded-xl shadow-sm">palette</span>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Fascinations</span>
                                <span class="text-slate-700 dark:text-slate-200 font-medium"><?php echo htmlspecialchars($targetUser['hobbies']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Friends Grid -->
            <div class="bg-white/50 dark:bg-background-dark/50 backdrop-blur-xl border border-primary/10 rounded-3xl p-8 shadow-xl">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-display text-primary" style="font-family: 'Great Vibes', cursive;">Soulmates</h2>
                    <a href="friends.php?id=<?php echo $targetUserId; ?>" class="text-xs font-bold text-primary hover:underline uppercase tracking-widest">View All</a>
                </div>
                <div class="grid grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php
                    $stmt = $conn->prepare("
                        SELECT u.id, u.username, u.profile_pic 
                        FROM users u 
                        JOIN friends f ON (u.id = f.user_id1 OR u.id = f.user_id2)
                        WHERE (f.user_id1 = ? OR f.user_id2 = ?) AND u.id != ?
                        LIMIT 9
                    ");
                    $stmt->execute([$targetUserId, $targetUserId, $targetUserId]);
                    $profileFriends = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($profileFriends): 
                        foreach ($profileFriends as $pf): ?>
                        <a href="profile.php?id=<?php echo $pf['id']; ?>" class="group">
                            <div class="relative aspect-square rounded-2xl overflow-hidden shadow-sm transition-transform group-hover:scale-105 group-hover:shadow-lg">
                                <img src="<?php echo getProfilePic($pf['profile_pic']); ?>" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="text-[8px] text-white font-bold uppercase tracking-tighter px-1 text-center"><?php echo htmlspecialchars($pf['username']); ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach;
                    else: ?>
                        <div class="col-span-3 text-center py-8">
                            <span class="material-icons-round text-slate-200 text-5xl">people_outline</span>
                            <p class="text-slate-400 text-xs mt-2 italic">Awaits new connections</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="space-y-8">
            <!-- Create Post -->
            <?php if ($isOwnProfile): ?>
                <div class="bg-white/50 dark:bg-background-dark/50 backdrop-blur-xl border border-primary/10 rounded-3xl p-6 shadow-xl flex items-center gap-6">
                    <img src="<?php echo getProfilePic($targetUser); ?>" class="w-14 h-14 rounded-full border-2 border-primary/10 object-cover">
                    <button class="flex-1 text-left bg-slate-100 dark:bg-white/5 hover:bg-white dark:hover:bg-white/10 px-8 py-4 rounded-full text-slate-500 font-medium transition-all shadow-inner" data-bs-toggle="modal" data-bs-target="#createPostModal">
                        What's blooming in your heart, <?php echo htmlspecialchars($targetUser['username']); ?>?
                    </button>
                    <button class="w-14 h-14 bg-primary/5 text-primary hover:bg-primary hover:text-white rounded-full flex items-center justify-center transition-all shadow-sm" data-bs-toggle="modal" data-bs-target="#createPostModal">
                        <span class="material-icons-round">image</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Posts List -->
            <div id="user-posts" class="space-y-8">
                <?php
                $stmt = $conn->prepare("
                    SELECT p.*, u.username, u.profile_pic,
                           (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
                           (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked,
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.user_id = ?
                    ORDER BY p.created_at DESC
                ");
                $stmt->execute([$userId, $targetUserId]);
                $userPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($userPosts)): ?>
                    <div class="bg-white/30 dark:bg-black/10 rounded-3xl border-2 border-dashed border-primary/10 p-20 text-center">
                        <span class="material-icons-round text-7xl text-primary/10 mb-6">waves</span>
                        <h3 class="text-2xl font-bold text-slate-400 italic">Silence of the Soul</h3>
                        <p class="text-slate-400 mt-2">No echoes have been whispered here yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($userPosts as $post): ?>
                        <div class="bg-white/80 dark:bg-background-dark/80 backdrop-blur-xl border border-primary/5 rounded-3xl shadow-xl overflow-hidden group">
                            <!-- Post Header -->
                            <div class="p-6 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <img src="uploads/profile_pics/<?php echo $post['profile_pic']; ?>" class="w-12 h-12 rounded-full border-2 border-white object-cover">
                                    <div>
                                        <h4 class="font-bold text-slate-800 dark:text-white"><?php echo htmlspecialchars($post['username']); ?></h4>
                                        <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                            <span class="material-icons-round text-[12px]">schedule</span>
                                            <?php echo getTimeAgo($post['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                                <button class="p-2 text-slate-400 hover:text-primary transition-colors">
                                    <span class="material-icons-round">more_horiz</span>
                                </button>
                            </div>

                            <!-- Post Content -->
                            <div class="px-8 pb-4">
                                <p class="text-slate-600 dark:text-slate-300 leading-relaxed text-lg"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            </div>

                            <!-- Post Media -->
                            <?php if ($post['media_path']): ?>
                                <div class="px-4 pb-4">
                                    <div class="rounded-2xl overflow-hidden bg-slate-100 dark:bg-black/20 border border-primary/5">
                                        <img src="<?php echo $post['media_path']; ?>" class="w-full h-auto max-h-[600px] object-contain mx-auto transition-transform duration-700 group-hover:scale-[1.02]">
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Post Footer -->
                            <div class="p-4 mx-4 mb-4 rounded-2xl bg-primary/[0.02] border border-primary/5 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <button onclick="likePost(<?php echo $post['id']; ?>, this)" 
                                            class="flex items-center gap-2 px-6 py-2 rounded-full transition-all <?php echo $post['user_liked'] ? 'bg-primary text-white shadow-lg' : 'text-slate-500 hover:bg-primary/10'; ?>">
                                        <span class="material-icons-round"><?php echo $post['user_liked'] ? 'favorite' : 'favorite_border'; ?></span>
                                        <span class="font-bold"><?php echo $post['like_count']; ?></span>
                                    </button>
                                    <button class="flex items-center gap-2 px-6 py-2 rounded-full text-slate-500 hover:bg-primary/5 transition-all">
                                        <span class="material-icons-round">chat_bubble_outline</span>
                                        <span class="font-bold"><?php echo $post['comment_count']; ?></span>
                                    </button>
                                </div>
                                <button class="p-2 text-slate-400 hover:text-primary transition-colors">
                                    <span class="material-icons-round">ios_share</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-white/95 dark:bg-background-dark/95 backdrop-blur-2xl border-none rounded-[2.5rem] shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-primary/5 flex justify-between items-center">
                <h2 class="text-4xl font-display text-primary" style="font-family: 'Great Vibes', cursive;">Refine Your Soul</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="p-10">
                <form method="POST" action="" enctype="multipart/form-data" class="space-y-8">
                    <div class="flex flex-col items-center gap-4 mb-8">
                        <div class="relative group">
                            <img src="uploads/profile_pics/<?php echo $targetUser['profile_pic']; ?>" class="w-32 h-32 rounded-full border-4 border-primary/10 shadow-xl object-cover">
                            <label class="absolute bottom-0 right-0 w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center border-4 border-white cursor-pointer hover:scale-110 transition-transform">
                                <span class="material-icons-round text-sm">photo_camera</span>
                                <input type="file" name="profile_pic" class="hidden">
                            </label>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Update Essence</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-4">Whisper Bio</label>
                            <textarea name="bio" rows="3" class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-primary transition-all text-sm italic" placeholder="What does your soul say?"><?php echo htmlspecialchars($targetUser['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-4">Dwell In</label>
                            <div class="relative">
                                <span class="material-icons-round absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-50">place</span>
                                <input type="text" name="location" value="<?php echo htmlspecialchars($targetUser['location'] ?? ''); ?>" class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-full pl-12 pr-6 py-3.5 focus:ring-2 focus:ring-primary transition-all text-sm" placeholder="City, Heart">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-4">Calling</label>
                            <div class="relative">
                                <span class="material-icons-round absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-50">work</span>
                                <input type="text" name="occupation" value="<?php echo htmlspecialchars($targetUser['occupation'] ?? ''); ?>" class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-full pl-12 pr-6 py-3.5 focus:ring-2 focus:ring-primary transition-all text-sm" placeholder="Your Passion">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-4">Wisdom Journey</label>
                            <div class="relative">
                                <span class="material-icons-round absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-50">school</span>
                                <input type="text" name="education" value="<?php echo htmlspecialchars($targetUser['education'] ?? ''); ?>" class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-full pl-12 pr-6 py-3.5 focus:ring-2 focus:ring-primary transition-all text-sm" placeholder="School of Life">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-4">Heart Status</label>
                            <div class="relative">
                                <span class="material-icons-round absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-50">favorite</span>
                                <select name="relationship_status" class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-full pl-12 pr-6 py-3.5 focus:ring-2 focus:ring-primary transition-all text-sm appearance-none">
                                    <option value="">Choosing...</option>
                                    <option value="Single" <?php echo ($targetUser['relationship_status']==='Single')?'selected':''; ?>>Exploring Solo</option>
                                    <option value="In a Relationship" <?php echo ($targetUser['relationship_status']==='In a Relationship')?'selected':''; ?>>Shared Journey</option>
                                    <option value="Engaged" <?php echo ($targetUser['relationship_status']==='Engaged')?'selected':''; ?>>Deeply Committed</option>
                                    <option value="Married" <?php echo ($targetUser['relationship_status']==='Married')?'selected':''; ?>>Eternally Bonded</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="w-full bg-primary hover:bg-primary-hover text-white py-4 rounded-full font-bold shadow-xl shadow-primary/20 hover:scale-[1.02] transition-all mt-8">
                        Seal the Journey
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Cover Modal -->
<div class="modal fade" id="updateCoverModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-white/95 dark:bg-background-dark/95 backdrop-blur-2xl border-none rounded-[2rem] shadow-2xl p-8">
            <h2 class="text-3xl font-display text-primary mb-8" style="font-family: 'Great Vibes', cursive;">Frame the Horizon</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                <div class="group relative bg-slate-50 dark:bg-white/5 border-2 border-dashed border-primary/20 rounded-3xl p-12 text-center transition-all hover:bg-primary/5 hover:border-primary/40">
                    <span class="material-icons-round text-6xl text-primary/20 group-hover:text-primary/40 group-hover:scale-110 transition-all">landscape</span>
                    <p class="mt-4 font-bold text-slate-500 uppercase tracking-widest text-xs">Capture the Vista</p>
                    <input type="file" name="cover_pic" class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.form.submit()">
                </div>
                <p class="text-[10px] text-slate-400 italic text-center mt-4">Panoramic whispers: 1200x350px recommended</p>
            </form>
        </div>
    </div>
</div>

<style>
.profile-shell {
    width: 100%;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
    padding-left: 1rem;
    padding-right: 1rem;
}

@media (min-width: 992px) {
    .profile-shell {
        padding-left: 1.25rem;
        padding-right: 1.25rem;
    }
}

.profile-layout {
    grid-template-columns: repeat(auto-fit, minmax(560px, 1fr));
    align-items: start;
}

@media (max-width: 1199.98px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }
}

.modal.fade .modal-dialog { transform: scale(0.9); transition: transform 0.3s ease-out; }
.modal.show .modal-dialog { transform: scale(1); }
</style>

<?php require_once 'includes/footer.php'; ?>
