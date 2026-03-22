<?php
$page_title = "The Way I See You";
$page_minimal_layout = true;
$page_hide_global_player = true;
$page_hide_footer = true;
$body_class = "valentine-immersive";
$page_styles = ["css/valentine.css"];
$page_scripts = ["js/valentine.js"];
$page_head_extras = '
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500&family=Great+Vibes&display=swap" rel="stylesheet">
';

require_once 'includes/header.php';
require_once 'includes/functions.php';
require_once 'valentine_data.php';


$thought_success = '';
$thought_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_thought'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf)) {
        $thought_error = 'Something went wrong. Please try again.';
    } elseif (!checkRateLimit('valentine_thought', 3, 300)) {
        $thought_error = 'Please wait a moment before sending another note.';
    } else {
        $author = sanitize($_POST['author_name'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        if ($message === '') {
            $thought_error = 'Please write a message first.';
        } else {
            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO valentine_thoughts (author_name, message) VALUES (?, ?)");
            if ($stmt->execute([$author, $message])) {
                $thought_success = 'Your note is saved softly.';
            } else {
                $thought_error = 'Unable to save your note. Please try again.';
            }
        }
    }
}

$thoughts = [];
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT author_name, message, created_at FROM valentine_thoughts ORDER BY created_at DESC LIMIT 12");
    $stmt->execute();
    $thoughts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $thoughts = [];
}
?>

<section class="valentine-hero">
    <div class="hero-atmosphere"></div>
    <div class="hero-glow"></div>
    <div class="container">
        <div class="hero-card reveal">
            <div class="hero-script"><?php echo esc($valentine_meta['sub_line']); ?></div>
            <h1 class="hero-name"><?php echo esc($valentine_meta['name']); ?></h1>
            <p class="hero-line"><?php echo esc($valentine_meta['opening_line']); ?></p>
            <div class="hero-actions">
                <button class="music-toggle" type="button" id="musicToggle">
                    <span class="music-icon">♪</span>
                    <span class="music-text">Play our song</span>
                </button>
                <div class="music-meta">
                    <div class="music-title"><?php echo esc($valentine_meta['music_title']); ?></div>
                    <div class="music-artist"><?php echo esc($valentine_meta['music_artist']); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="floaters" id="heroFloaters"></div>
    <audio id="valentineAudio" preload="none" loop>
        <source src="<?php echo esc($valentine_meta['music_file']); ?>" type="audio/mpeg">
    </audio>
</section>

<section class="section story-section">
    <div class="container">
        <div class="section-title reveal">
            <h2>Our Story</h2>
            <p>A simple thread of moments that changed my life.</p>
        </div>
        <div class="story-grid">
            <?php foreach ($our_story as $story): ?>
            <article class="story-card reveal">
                <h3><?php echo esc($story['title']); ?></h3>
                <p><?php echo escNl($story['text']); ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section gallery-section">
    <div class="container">
        <div class="section-title reveal">
            <h2>The Way I See You</h2>
            <p>Every photo is a doorway to how I feel about you. Especially the ones you dislike.</p>
        </div>
        <div class="gallery-grid">
            <?php foreach ($gallery_items as $index => $item): ?>
            <figure class="gallery-item reveal" data-index="<?php echo $index; ?>"
                data-src="<?php echo esc($item['src']); ?>"
                data-title="<?php echo esc($item['title']); ?>"
                data-story="<?php echo esc($item['story']); ?>">
                <div class="gallery-image">
                    <img src="<?php echo esc($item['src']); ?>" alt="<?php echo esc($item['title']); ?>" loading="lazy">
                </div>
                <figcaption>
                    <h4><?php echo esc($item['title']); ?></h4>
                    <span>Open to read my heart</span>
                </figcaption>
            </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section letters-section">
    <div class="container">
        <div class="section-title reveal">
            <h2>Letters To You</h2>
            <p>Soft words for the moments that need them most.</p>
        </div>
        <div class="letters-grid">
            <?php foreach ($letters as $letter): ?>
            <article class="letter-card reveal">
                <h3><?php echo esc($letter['title']); ?></h3>
                <p><?php echo escNl($letter['text']); ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section spiritual-section">
    <div class="spiritual-sky"></div>
    <div class="stars-container" id="starsContainer"></div>
    <div class="container">
        <div class="section-title reveal">
            <h2 style="color: #fff; -webkit-text-fill-color: initial; background: none;"><?php echo esc($spiritual_section['title']); ?></h2>
            <p style="color: rgba(255,255,255,0.7);">Love that does not count the hours.</p>
        </div>
        <div class="spiritual-lines reveal">
            <?php foreach ($spiritual_section['lines'] as $line): ?>
                <p><?php echo esc($line); ?></p>
            <?php endforeach; ?>
        </div>
        <div class="spiritual-close reveal"><?php echo esc($spiritual_section['closing']); ?></div>
    </div>
</section>

<section class="section promise-section">
    <div class="container text-center">
        <div class="promise-card reveal">
            <div class="promise-title">A Quiet Promise</div>
            <p><?php echo esc($closing_promise); ?></p>
        </div>
    </div>
</section>

<section class="section thoughts-section">
    <div class="container">
        <div class="section-title reveal">
            <h2>Our Thoughts</h2>
            <p>A space for both of us. Post what's on your mind, I'll always be here to read it.</p>
        </div>

        <div class="thoughts-grid">
            <div class="thought-form reveal">
                <?php if ($thought_success): ?>
                    <div class="valentine-alert success"><?php echo esc($thought_success); ?></div>
                <?php endif; ?>
                <?php if ($thought_error): ?>
                    <div class="valentine-alert error"><?php echo esc($thought_error); ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo esc(generateCSRFToken()); ?>">
                    <div class="mb-3">
                        <label for="author_name" class="form-label">Your name (or a nickname)</label>
                        <input type="text" id="author_name" name="author_name" class="form-control" placeholder="Optional...">
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">What are you feeling?</label>
                        <textarea id="message" name="message" rows="5" class="form-control" placeholder="Write your thoughts here..." required></textarea>
                    </div>
                    <button type="submit" name="add_thought" class="btn btn-primary w-100 rounded-pill">Post to our board</button>
                </form>
            </div>

            <div class="thought-list-container reveal">
                <div class="thought-list">
                    <?php if (count($thoughts) > 0): ?>
                        <?php foreach ($thoughts as $thought): ?>
                            <div class="thought-card">
                                <div class="thought-meta">
                                    <span class="thought-name"><strong><?php echo esc($thought['author_name'] ?: 'Anonymous'); ?></strong></span>
                                    <span class="thought-date small mt-1"><?php echo formatDate($thought['created_at']); ?></span>
                                </div>
                                <p class="mb-0 mt-2"><?php echo escNl($thought['message']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="thought-empty">No notes yet. Be the first to leave a mark here.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="valentine-modal" id="valentineModal">
    <div class="modal-content">
        <button class="modal-close" data-close="true">&times;</button>
        <div class="modal-media">
            <img id="modalImage" src="" alt="">
        </div>
        <div class="modal-body">
            <h3 id="modalTitle"></h3>
            <p id="modalStory"></p>
            
            <div class="modal-controls">
                <button class="modal-nav" id="modalPrev">Previous</button>
                <button class="modal-nav" id="modalNext">Next</button>
            </div>
        </div>
    </div>
</div>


<?php require_once 'includes/footer.php'; ?>
