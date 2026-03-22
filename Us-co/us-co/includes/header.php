<?php
require_once __DIR__ . '/functions.php';

if (!isset($page_title)) {
    $page_title = "Everest";
}
$current_page = basename($_SERVER['PHP_SELF']);
$page_head_extras = $page_head_extras ?? '';
$page_styles = $page_styles ?? [];
$body_class = $body_class ?? '';
$page_base_href = $page_base_href ?? '';
$page_minimal_layout = $page_minimal_layout ?? false;
$is_valentine_immersive = strpos($body_class, 'valentine-immersive') !== false;
$page_hide_guest_header = $page_hide_guest_header ?? false;
$body_layout_class = isLoggedIn() ? 'flex flex-col md:flex-row' : 'flex flex-col';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <?php if (!empty($page_base_href)): ?>
    <base href="<?php echo htmlspecialchars($page_base_href, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#ee2b8c",
                        "background-light": "#f8f6f7",
                        "background-dark": "#221019",
                        "sage": "#7a9e91",
                        "blush": "#fce4ec",
                        "cream": "#fffcf9"
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "1rem",
                        "lg": "2rem",
                        "xl": "3rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Great+Vibes&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS (required for .modal, .fade, .modal-dialog, etc.) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>">
    
    <?php if (!empty($page_styles)): ?>
        <?php foreach ($page_styles as $style_path): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($style_path); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .notification-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background-color: #ff416c;
            border-radius: 50%;
            border: 2px solid white;
        }
        .valentine-countdown {
            font-size: 0.7rem;
            background: #ff416c;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-800 dark:text-slate-100 min-h-screen <?php echo $body_layout_class; ?> <?php echo htmlspecialchars($body_class); ?>">
    
    <?php if (isLoggedIn()): ?>
    <!-- Sidebar Navigation -->
    <aside class="w-full md:w-64 bg-white/80 dark:bg-background-dark/80 backdrop-blur-xl border-r border-primary/10 md:h-screen md:sticky top-0 flex flex-col p-6 z-50">
        <div class="flex items-center gap-3 mb-10 px-2">
            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center shadow-lg shadow-primary/20 hover:rotate-12 transition-transform">
                <span class="material-icons-round text-white">favorite</span>
            </div>
            <span class="text-xl font-bold tracking-tight text-primary">Us & Co.</span>
        </div>
        
        <nav class="flex-1 space-y-2">
            <a class="flex items-center gap-4 px-4 py-3 <?php echo $current_page == 'dashboard.php' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-primary/10 hover:text-primary'; ?> rounded-full transition-all" href="dashboard.php">
                <span class="material-icons-round">dashboard</span>
                <span class="font-medium">Dashboard</span>
            </a>
            <a class="flex items-center gap-4 px-4 py-3 <?php echo $current_page == 'feed.php' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-primary/10 hover:text-primary'; ?> rounded-full transition-all" href="feed.php">
                <span class="material-icons-round">home</span>
                <span class="font-medium">Echoes</span>
            </a>
            <a class="flex items-center gap-4 px-4 py-3 <?php echo $current_page == 'gallery.php' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-primary/10 hover:text-primary'; ?> rounded-full transition-all" href="gallery.php">
                <span class="material-icons-round">auto_awesome</span>
                <span class="font-medium">Moments</span>
            </a>
            <a class="flex items-center gap-4 px-4 py-3 <?php echo $current_page == 'messages.php' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-primary/10 hover:text-primary'; ?> rounded-full transition-all" href="messages.php">
                <span class="material-icons-round">forum</span>
                <span class="font-medium">Letters</span>
            </a>
            <a class="flex items-center gap-4 px-4 py-3 <?php echo $current_page == 'news.php' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-primary/10 hover:text-primary'; ?> rounded-full transition-all" href="news.php">
                <span class="material-icons-round">newspaper</span>
                <span class="font-medium">News & Events</span>
            </a>
            <a class="flex items-center gap-4 px-4 py-3 <?php echo $current_page == 'explore.php' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-primary/10 hover:text-primary'; ?> rounded-full transition-all" href="explore.php">
                <span class="material-icons-round">explore</span>
                <span class="font-medium">Find Connections</span>
            </a>
        </nav>

        <div class="mt-auto pt-6 space-y-2">
            <a class="flex items-center gap-4 px-4 py-3 <?php echo $current_page == 'profile.php' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-primary/10 hover:text-primary'; ?> rounded-full transition-all" href="profile.php">
                <span class="material-icons-round">person</span>
                <span class="font-medium">My Profile</span>
            </a>
            <a class="flex items-center gap-4 px-4 py-3 text-slate-500 hover:bg-red-50 hover:text-red-500 rounded-full transition-all" href="logout.php">
                <span class="material-icons-round">logout</span>
                <span class="font-medium">Exit World</span>
            </a>
            
            <div class="p-4 bg-primary/5 rounded-xl border border-primary/10">
                <div class="flex items-center gap-3 text-primary">
                    <img src="<?php echo getProfilePic($_SESSION['profile_pic'] ?? 'default.jpg'); ?>" class="w-8 h-8 rounded-full border-2 border-white object-cover">
                    <div class="flex flex-col">
                        <span class="text-xs font-bold"><?php echo $_SESSION['username'] ?? 'User'; ?></span>
                        <span class="text-[10px] opacity-75">Online</span>
                    </div>
                </div>
            </div>
        </div>
    </aside>
    <?php endif; ?>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto p-3 md:p-4 lg:p-5">
        <?php if (!isLoggedIn() && !$page_hide_guest_header): ?>
        <!-- Minimal Header for Guest Users -->
        <?php if ($is_valentine_immersive): ?>
        <header class="valentine-topbar-wrap mb-10">
            <div class="valentine-topbar">
                <a href="index.php" class="valentine-brand-link" aria-label="Us and Co home">
                    <span class="valentine-brand-mark">
                        <span class="material-icons-round text-white">favorite</span>
                    </span>
                    <span class="valentine-brand-text">&nbsp;&nbsp;Us &amp; Co.</span>
                </a>
                <div class="valentine-topbar-copy text-center"></div>
                <a href="login.php" class="valentine-enter-btn">Enter Our World</a>
            </div>
        </header>
        <?php else: ?>
        <header class="flex justify-between items-center mb-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center shadow-lg">
                    <span class="material-icons-round text-white">favorite</span>
                </div>
                <span class="text-xl font-bold text-primary">Us & Co.</span>
            </div>
            <a href="login.php" class="bg-primary text-white px-6 py-2 rounded-full font-bold shadow-md hover:scale-105 transition-all text-sm">Enter Our World</a>
        </header>
        <?php endif; ?>
        <?php endif; ?>
    

