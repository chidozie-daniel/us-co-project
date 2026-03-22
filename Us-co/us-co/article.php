<?php
require_once 'includes/functions.php';

$articleId = $_GET['id'] ?? null;

if (!$articleId || !is_numeric($articleId)) {
    redirect('articles.php');
}

$page_title = "Article";
require_once 'includes/header.php';

$user = getCurrentUser();
$conn = getDBConnection();

$conn = getDBConnection();

// Increment view count
$stmt = $conn->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
$stmt->execute([$articleId]);

// Get article
$stmt = $conn->prepare("SELECT a.*, u.username FROM articles a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
$stmt->execute([$articleId]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header("Location: articles.php");
    exit();
}

$isOwner = $article['user_id'] == $_SESSION['user_id'];
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <!-- Article Header -->
            <div class="mb-4">
                <a href="articles.php" class="btn btn-outline-secondary mb-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Articles
                </a>
                
                <h1 class="display-5 mb-3"><?php echo htmlspecialchars($article['title']); ?></h1>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3">
                        <i class="fas fa-user me-1"></i>
                        <strong><?php echo htmlspecialchars($article['username']); ?></strong>
                    </div>
                    <div class="me-3">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo formatDate($article['created_at']); ?>
                    </div>
                    <div class="me-3">
                        <i class="fas fa-eye me-1"></i>
                        <?php echo $article['views']; ?> views
                    </div>
                    <?php if ($article['category']): ?>
                        <div>
                            <span class="badge bg-primary">
                                <?php echo htmlspecialchars($article['category']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($article['tags']): ?>
                    <div class="mb-3">
                        <i class="fas fa-tags me-1"></i>
                        <?php
                        $tags = explode(',', $article['tags']);
                        foreach ($tags as $tag):
                            $tag = trim($tag);
                        ?>
                            <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($isOwner): ?>
                    <div class="mb-3">
                        <a href="articles.php?delete=<?php echo $article['id']; ?>" 
                           class="btn btn-outline-danger btn-sm"
                           onclick="return confirm('Are you sure you want to delete this article?')">
                            <i class="fas fa-trash me-1"></i>Delete Article
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Article Content -->
            <div class="card">
                <div class="card-body">
                    <div class="article-content">
                        <?php echo nl2br($article['content']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Article Footer -->
            <div class="mt-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Share this article</h6>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="shareArticle()">
                                <i class="fas fa-share me-1"></i>Share
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="copyLink()">
                                <i class="fas fa-link me-1"></i>Copy Link
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.article-content {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #333;
}

.article-content p {
    margin-bottom: 1.5rem;
}
</style>

<script>
function shareArticle() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo addslashes($article['title']); ?>',
            text: '<?php echo addslashes(substr(strip_tags($article['content']), 0, 100)); ?>...',
            url: window.location.href
        });
    } else {
        copyLink();
    }
}

function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        alert('Link copied to clipboard!');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
