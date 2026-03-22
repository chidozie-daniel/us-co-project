<?php
$page_title = "The Way I See You";
$page_minimal_layout = true;
$page_hide_global_player = true;
$page_hide_footer = true;
$body_class = "valentine-immersive";
$page_base_href = "us-co/";
$page_styles = ["css/valentine.css?v=" . filemtime(__DIR__ . "/us-co/css/valentine.css")];
$page_scripts = ["js/valentine.js?v=" . filemtime(__DIR__ . "/us-co/js/valentine.js")];
$page_head_extras = '
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500&family=Great+Vibes&display=swap" rel="stylesheet">
';

require_once __DIR__ . '/us-co/includes/header.php';
require_once __DIR__ . '/us-co/includes/functions.php';
require_once __DIR__ . '/us-co/valentine_data.php';

$current_user = isLoggedIn() ? getCurrentUser() : null;
$spotify_embed_url = '';
$spotify_playlist_url = '';
if ($current_user && !empty($current_user['spotify_playlist_url'])) {
    $spotify_playlist_url = normalizeSpotifyPlaylistUrl($current_user['spotify_playlist_url']) ?? '';
    $spotify_embed_url = getSpotifyEmbedUrl($current_user['spotify_playlist_url']) ?? '';
}

// Fetch thoughts for the board
$thoughts = [];
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT author_name, message, created_at FROM valentine_thoughts ORDER BY created_at DESC LIMIT 12");
    $stmt->execute();
    $thoughts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $thoughts = [];
}

