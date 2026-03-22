<?php
require_once 'includes/functions.php';

// Redirect if not logged in (before any HTML output)
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = "Stories";
require_once 'includes/header.php';

$user = getCurrentUser();

// Handle story upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['story_media'])) {
    $uploadResult = uploadFile($_FILES['story_media'], 'story');
    
    if ($uploadResult['success']) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO stories (user_id, media_path, media_type, caption, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))");
        
        $mediaType = pathinfo($uploadResult['path'], PATHINFO_EXTENSION);
        $caption = sanitize($_POST['caption'] ?? '');
        
        if ($stmt->execute([$_SESSION['user_id'], $uploadResult['path'], $mediaType, $caption])) {
            $success = 'Your story has been posted!';
        }
    } else {
        $error = $uploadResult['error'];
    }
}

// Get active stories
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT s.*, u.username, u.profile_pic 
    FROM stories s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.expires_at > NOW() 
    ORDER BY s.created_at DESC
");
$stmt->execute();
$stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's own story
$stmt = $conn->prepare("SELECT * FROM stories WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$userStory = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="fas fa-book-open me-2"></i>Stories
            </h2>
        </div>
    </div>
    
    <!-- Stories Container -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="stories-wrapper">
                <div class="stories-container d-flex gap-3 overflow-auto pb-3">
                    <!-- Add Story -->
                    <div class="story-item">
                        <div class="story-card text-center" data-bs-toggle="modal" data-bs-target="#storyModal">
                            <div class="story-avatar">
                                <img src="uploads/profile_pics/<?php echo $user['profile_pic']; ?>" alt="You">
                                <div class="add-story-btn">
                                    <i class="fas fa-plus"></i>
                                </div>
                            </div>
                            <p class="story-username">Your Story</p>
                        </div>
                    </div>
                    
                    <!-- Existing Stories -->
                    <?php if ($stories): ?>
                        <?php foreach ($stories as $story): ?>
                            <div class="story-item">
                                <div class="story-card" onclick="viewStory(<?php echo $story['id']; ?>)">
                                    <div class="story-avatar">
                                        <img src="uploads/profile_pics/<?php echo $story['profile_pic']; ?>" alt="<?php echo htmlspecialchars($story['username']); ?>">
                                        <div class="story-ring"></div>
                                    </div>
                                    <p class="story-username"><?php echo htmlspecialchars($story['username']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Story Viewer -->
    <div class="modal fade" id="storyViewerModal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center">
                    <div id="storyContent" class="text-center">
                        <!-- Story content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upload Story Modal -->
    <div class="modal fade" id="storyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-camera me-2"></i>Create Story
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($userStory): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                You already have an active story that expires in <?php echo getTimeAgo(strtotime($userStory['expires_at'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="story_media" class="form-label">Photo or Video</label>
                            <input type="file" class="form-control" id="story_media" name="story_media" 
                                   accept="image/*,video/*" required>
                            <div class="form-text">Stories expire after 24 hours</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="caption" class="form-label">Caption (optional)</label>
                            <textarea class="form-control" id="caption" name="caption" rows="3" 
                                      placeholder="Add a caption to your story..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" <?php echo $userStory ? 'disabled' : ''; ?>>
                            <i class="fas fa-upload me-2"></i>Post Story
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function viewStory(storyId) {
    // Load story content via AJAX
    fetch(`ajax/get_story.php?id=${storyId}`)
        .then(response => response.json())
        .then(data => {
            const storyContent = document.getElementById('storyContent');
            
            if (data.media_type.match(/(jpg|jpeg|png|gif)$/i)) {
                storyContent.innerHTML = `
                    <img src="${data.media_path}" class="img-fluid" style="max-height: 80vh;">
                    <div class="mt-3">
                        <h5 class="text-white">${data.username}</h5>
                        <p class="text-white-50">${data.caption || ''}</p>
                        <small class="text-white-50">${new Date(data.created_at).toLocaleString()}</small>
                    </div>
                `;
            } else if (data.media_type.match(/(mp4|mov|avi)$/i)) {
                storyContent.innerHTML = `
                    <video controls class="img-fluid" style="max-height: 80vh;">
                        <source src="${data.media_path}" type="video/${data.media_type}">
                    </video>
                    <div class="mt-3">
                        <h5 class="text-white">${data.username}</h5>
                        <p class="text-white-50">${data.caption || ''}</p>
                        <small class="text-white-50">${new Date(data.created_at).toLocaleString()}</small>
                    </div>
                `;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('storyViewerModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error loading story:', error);
        });
}

// Auto-refresh stories every 5 minutes
setInterval(() => {
    location.reload();
}, 300000);
</script>

<style>
.stories-container {
    scrollbar-width: thin;
    scrollbar-color: #ddd transparent;
}

.stories-container::-webkit-scrollbar {
    height: 6px;
}

.stories-container::-webkit-scrollbar-track {
    background: transparent;
}

.stories-container::-webkit-scrollbar-thumb {
    background-color: #ddd;
    border-radius: 3px;
}

.story-item {
    flex: 0 0 auto;
    width: 100px;
}

.story-card {
    cursor: pointer;
    text-align: center;
}

.story-avatar {
    position: relative;
    width: 70px;
    height: 70px;
    margin: 0 auto 8px;
}

.story-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #ddd;
}

.story-ring {
    position: absolute;
    top: -3px;
    left: -3px;
    right: -3px;
    bottom: -3px;
    border: 3px solid #ff416c;
    border-radius: 50%;
    background: linear-gradient(45deg, #ff416c, #ff4b2b);
    z-index: -1;
}

.add-story-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 24px;
    height: 24px;
    background: #1877f2;
    border: 3px solid white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}

.story-username {
    font-size: 0.8rem;
    margin: 0;
    color: var(--text-main);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 576px) {
    .story-item {
        width: 80px;
    }
    
    .story-avatar {
        width: 60px;
        height: 60px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
