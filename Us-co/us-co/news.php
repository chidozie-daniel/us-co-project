<?php
$page_title = "News & Events";
require_once 'includes/functions.php';
require_once 'includes/news_api.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

require_once 'includes/header.php';

$user = getCurrentUser();
$newsAPI = getNewsAPI();

// Get news category from URL
$category = $_GET['category'] ?? 'general';
$search = $_GET['search'] ?? '';

// Get news data
$newsData = [];
$eventsData = [];

if ($search) {
    $newsData = $newsAPI->searchNews($search, 12);
} else {
    switch($category) {
        case 'technology':
            $newsData = $newsAPI->getTechNews();
            break;
        case 'entertainment':
            $newsData = $newsAPI->getEntertainmentNews();
            break;
        case 'sports':
            $newsData = $newsAPI->getSportsNews();
            break;
        default:
            $newsData = $newsAPI->getTopHeadlines('us', null, 12);
            break;
    }
}

// Use fallback data if API fails
if (isset($newsData['error']) || !isset($newsData['articles'])) {
    $fallbackCategory = $category === 'general' ? 'general' : $category;
    $newsData = getMockNewsData($fallbackCategory);
    $usingFallback = true;
} else {
    $usingFallback = false;
}

// Get local events
$eventsData = $newsAPI->getLocalEvents($user['location'] ?? 'New York');

// Format articles
$articles = [];
if (isset($newsData['articles'])) {
    foreach ($newsData['articles'] as $article) {
        $articles[] = $newsAPI->formatArticle($article);
    }
}
?>

