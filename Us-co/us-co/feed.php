<?php
require_once 'includes/functions.php';

// Redirect if not logged in (before any HTML output)
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = "Our Echoes";
require_once 'includes/header.php';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];
$user = getCurrentUser();

// Get posts from self and friends
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.profile_pic,
           (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ? 
    OR p.user_id IN (
        SELECT CASE WHEN user_id1 = ? THEN user_id2 ELSE user_id1 END 
        FROM friends WHERE user_id1 = ? OR user_id2 = ?
    )
    ORDER BY p.created_at DESC
    LIMIT 20
");
$stmt->execute([$userId, $userId, $userId, $userId, $userId]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="w-full max-w-none px-2 sm:px-3 lg:px-4 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-7 xl:grid-cols-8 2xl:grid-cols-9 gap-8 xl:gap-10">
        <!-- Feed Content -->
        <div class="space-y-8 lg:col-span-4 xl:col-span-5 2xl:col-span-6">
            <!-- Create Post -->
            <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-2xl p-6 shadow-xl border border-primary/10">
                <div class="flex gap-4 mb-6">
                    <img src="<?php echo getProfilePic($user); ?>" class="w-12 h-12 rounded-full border-2 border-primary/20 object-cover shadow-md">
                    <button class="flex-1 text-left px-6 py-3 rounded-full bg-slate-50 dark:bg-white/5 text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10 transition-all border border-slate-100 dark:border-white/5 shadow-sm" data-bs-toggle="modal" data-bs-target="#createPostModal">
                        Share a memory, <?php echo htmlspecialchars($user['username']); ?>...
                    </button>
                    <div id="status-display" class="hidden"></div>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-white/5">
                    <button class="flex items-center gap-3 text-sm font-bold text-slate-500 hover:text-red-500 transition-colors px-4 py-2 rounded-full hover:bg-red-50 dark:hover:bg-red-900/20" onclick="openWebcam()">
                        <span class="material-icons-round text-red-500 text-xl">videocam</span>
                        Live Video
                    </button>
                    <button class="flex items-center gap-3 text-sm font-bold text-slate-500 hover:text-green-500 transition-colors px-4 py-2 rounded-full hover:bg-green-50 dark:hover:bg-green-900/20" onclick="document.getElementById('post-image').click()">
                        <span class="material-icons-round text-green-500 text-xl">image</span>
                        Photo/Video
                    </button>
                    <button class="flex items-center gap-3 text-sm font-bold text-slate-500 hover:text-yellow-500 transition-colors px-4 py-2 rounded-full hover:bg-yellow-50 dark:hover:bg-yellow-900/20" data-bs-toggle="modal" data-bs-target="#feelingModal">
                        <span class="material-icons-round text-yellow-500 text-xl">mood</span>
                        Feeling
                    </button>
                </div>
            </div>

            <!-- Posts List -->
            <div id="posts-container" class="space-y-8">
                <?php if (empty($posts)): ?>
                    <div class="bg-white dark:bg-white/5 rounded-xl p-12 text-center border-2 border-dashed border-slate-200 dark:border-white/10">
                        <span class="material-icons-round text-6xl text-primary/20 mb-4">favorite_border</span>
                        <h2 class="text-xl font-bold mb-2">Our world is waiting for a memory.</h2>
                        <p class="text-slate-500 mb-6">Share a photo or a thought to start our collection.</p>
                        <button class="bg-primary text-white px-8 py-3 rounded-full font-bold shadow-lg shadow-primary/30 hover:scale-105 transition-all" data-bs-toggle="modal" data-bs-target="#createPostModal">Capture Echo</button>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="bg-white dark:bg-white/5 rounded-xl shadow-sm border border-primary/5 overflow-hidden group" id="post-<?php echo $post['id']; ?>">
                            <!-- Post Header -->
                            <div class="p-4 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="relative">
                                        <img src="<?php echo getProfilePic($post['profile_pic']); ?>" class="w-12 h-12 rounded-full border-2 border-white shadow-md object-cover">
                                        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                                    </a>
                                    <div>
                                        <div class="flex items-center flex-wrap gap-2">
                                            <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="font-bold hover:text-primary transition-colors">
                                                <?php echo htmlspecialchars($post['username']); ?>
                                            </a>
                                            <?php if (!empty($post['feeling'])): ?>
                                                <span class="text-xs text-slate-400 bg-slate-100 dark:bg-white/5 px-2 py-1 rounded-full flex items-center gap-1">
                                                    is feeling <?php echo $post['feeling_icon']; ?> <?php echo htmlspecialchars($post['feeling']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center gap-2 text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">
                                            <span><?php echo getTimeAgo($post['created_at']); ?></span>
                                            <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                                            <span class="material-icons-round text-xs">public</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative inline-block text-left">
                                    <button class="text-slate-400 hover:text-primary transition-colors p-2 rounded-full hover:bg-primary/5">
                                        <span class="material-icons-round">more_horiz</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Post Content -->
                            <div class="px-4 pb-3">
                                <p class="text-slate-700 dark:text-slate-200 leading-relaxed"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            </div>

                            <!-- Post Media -->
                            <?php if ($post['media_path']): ?>
                                <div class="relative bg-slate-50 dark:bg-black/20 text-center">
                                    <?php if ($post['media_type'] === 'video'): ?>
                                        <video src="<?php echo $post['media_path']; ?>" controls class="max-h-[600px] w-full mx-auto"></video>
                                    <?php else: ?>
                                        <img src="<?php echo $post['media_path']; ?>" class="max-h-[600px] w-full object-contain mx-auto">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Post Stats -->
                            <div class="px-4 py-2 flex items-center justify-between border-b border-slate-50 dark:border-white/5">
                                <div class="flex items-center gap-3 like-counter-<?php echo $post['id']; ?>">
                                    <?php if ($post['like_count'] > 0): ?>
                                        <div class="flex -space-x-2">
                                            <div class="w-6 h-6 bg-primary rounded-full flex items-center justify-center border-2 border-white">
                                                <span class="material-icons-round text-white text-[10px]">favorite</span>
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-500"><?php echo $post['like_count']; ?> Echoes</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                                    <?php echo $post['comment_count']; ?> Comments
                                </div>
                            </div>

                            <!-- Post Actions -->
                            <div class="flex p-1">
                                <button onclick="likePost(<?php echo $post['id']; ?>, this)" 
                                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-lg hover:bg-primary/5 transition-all <?php echo $post['user_liked'] ? 'text-primary font-bold' : 'text-slate-500'; ?>">
                                    <span class="material-icons-round"><?php echo $post['user_liked'] ? 'favorite' : 'favorite_border'; ?></span>
                                    <span class="text-sm">Like</span>
                                </button>
                                <button class="flex-1 flex items-center justify-center gap-2 py-3 rounded-lg hover:bg-primary/5 transition-all text-slate-500">
                                    <span class="material-icons-round">forum</span>
                                    <span class="text-sm">Comment</span>
                                </button>
                                <button class="flex-1 flex items-center justify-center gap-2 py-3 rounded-lg hover:bg-primary/5 transition-all text-slate-500">
                                    <span class="material-icons-round">share</span>
                                    <span class="text-sm">Share</span>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Sidebar - optimized for the space -->
<aside class="hidden lg:block space-y-5 lg:col-span-3 xl:col-span-3 2xl:col-span-3"> <!-- Wider sidebar on desktop -->
    <div class="bg-primary p-6 rounded-xl text-white relative overflow-hidden shadow-lg shadow-primary/20"> <!-- Reduced from p-8 -->
        <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div> <!-- Adjusted decorative element -->
        <div class="relative z-10">
            <h2 class="text-3xl font-display mb-2" style="font-family: 'Great Vibes', cursive;">The Way I See You</h2> <!-- Reduced from text-4xl -->
            <p class="text-white/80 text-xs font-medium">Every echo is a heartbeat.</p> <!-- Reduced from text-sm -->
            <a href="gallery.php" class="mt-4 block w-full bg-white text-primary text-center py-2.5 rounded-full font-bold shadow-md hover:scale-105 transition-all text-sm">View Gallery</a> <!-- Reduced padding and text -->
        </div>
    </div>
    
    <div class="bg-white dark:bg-white/5 rounded-xl p-5 shadow-sm border border-primary/5"> <!-- Reduced from p-6 -->
        <h3 class="font-bold mb-3 flex items-center gap-2 text-base"> <!-- Adjusted size -->
            <span class="material-icons-round text-primary text-xl">local_fire_department</span> <!-- Adjusted icon size -->
            Trending Moments
        </h3>
        <div class="space-y-3 text-xs text-slate-500 italic"> <!-- Reduced from text-sm -->
            <p>No trending moments yet. Be the first to start a conversation!</p>
        </div>
    </div>
</aside>

<!-- Create Post Modal -->
<div class="modal fade" id="createPostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-white dark:bg-background-dark rounded-xl border-none shadow-2xl overflow-hidden">
            <div class="bg-primary p-6 text-white flex items-center justify-between">
                <h5 class="text-xl font-bold flex items-center gap-2">
                    <span class="material-icons-round">auto_awesome</span>
                    Create Echo
                </h5>
                <button type="button" class="text-white/70 hover:text-white transition-colors" data-bs-dismiss="modal">
                    <span class="material-icons-round">close</span>
                </button>
            </div>
            <div class="p-6">
                <form id="post-form" enctype="multipart/form-data">
                    <div class="flex gap-4 mb-4">
                        <img src="uploads/profile_pics/<?php echo $user['profile_pic']; ?>" class="w-10 h-10 rounded-full object-cover">
                        <div class="flex-1">
                            <h6 class="font-bold"><?php echo htmlspecialchars($user['username']); ?></h6>
                            <div id="status-display-modal" class="text-xs text-primary font-bold mt-1"></div>
                        </div>
                    </div>
                    
                    <textarea name="content" class="w-full min-h-[150px] p-4 rounded-xl border-none bg-slate-50 dark:bg-white/5 focus:ring-2 focus:ring-primary text-lg resize-none mb-4" placeholder="What's on your mind?"></textarea>
                    
                    <div id="image-preview-container" class="hidden relative rounded-xl overflow-hidden mb-4 bg-slate-100">
                        <img id="image-preview" class="max-h-[300px] w-full object-contain mx-auto hidden">
                        <video id="video-preview" class="max-h-[300px] w-full mx-auto hidden" controls></video>
                        <button type="button" onclick="clearPreview()" class="absolute top-2 right-2 bg-black/50 text-white p-1 rounded-full hover:bg-black/70">
                            <span class="material-icons-round text-sm">close</span>
                        </button>
                    </div>

                    <input type="file" id="post-image" name="media" class="hidden" accept="image/*,video/*" onchange="previewImage(this)">
                    <input type="hidden" id="feeling-input" name="feeling">
                    <input type="hidden" id="feeling-icon-input" name="feeling_icon">

                    <button type="submit" class="w-full bg-primary text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/30 hover:bg-primary-dark transition-all flex items-center justify-center gap-2">
                        <span>Capture Echo</span>
                        <span class="material-icons-round">send</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Feeling/Activity Modal -->
<div class="modal fade" id="feelingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content bg-white dark:bg-background-dark border-none rounded-xl shadow-2xl">
            <div class="p-4 border-b border-slate-50 dark:border-white/5">
                <h6 class="font-bold flex items-center gap-2">
                    <span class="material-icons-round text-yellow-500">mood</span>
                    How are you feeling?
                </h6>
            </div>
            <div class="p-2 max-h-[300px] overflow-y-auto custom-scrollbar">
                <div class="grid grid-cols-1 gap-1">
                    <?php
                    $feelings = [
                        ['Happy', '😄'], ['Blessed', '😇'], ['Loved', '🥰'], 
                        ['Sad', '😢'], ['Excited', '🤩'], ['Tired', '😴'],
                        ['Cool', '😎'], ['Peaceful', '🧘'], ['Thinking', '🤔']
                    ];
                    foreach ($feelings as $f):
                    ?>
                    <button class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-primary/5 transition-all text-left" onclick="selectFeeling('<?php echo $f[0]; ?>', '<?php echo $f[1]; ?>')">
                        <span class="text-2xl"><?php echo $f[1]; ?></span>
                        <span class="font-medium text-slate-700 dark:text-slate-200"><?php echo $f[0]; ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Live Video Modal -->
<div class="modal fade" id="liveVideoModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-black rounded-xl border-none shadow-2xl overflow-hidden">
             <div class="p-4 flex items-center justify-between text-white">
                <h5 class="font-bold flex items-center gap-2">
                    <span class="material-icons-round text-red-500">videocam</span>
                    Live Capture
                </h5>
                <button type="button" class="text-white/70 hover:text-white" data-bs-dismiss="modal" onclick="stopWebcam()">
                    <span class="material-icons-round">close</span>
                </button>
            </div>
            <div class="relative aspect-video bg-slate-900 border-y border-white/10">
                 <video id="webcamPreview" autoplay playsinline muted class="w-full h-full object-cover"></video>
                 <div class="absolute bottom-6 left-0 w-full flex justify-center gap-6">
                     <button id="startRecordBtn" class="w-16 h-16 bg-red-600 hover:bg-red-700 rounded-full flex items-center justify-center shadow-2xl shadow-red-600/50 hover:scale-110 transition-all border-4 border-white" onclick="startRecording()">
                         <div class="w-6 h-6 bg-white rounded-full"></div>
                     </button>
                     <button id="stopRecordBtn" class="hidden w-16 h-16 bg-white hover:bg-slate-100 rounded-full flex items-center justify-center shadow-2xl hover:scale-110 transition-all" onclick="stopRecording()">
                         <div class="w-6 h-6 bg-red-600 rounded-sm"></div>
                     </button>
                 </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(238, 43, 140, 0.2); border-radius: 10px; }
</style>

<script>
let mediaRecorder;
let recordedChunks = [];
let stream;

// Post & Preview Logic
function previewImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const previewImg = document.getElementById('image-preview');
        const previewVideo = document.getElementById('video-preview');
        const container = document.getElementById('image-preview-container');
        
        container.classList.remove('hidden');
        
        if (file.type.startsWith('video/')) {
            previewImg.classList.add('hidden');
            previewVideo.classList.remove('hidden');
            previewVideo.src = URL.createObjectURL(file);
        } else {
            previewVideo.classList.add('hidden');
            previewVideo.pause();
            previewImg.classList.remove('hidden');
            
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    }
}

function clearPreview() {
    document.getElementById('post-image').value = "";
    document.getElementById('image-preview-container').classList.add('hidden');
    document.getElementById('video-preview').pause();
    document.getElementById('video-preview').src = "";
}

function selectFeeling(feeling, icon) {
    document.getElementById('feeling-input').value = feeling;
    document.getElementById('feeling-icon-input').value = icon;
    
    // Update UI
    const statusDivModal = document.getElementById('status-display-modal');
    statusDivModal.innerHTML = `<span class="bg-primary/10 px-2 py-1 rounded">is feeling ${icon} ${feeling}</span>`;
    
    bootstrap.Modal.getInstance(document.getElementById('feelingModal')).hide();
}

// Webcam Capture
async function openWebcam() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        document.getElementById('webcamPreview').srcObject = stream;
        new bootstrap.Modal(document.getElementById('liveVideoModal')).show();
    } catch (err) { alert("Camera access denied."); }
}

