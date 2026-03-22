<?php
require_once 'includes/functions.php';

// Redirect if not logged in (before any HTML output)
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = "Gallery";
require_once 'includes/header.php';

$user = getCurrentUser();
$action = $_GET['action'] ?? 'view';
$message = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    $caption = sanitize($_POST['caption']);
    $album = sanitize($_POST['album']);
    $tags = sanitize($_POST['tags']);
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload = uploadFile($_FILES['photo']);
        
        if ($upload['success']) {
            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO gallery (user_id, image_path, caption, album, tags) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$_SESSION['user_id'], $upload['path'], $caption, $album, $tags])) {
                $message = '<div class="alert alert-success">Photo uploaded successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to save photo details.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">' . $upload['error'] . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Please select a file to upload.</div>';
    }
}

// Get all albums
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT DISTINCT album FROM gallery WHERE user_id = ? AND album IS NOT NULL AND album != ''");
$stmt->execute([$_SESSION['user_id']]);
$albums = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get photos based on album filter
$albumFilter = $_GET['album'] ?? 'all';
if ($albumFilter === 'all') {
    $stmt = $conn->prepare("SELECT * FROM gallery WHERE user_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $conn->prepare("SELECT * FROM gallery WHERE user_id = ? AND album = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$_SESSION['user_id'], $albumFilter]);
}
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-12">
    <!-- Header Section -->
    <div class="text-center mb-16 space-y-6">
        <h1 class="text-7xl font-display text-primary drop-shadow-sm" style="font-family: 'Great Vibes', cursive;">Our Shared Moments</h1>
        <p class="text-slate-500 font-medium max-w-2xl mx-auto leading-relaxed italic">A mosaic of the light we've found together, captured in snapshots of soul.</p>
        
        <div class="flex flex-wrap justify-center gap-4 pt-4">
            <button class="bg-primary hover:bg-primary-hover text-white px-10 py-4 rounded-full font-bold shadow-xl shadow-primary/20 transition-all flex items-center gap-3" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <span class="material-icons-round">add_photo_alternate</span>
                Create Memory
            </button>
            <div class="relative group">
                <button class="bg-white/50 dark:bg-background-dark/50 backdrop-blur-md border border-primary/10 px-8 py-4 rounded-full font-bold text-slate-600 dark:text-slate-300 shadow-lg flex items-center gap-3 dropdown-toggle" data-bs-toggle="dropdown">
                    <span class="material-icons-round text-primary">filter_list</span>
                    <?php echo $albumFilter === 'all' ? 'All Journeys' : htmlspecialchars($albumFilter); ?>
                </button>
                <div class="dropdown-menu absolute hidden group-hover:block mt-2 w-56 bg-white/95 dark:bg-background-dark/95 backdrop-blur-xl border border-primary/5 rounded-2xl shadow-2xl z-50 overflow-hidden">
                    <a href="gallery.php?album=all" class="block px-6 py-4 text-sm font-bold text-slate-600 hover:bg-primary/5 hover:text-primary transition-colors border-b border-primary/5">All Journeys</a>
                    <?php foreach ($albums as $alb): ?>
                        <a href="gallery.php?album=<?php echo urlencode($alb); ?>" class="block px-6 py-4 text-sm font-bold text-slate-600 hover:bg-primary/5 hover:text-primary transition-colors"><?php echo htmlspecialchars($alb); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Message -->
    <?php if ($message): ?>
        <div class="max-w-md mx-auto mb-8"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Masonry Grid -->
    <?php if (empty($photos)): ?>
        <div class="bg-white/30 dark:bg-black/10 rounded-[3rem] border-2 border-dashed border-primary/10 p-24 text-center">
            <span class="material-icons-round text-8xl text-primary/10 mb-6">wb_sunny</span>
            <h3 class="text-2xl font-bold text-slate-400 italic">Eclipse of Memories</h3>
            <p class="text-slate-400 mt-2">Start capturing the light. Your gallery is waiting for its first spark.</p>
        </div>
    <?php else: ?>
        <div class="columns-1 md:columns-2 lg:columns-3 gap-8" id="photoGrid">
            <?php foreach ($photos as $photo): ?>
                <div class="mb-8 break-inside-avoid animate-fadeIn">
                    <div class="relative group rounded-3xl overflow-hidden bg-white/50 dark:bg-background-dark/50 border border-primary/5 shadow-xl transition-all hover:shadow-2xl hover:scale-[1.02]">
                        <img src="<?php echo $photo['image_path']; ?>" 
                             class="w-full h-auto cursor-pointer object-cover transition-all duration-700 group-hover:scale-110" 
                             data-bs-toggle="modal" 
                             data-bs-target="#photoModal"
                             data-id="<?php echo $photo['id']; ?>"
                             data-src="<?php echo $photo['image_path']; ?>"
                             data-caption="<?php echo htmlspecialchars($photo['caption']); ?>"
                             data-album="<?php echo htmlspecialchars($photo['album']); ?>"
                             data-tags="<?php echo htmlspecialchars($photo['tags']); ?>"
                             data-date="<?php echo formatDate($photo['uploaded_at']); ?>">
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-8">
                            <h3 class="text-white font-bold text-lg mb-1 truncate"><?php echo htmlspecialchars($photo['caption']); ?></h3>
                            <div class="flex items-center gap-2 text-[10px] font-bold text-white/70 uppercase tracking-widest">
                                <span class="material-icons-round text-sm">auto_awesome</span>
                                <?php echo htmlspecialchars($photo['album'] ?? 'Unbound'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-white/95 dark:bg-background-dark/95 backdrop-blur-2xl border-none rounded-[2.5rem] shadow-2xl p-10">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-4xl font-display text-primary" style="font-family: 'Great Vibes', cursive;">Sow a Memory</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form method="POST" action="gallery.php?action=upload" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="upload_photo" value="1">
                
                <div class="relative group bg-slate-50 dark:bg-white/5 border-2 border-dashed border-primary/20 rounded-3xl p-12 text-center transition-all hover:bg-primary/5 hover:border-primary/40">
                    <span class="material-icons-round text-6xl text-primary/20 group-hover:text-primary/40 group-hover:scale-110 transition-all">cloud_upload</span>
                    <p class="mt-4 font-bold text-slate-500 uppercase tracking-widest text-xs" id="fileNameDisplay">Cast Your Snapshot</p>
                    <input type="file" name="photo" class="absolute inset-0 opacity-0 cursor-pointer" required onchange="handleFileSelect(this)">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-4">Heart Caption</label>
                    <input type="text" name="caption" class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-full px-6 py-4 focus:ring-2 focus:ring-primary text-sm" placeholder="Whisper some words..." required>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-4">Journey</label>
                        <input type="text" name="album" class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-full px-6 py-4 focus:ring-2 focus:ring-primary text-sm" placeholder="e.g. Voyage" list="albumSuggestions">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-4">Sparks</label>
                        <input type="text" name="tags" class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-full px-6 py-4 focus:ring-2 focus:ring-primary text-sm" placeholder="Tags (comma separ.)">
                    </div>
                </div>

                <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white py-4 rounded-full font-bold shadow-xl shadow-primary/20 hover:scale-[1.02] transition-all pt-4">
                    Bloom into Gallery
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Photo View Modal -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-none shadow-none overflow-visible">
            <div class="relative flex flex-col md:flex-row h-full max-h-[90vh] bg-black/90 md:bg-white/95 dark:md:bg-background-dark/95 backdrop-blur-2xl rounded-[3rem] overflow-hidden shadow-2xl">
                <button type="button" class="absolute top-6 right-6 md:right-auto md:left-6 z-50 p-2 bg-black/50 md:bg-slate-100 rounded-full text-white md:text-slate-600 hover:scale-110 transition-all" data-bs-dismiss="modal">
                    <span class="material-icons-round">close</span>
                </button>

                <!-- Image Side -->
                <div class="flex-1 min-h-[40vh] md:min-h-0 flex items-center justify-center p-8 bg-black">
                    <img id="modalImage" src="" class="max-w-full max-h-full object-contain drop-shadow-2xl">
                </div>

                <!-- Info Side -->
                <div class="w-full md:w-96 p-8 md:p-12 flex flex-col justify-between bg-white/10 md:bg-transparent backdrop-blur-md md:backdrop-blur-none border-t md:border-t-0 md:border-l border-white/10 md:border-primary/5">
                    <div class="space-y-8">
                        <div>
                            <span class="text-[10px] font-bold text-primary uppercase tracking-widest mb-2 block">The Moment</span>
                            <h2 id="modalTitle" class="text-4xl font-display text-slate-800 dark:text-white" style="font-family: 'Great Vibes', cursive;"></h2>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter block mb-1 underline decoration-primary/30">Journey</span>
                                <span id="modalAlbum" class="text-sm font-bold text-slate-600 dark:text-slate-300"></span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter block mb-1 underline decoration-primary/30">Captured</span>
                                <span id="modalDate" class="text-sm font-bold text-slate-600 dark:text-slate-300"></span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter block underline decoration-primary/30">Soul Sparks</span>
                            <div id="modalTags" class="flex flex-wrap gap-2 italic text-primary font-medium text-xs"></div>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-12">
                        <a href="#" id="downloadBtn" class="flex-1 bg-primary text-white py-3 rounded-full font-bold shadow-lg shadow-primary/20 hover:scale-[1.05] transition-all flex items-center justify-center gap-2 text-sm" download>
                            <span class="material-icons-round text-lg">download</span>
                            Keep
                        </a>
                        <button id="deleteBtn" class="w-12 h-12 border border-red-100 text-red-400 hover:bg-red-50 rounded-full flex items-center justify-center transition-all">
                            <span class="material-icons-round">delete_outline</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.animate-fadeIn { animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
.dropdown-toggle::after { display: none; }
</style>

<script>
function handleFileSelect(input) {
    const display = document.getElementById('fileNameDisplay');
    if (input.files && input.files[0]) {
        display.textContent = input.files[0].name;
        display.classList.add('text-primary');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const photoModal = document.getElementById('photoModal');
    photoModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const photoId = button.getAttribute('data-id');
        const imageSrc = button.getAttribute('data-src');
        const caption = button.getAttribute('data-caption');
        const album = button.getAttribute('data-album');
        const tags = button.getAttribute('data-tags');
        const date = button.getAttribute('data-date');
        
        document.getElementById('modalImage').src = imageSrc;
        document.getElementById('modalTitle').textContent = caption || 'Moment in Soul';
        document.getElementById('modalAlbum').textContent = album || 'Unbound';
        document.getElementById('modalDate').textContent = date;
        
        const tagContainer = document.getElementById('modalTags');
        tagContainer.innerHTML = '';
        if (tags) {
            tags.split(',').forEach(tag => {
                const span = document.createElement('span');
                span.textContent = '#' + tag.trim();
                tagContainer.appendChild(span);
            });
        }

        document.getElementById('downloadBtn').href = imageSrc;
        
        document.getElementById('deleteBtn').onclick = function() {
            if (confirm('Does this memory need to return to the silence?')) {
                fetch('ajax/delete_photo.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'photo_id=' + photoId
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Persistence failed: ' + (data.error || 'Unknown mystery'));
                });
            }
        };
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>