<div class="container mx-auto px-4 md:px-8 py-12">
    <!-- Header Section -->
    <div class="mb-12">
        <h1 class="text-5xl md:text-6xl font-display text-primary mb-2" style="font-family: 'Great Vibes', cursive;">
            News & Events
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-lg">Stay connected with the world around us</p>
    </div>
    
    <!-- Category Navigation & Search -->
    <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl p-8 mb-12">
        <div class="flex flex-col md:flex-row gap-6 items-center justify-between">
            <!-- Categories -->
            <div class="flex flex-wrap gap-2">
                <a href="news.php" 
                   class="px-6 py-3 rounded-full font-bold text-sm transition-all <?php echo $category === 'general' && !$search ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 hover:bg-primary/10 hover:text-primary'; ?>">
                    <span class="material-icons-round text-sm align-middle mr-2">public</span>
                    General
                </a>
                <a href="news.php?category=technology" 
                   class="px-6 py-3 rounded-full font-bold text-sm transition-all <?php echo $category === 'technology' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 hover:bg-primary/10 hover:text-primary'; ?>">
                    <span class="material-icons-round text-sm align-middle mr-2">computer</span>
                    Technology
                </a>
                <a href="news.php?category=entertainment" 
                   class="px-6 py-3 rounded-full font-bold text-sm transition-all <?php echo $category === 'entertainment' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 hover:bg-primary/10 hover:text-primary'; ?>">
                    <span class="material-icons-round text-sm align-middle mr-2">movie</span>
                    Entertainment
                </a>
                <a href="news.php?category=sports" 
                   class="px-6 py-3 rounded-full font-bold text-sm transition-all <?php echo $category === 'sports' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 hover:bg-primary/10 hover:text-primary'; ?>">
                    <span class="material-icons-round text-sm align-middle mr-2">sports_soccer</span>
                    Sports
                </a>
            </div>
            
            <!-- Search -->
            <form method="GET" action="news.php" class="w-full md:w-auto">
                <div class="relative">
                    <span class="material-icons-round absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input type="text" 
                           name="search" 
                           placeholder="Search news..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full md:w-80 pl-12 pr-4 py-3 bg-slate-100 dark:bg-white/5 border-none rounded-full focus:ring-2 focus:ring-primary text-sm">
                </div>
            </form>
        </div>
    </div>

    <div class="news-page-layout">
        <!-- Main News Content -->
        <div class="xl:col-span-2 space-y-10">
            <?php if (isset($newsData['error'])): ?>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 rounded-r-2xl p-6">
                    <div class="flex items-start gap-3">
                        <span class="material-icons-round text-yellow-500">warning</span>
                        <div>
                            <p class="font-bold text-yellow-800 dark:text-yellow-200"><?php echo htmlspecialchars($newsData['error']); ?></p>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">Please configure your News API key in includes/news_api.php</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($articles)): ?>
                <?php if ($usingFallback): ?>
                    <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-r-2xl p-6">
                        <div class="flex items-start gap-3">
                            <span class="material-icons-round text-blue-500">info</span>
                            <p class="text-sm text-blue-800 dark:text-blue-200">Using demo data while news API is being configured. Real news will appear once the API is working.</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Featured Article -->
                <article class="news-featured bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl overflow-hidden group hover:shadow-2xl transition-all">
                    <div class="grid md:grid-cols-2 gap-0">
                        <div class="relative h-64 md:h-full overflow-hidden">
                            <img src="<?php echo htmlspecialchars($articles[0]['image']); ?>" 
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                 alt="<?php echo htmlspecialchars($articles[0]['title']); ?>">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                        </div>
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-4 py-1.5 bg-primary/10 text-primary rounded-full text-xs font-bold uppercase tracking-wider">
                                    <?php echo htmlspecialchars($articles[0]['source']); ?>
                                </span>
                                <span class="text-xs text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                    <span class="material-icons-round text-sm">schedule</span>
                                    <?php echo getTimeAgo($articles[0]['publishedAt']); ?>
                                </span>
                            </div>
                            <h2 class="text-2xl font-bold mb-4 leading-tight">
                                <a href="<?php echo htmlspecialchars($articles[0]['url']); ?>" 
                                   target="_blank" 
                                   class="text-slate-800 dark:text-white hover:text-primary transition-colors">
                                    <?php echo htmlspecialchars($articles[0]['title']); ?>
                                </a>
                            </h2>
                            <p class="text-slate-600 dark:text-slate-300 mb-6 leading-relaxed">
                                <?php echo htmlspecialchars($articles[0]['description']); ?>
                            </p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-500 flex items-center gap-1">
                                    <span class="material-icons-round text-sm">person</span>
                                    <?php echo htmlspecialchars($articles[0]['author']); ?>
                                </span>
                                <a href="<?php echo htmlspecialchars($articles[0]['url']); ?>" 
                                   target="_blank" 
                                   class="px-6 py-2.5 bg-primary text-white rounded-full font-bold text-sm shadow-lg shadow-primary/20 hover:scale-105 transition-all flex items-center gap-2">
                                    Read More
                                    <span class="material-icons-round text-sm">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
                
                <!-- More Articles Grid -->
                <div class="news-articles-grid gap-6">
                    <?php for ($i = 1; $i < count($articles); $i++): ?>
                        <article class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-2xl border border-primary/10 shadow-lg overflow-hidden group hover:shadow-2xl hover:scale-[1.02] transition-all">
                            <div class="relative h-48 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($articles[$i]['image']); ?>" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                     alt="<?php echo htmlspecialchars($articles[$i]['title']); ?>">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                                <span class="absolute top-4 left-4 px-3 py-1 bg-white/90 backdrop-blur-md text-primary rounded-full text-xs font-bold">
                                    <?php echo htmlspecialchars($articles[$i]['source']); ?>
                                </span>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="material-icons-round text-xs text-slate-400">schedule</span>
                                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">
                                        <?php echo getTimeAgo($articles[$i]['publishedAt']); ?>
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold mb-3 leading-tight line-clamp-2">
                                    <a href="<?php echo htmlspecialchars($articles[$i]['url']); ?>" 
                                       target="_blank" 
                                       class="text-slate-800 dark:text-white hover:text-primary transition-colors">
                                        <?php echo htmlspecialchars($articles[$i]['title']); ?>
                                    </a>
                                </h3>
                                <p class="text-sm text-slate-600 dark:text-slate-300 mb-4 line-clamp-3">
                                    <?php echo htmlspecialchars($articles[$i]['description']); ?>
                                </p>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-500 flex items-center gap-1">
                                        <span class="material-icons-round text-xs">person</span>
                                        <?php echo htmlspecialchars($articles[$i]['author']); ?>
                                    </span>
                                    <a href="<?php echo htmlspecialchars($articles[$i]['url']); ?>" 
                                       target="_blank" 
                                       class="text-primary font-bold text-sm hover:underline flex items-center gap-1">
                                        Read
                                        <span class="material-icons-round text-sm">arrow_forward</span>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endfor; ?>
                </div>
            <?php else: ?>
                <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl p-12 text-center">
                    <span class="material-icons-round text-6xl text-slate-200 dark:text-slate-700 mb-4">newspaper</span>
                    <h3 class="text-xl font-bold mb-2">No news articles found</h3>
                    <p class="text-slate-500">Try selecting a different category or search for something else.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar - Local Events -->
        <div class="xl:col-span-1">
            <div class="news-sidebar">
                <div class="bg-white/50 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-primary/10 shadow-xl overflow-hidden">
                    <div class="p-8 bg-gradient-to-r from-primary to-primary/80 text-white">
                        <h2 class="text-3xl font-display flex items-center gap-3" style="font-family: 'Great Vibes', cursive;">
                            <span class="material-icons-round text-4xl">event</span>
                            Local Events
                        </h2>
                    </div>
                    <div class="p-8 space-y-6">
                        <?php if (isset($eventsData['events']) && !empty($eventsData['events'])): ?>
                            <?php foreach ($eventsData['events'] as $event): ?>
                                <div class="group">
                                    <div class="flex gap-4">
                                        <div class="relative w-20 h-20 flex-shrink-0 rounded-2xl overflow-hidden">
                                            <img src="<?php echo htmlspecialchars($event['image']); ?>" 
                                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                                 alt="<?php echo htmlspecialchars($event['title']); ?>">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-bold text-sm mb-2 line-clamp-2 group-hover:text-primary transition-colors">
                                                <?php echo htmlspecialchars($event['title']); ?>
                                            </h4>
                                            <div class="space-y-1 text-xs text-slate-500">
                                                <div class="flex items-center gap-2">
                                                    <span class="material-icons-round text-xs">calendar_today</span>
                                                    <?php echo formatDate($event['date']); ?>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="material-icons-round text-xs">schedule</span>
                                                    <?php echo htmlspecialchars($event['time']); ?>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="material-icons-round text-xs">place</span>
                                                    <?php echo htmlspecialchars($event['location']); ?>
                                                </div>
                                            </div>
                                            <span class="inline-block mt-2 px-3 py-1 bg-primary/10 text-primary rounded-full text-xs font-bold">
                                                <?php echo htmlspecialchars($event['category']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="border-b border-slate-100 dark:border-white/5 mt-4"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-slate-500 text-center py-8">No upcoming events found.</p>
                        <?php endif; ?>
                        
                        <div class="pt-4">
                            <a href="#" class="block w-full text-center px-6 py-3 bg-primary/10 text-primary rounded-full font-bold text-sm hover:bg-primary hover:text-white transition-all">
                                <span class="material-icons-round text-sm align-middle mr-2">add</span>
                                View All Events
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php require_once 'includes/footer.php'; ?>
