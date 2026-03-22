<?php
require_once 'includes/functions.php';

// Redirect if not logged in (before any HTML output)
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = "Articles";
require_once 'includes/header.php';

$user = getCurrentUser();
$action = $_GET['action'] ?? 'list';
$message = '';

// Handle article creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_article'])) {
    $title = sanitize($_POST['title']);
    $content = $_POST['content']; // Don't sanitize content too much, allow HTML
    $category = sanitize($_POST['category']);
    $tags = sanitize($_POST['tags']);
    
    if (!empty($title) && !empty($content)) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO articles (user_id, title, content, category, tags) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$_SESSION['user_id'], $title, $content, $category, $tags])) {
            $message = '<div class="alert alert-success">Article created successfully!</div>';
            $action = 'list';
        } else {
            $message = '<div class="alert alert-danger">Failed to create article.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Title and content are required.</div>';
    }
}

// Handle article deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM articles WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$_GET['delete'], $_SESSION['user_id']])) {
        $message = '<div class="alert alert-success">Article deleted successfully!</div>';
    }
}

// Get all articles
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM articles WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$stmt = $conn->prepare("SELECT DISTINCT category FROM articles WHERE user_id = ? AND category IS NOT NULL AND category != ''");
$stmt->execute([$_SESSION['user_id']]);
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="fas fa-newspaper me-2"></i>My Articles
        </h1>
        <div>
            <a href="articles.php?action=create" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Write New Article
            </a>
            <a href="articles.php?action=list" class="btn btn-outline-primary">
                <i class="fas fa-list me-1"></i>View All
            </a>
        </div>
    </div>
    
    <?php echo $message; ?>
    
    <?php if ($action === 'create'): ?>
        <!-- Create Article Form -->
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-pen me-2"></i>Write New Article
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading me-1"></i>Title
                                </label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       placeholder="Enter article title..." required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>Content
                                </label>
                                <textarea class="form-control" id="content" name="content" rows="15" 
                                          placeholder="Write your article here..." required></textarea>
                                <div class="form-text">You can use basic HTML formatting</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">
                                        <i class="fas fa-folder me-1"></i>Category
                                    </label>
                                    <input type="text" class="form-control" id="category" name="category" 
                                           placeholder="Love Letter, Relationship Tips, etc."
                                           list="categorySuggestions">
                                    <datalist id="categorySuggestions">
                                        <option value="Love Letter">
                                        <option value="Relationship Tips">
                                        <option value="Personal Story">
                                        <option value="Anniversary">
                                        <option value="Date Ideas">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="tags" class="form-label">
                                        <i class="fas fa-tags me-1"></i>Tags
                                    </label>
                                    <input type="text" class="form-control" id="tags" name="tags" 
                                           placeholder="love, romance, anniversary, etc.">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="create_article" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Publish Article
                                </button>
                                <a href="articles.php" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Articles List -->
        <div class="row">
            <div class="col-12">
                <?php if ($articles): ?>
                    <div class="row">
                        <?php foreach ($articles as $article): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="article.php?id=<?php echo $article['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($article['title']); ?>
                                            </a>
                                        </h5>
                                        
                                        <?php if ($article['category']): ?>
                                            <span class="badge bg-primary mb-2">
                                                <?php echo htmlspecialchars($article['category']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <p class="card-text text-muted">
                                            <?php echo substr(strip_tags($article['content']), 0, 150); ?>...
                                        </p>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo formatDate($article['created_at']); ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-eye me-1"></i>
                                                <?php echo $article['views']; ?> views
                                            </small>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex gap-2">
                                            <a href="article.php?id=<?php echo $article['id']; ?>" class="btn btn-sm btn-primary flex-grow-1">
                                                <i class="fas fa-eye me-1"></i>Read
                                            </a>
                                            <a href="articles.php?delete=<?php echo $article['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this article?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-newspaper fa-4x text-muted mb-3"></i>
                            <h4>No Articles Yet</h4>
                            <p class="text-muted mb-4">Start writing your first love letter or relationship article!</p>
                            <a href="articles.php?action=create" class="btn btn-primary btn-lg">
                                <i class="fas fa-pen me-2"></i>Write First Article
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