function stopWebcam() {
    if (stream) stream.getTracks().forEach(t => t.stop());
}

function startRecording() {
    recordedChunks = [];
    mediaRecorder = new MediaRecorder(stream);
    mediaRecorder.ondataavailable = e => { if (e.data.size > 0) recordedChunks.push(e.data); };
    mediaRecorder.onstop = () => {
        const blob = new Blob(recordedChunks, { type: 'video/webm' });
        const file = new File([blob], `live-${Date.now()}.webm`, { type: "video/webm" });
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('post-image').files = dt.files;
        previewImage(document.getElementById('post-image'));
        bootstrap.Modal.getInstance(document.getElementById('liveVideoModal')).hide();
        stopWebcam();
    };
    mediaRecorder.start();
    document.getElementById('startRecordBtn').classList.add('hidden');
    document.getElementById('stopRecordBtn').classList.remove('hidden');
}

function stopRecording() {
    mediaRecorder.stop();
}

// Ajax Submission
document.getElementById('post-form').onsubmit = function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span>Capturing...</span>';

    fetch('ajax/create_post.php', { method: 'POST', body: new FormData(this) })
    .then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else { alert(data.error); btn.disabled = false; btn.innerHTML = '<span>Capture Echo</span>'; }
    });
};

function likePost(postId, btn) {
    fetch('ajax/like_post.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `post_id=${postId}`
    })
    .then(r => r.json()).then(data => {
        if (data.success) {
            const icon = btn.querySelector('.material-icons-round');
            const counter = document.querySelector(`.like-counter-${postId}`);
            if (data.action === 'liked') {
                btn.classList.add('text-primary', 'font-bold');
                icon.textContent = 'favorite';
            } else {
                btn.classList.remove('text-primary', 'font-bold');
                icon.textContent = 'favorite_border';
            }
            if (counter) {
                counter.innerHTML = data.count > 0 ? `<div class="w-6 h-6 bg-primary rounded-full flex items-center justify-center border-2 border-white"><span class="material-icons-round text-white text-[10px]">favorite</span></div><span class="text-xs font-bold text-slate-500">${data.count} Echoes</span>` : '';
            }
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