// Logic for adding thoughts (copied from valentine.php or shared)
$thought_success = '';
$thought_error = '';
$thought_posted = isset($_GET['thought_posted']) && $_GET['thought_posted'] === '1';
$board_cleared = isset($_GET['board_cleared']) && $_GET['board_cleared'] === '1';
if ($thought_posted) {
    $thought_success = 'Your note is saved softly.';
}
if ($board_cleared) {
    $thought_success = 'Board has been cleared.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_thought'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf)) {
        $thought_error = 'Something went wrong. Please try again.';
    } else {
        $author = sanitize($_POST['author_name'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        if ($message !== '') {
            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO valentine_thoughts (author_name, message) VALUES (?, ?)");
            if ($stmt->execute([$author, $message])) {
                $redirect_target = $_SERVER['PHP_SELF'] . '?thought_posted=1#thoughts_section';
                header('Location: ' . $redirect_target);
                exit;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_board'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf)) {
        $thought_error = 'Something went wrong. Please try again.';
    } else {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("DELETE FROM valentine_thoughts");
            $stmt->execute();
            $redirect_target = $_SERVER['PHP_SELF'] . '?board_cleared=1#thoughts_section';
            header('Location: ' . $redirect_target);
            exit;
        } catch (Exception $e) {
            $thought_error = 'Could not clear board right now. Please try again.';
        }
    }
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
                <button
                    class="music-toggle"
                    type="button"
                    id="musicToggle"
                    data-music-source="<?php echo $spotify_embed_url ? 'spotify' : 'audio'; ?>"
                    data-spotify-url="<?php echo esc($spotify_playlist_url); ?>"
                >
                    <span class="music-icon">♪</span>
                    <span class="music-text">Play our song</span>
                </button>
                <div class="music-meta">
                    <div class="music-title"><?php echo esc($spotify_embed_url ? 'Spotify Playlist' : $valentine_meta['music_title']); ?></div>
                    <div class="music-artist"><?php echo esc($spotify_embed_url ? 'From your linked account' : $valentine_meta['music_artist']); ?></div>
                </div>
            </div>
            <?php if ($spotify_embed_url): ?>
                <div class="spotify-player-wrap" id="spotifyPlayerWrap" hidden>
                    <iframe
                        id="spotifyPlayerFrame"
                        src="<?php echo esc($spotify_embed_url); ?>"
                        data-base-src="<?php echo esc($spotify_embed_url); ?>"
                        width="100%"
                        height="152"
                        frameborder="0"
                        allowfullscreen=""
                        allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                        loading="lazy"
                    ></iframe>
                </div>
            <?php endif; ?>
            <div class="mt-4 d-flex justify-content-center gap-3">
                <a href="#story_section" class="btn btn-outline-primary rounded-pill px-4 py-2">Begin Our Story</a>
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">Go to Our Echoes</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">Private Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="floaters" id="heroFloaters"></div>
    <?php
        $music_file = $valentine_meta['music_file'];
        $music_ext = strtolower(pathinfo($music_file, PATHINFO_EXTENSION));
        $music_type = 'audio/mpeg';
        if (in_array($music_ext, ['m4a', 'mp4'], true)) {
            $music_type = 'audio/mp4';
        } elseif ($music_ext === 'ogg') {
            $music_type = 'audio/ogg';
        } elseif ($music_ext === 'wav') {
            $music_type = 'audio/wav';
        }
    ?>
    <audio id="valentineAudio" preload="none" loop>
        <source src="<?php echo esc($music_file); ?>" type="<?php echo esc($music_type); ?>">
    </audio>
</section>

<section class="welcome-strip">
    <div class="container">
        <div class="welcome-panel reveal">
            <span class="welcome-kicker">Welcome</span>
            <h2>Start Here, Move Gently Through Our World</h2>
            <p>Every section below holds a piece of us. Begin with our story, open the gallery moments, read the letters, and leave a thought before you go.</p>
            <div class="welcome-links">
                <a href="#story_section">Our Story</a>
                <a href="#gallery_section">Gallery</a>
                <a href="#letters_section">Letters</a>
                <a href="#thoughts_section">Our Thoughts</a>
            </div>
        </div>
    </div>
</section>

<section id="story_section" class="section story-section">
    <div class="container">
        <div class="section-title reveal">
            <h2>Our Story</h2>
            <p>A simple thread of moments that changed my life.</p>
        </div>
        <div class="story-grid">
            <?php foreach ($our_story as $story_index => $story): ?>
            <?php
                $is_quiet_start = (strcasecmp(trim($story['title']), 'A Quiet Start') === 0) || $story_index === 0;
                $is_steady_ground = (strcasecmp(trim($story['title']), 'Steady Ground') === 0) || $story_index === 1;
                $story_modal_key = $is_quiet_start ? 'quiet-start' : ($is_steady_ground ? 'steady-ground' : '');
            ?>
            <article
                class="story-card reveal<?php echo $story_modal_key ? ' story-card-trigger' : ''; ?>"
                <?php if ($story_modal_key): ?>
                    data-story-modal-open="<?php echo esc($story_modal_key); ?>"
                    role="button"
                    tabindex="0"
                    aria-label="Open full <?php echo esc($story['title']); ?> story"
                <?php endif; ?>
            >
                <div class="story-image">
                    <img src="<?php echo esc($story['image']); ?>" alt="<?php echo esc($story['title']); ?>" onerror="this.src='assets/valentine/story/meeting.jpg';">
                </div>
                <div class="story-content">
                    <h3><?php echo esc($story['title']); ?></h3>
                    <p><?php echo escNl($story['text']); ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="gallery_section" class="section gallery-section">
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

<section id="letters_section" class="section letters-section">
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

<section id="thoughts_section" class="section thoughts-section">
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
                <form method="POST" action="" class="mt-3">
                    <input type="hidden" name="csrf_token" value="<?php echo esc(generateCSRFToken()); ?>">
                    <button
                        type="submit"
                        name="clear_board"
                        class="btn btn-outline-danger w-100 rounded-pill"
                        onclick="return confirm('Clear the entire board? This action cannot be undone.');"
                    >Clear board</button>
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
        <button
            class="modal-close"
            data-close="true"
            type="button"
            onclick="var m=document.getElementById('valentineModal'); if(m){m.classList.remove('active');} document.body.style.overflow='';"
        >&times;</button>
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

<div class="valentine-modal quiet-story-modal" id="quietStartModal">
    <div class="quiet-story-content">
        <button class="modal-close" data-close="true" aria-label="Close quiet start story">&times;</button>
        <div class="quiet-story-header">
            <h3><?php echo esc($quiet_start_story['title']); ?></h3>
            <p>The beginning that stayed with me.</p>
        </div>
        <div class="quiet-story-scroll" id="quietStoryScroll">
            <div class="quiet-story-inner">
                <?php foreach ($quiet_start_story['parts'] as $part): ?>
                    <section class="quiet-story-part">
                        <h4><?php echo esc($part['heading']); ?></h4>
                        <p><?php echo esc($part['text']); ?></p>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="valentine-modal quiet-story-modal" id="steadyGroundModal">
    <div class="quiet-story-content">
        <button class="modal-close" data-close="true" aria-label="Close steady ground story">&times;</button>
        <div class="quiet-story-header">
            <h3><?php echo esc($steady_ground_story['title']); ?></h3>
            <p>The love that keeps standing.</p>
        </div>
        <div class="quiet-story-scroll" id="steadyStoryScroll">
            <div class="quiet-story-inner">
                <?php foreach ($steady_ground_story['parts'] as $part): ?>
                    <section class="quiet-story-part">
                        <h4><?php echo esc($part['heading']); ?></h4>
                        <p><?php echo esc($part['text']); ?></p>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var storyAutoScroll = {};
        var storySpeed = 60; // px per second

        function getStoryElements(key) {
            if (key === 'quiet-start') {
                return {
                    modal: document.getElementById('quietStartModal'),
                    scroll: document.getElementById('quietStoryScroll')
                };
            }
            if (key === 'steady-ground') {
                return {
                    modal: document.getElementById('steadyGroundModal'),
                    scroll: document.getElementById('steadyStoryScroll')
                };
            }
            return { modal: null, scroll: null };
        }

        function stopStoryAutoScroll(key) {
            if (!storyAutoScroll[key]) return;
            if (storyAutoScroll[key].frame) {
                window.cancelAnimationFrame(storyAutoScroll[key].frame);
            }
            storyAutoScroll[key] = { frame: null, lastTs: 0 };
        }

        function stepStoryAutoScroll(key, ts) {
            var parts = getStoryElements(key);
            if (!parts.modal || !parts.scroll || !parts.modal.classList.contains('active')) return;

            if (!storyAutoScroll[key]) {
                storyAutoScroll[key] = { frame: null, lastTs: 0 };
            }
            if (!storyAutoScroll[key].lastTs) {
                storyAutoScroll[key].lastTs = ts;
            }

            var deltaMs = ts - storyAutoScroll[key].lastTs;
            storyAutoScroll[key].lastTs = ts;
            var maxScroll = parts.scroll.scrollHeight - parts.scroll.clientHeight;

            if (maxScroll > 0) {
                parts.scroll.scrollTop += (storySpeed * deltaMs) / 1000;
                if (parts.scroll.scrollTop >= maxScroll - 1) {
                    parts.scroll.scrollTop = 0;
                }
            }

            storyAutoScroll[key].frame = window.requestAnimationFrame(function (nextTs) {
                stepStoryAutoScroll(key, nextTs);
            });
        }

        function startStoryAutoScroll(key) {
            stopStoryAutoScroll(key);
            if (!storyAutoScroll[key]) {
                storyAutoScroll[key] = { frame: null, lastTs: 0 };
            }
            storyAutoScroll[key].frame = window.requestAnimationFrame(function (ts) {
                stepStoryAutoScroll(key, ts);
            });
        }

        function openStoryModal(key) {
            var parts = getStoryElements(key);
            if (!parts.modal || !parts.scroll) return;
            parts.modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            parts.scroll.scrollTop = 0;
            window.setTimeout(function () {
                startStoryAutoScroll(key);
            }, 120);
        }

        function closeStoryModal(key) {
            var parts = getStoryElements(key);
            if (!parts.modal) return;
            parts.modal.classList.remove('active');
            stopStoryAutoScroll(key);
            document.body.style.overflow = '';
        }

        var triggers = document.querySelectorAll('[data-story-modal-open]');
        triggers.forEach(function (trigger) {
            var key = trigger.getAttribute('data-story-modal-open');
            if (!key) return;
            trigger.addEventListener('click', function () {
                openStoryModal(key);
            });
            trigger.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openStoryModal(key);
                }
            });
        });

        var quietModal = document.getElementById('quietStartModal');
        if (quietModal) {
            quietModal.addEventListener('click', function (event) {
                if (event.target.id === 'quietStartModal' || event.target.dataset.close === 'true') {
                    closeStoryModal('quiet-start');
                }
            });
        }

        var steadyModal = document.getElementById('steadyGroundModal');
        if (steadyModal) {
            steadyModal.addEventListener('click', function (event) {
                if (event.target.id === 'steadyGroundModal' || event.target.dataset.close === 'true') {
                    closeStoryModal('steady-ground');
                }
            });
        }
    })();
</script>

<?php require_once __DIR__ . '/us-co/includes/footer.php'; ?>
