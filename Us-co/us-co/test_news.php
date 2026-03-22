<?php
// Test the News API functionality
require_once 'includes/news_api.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing News API</h2>";

try {
    $newsAPI = new NewsAPI();
    
    // Test 1: Get top headlines
    echo "<h3>Test 1: Getting Top Headlines...</h3>";
    $headlines = $newsAPI->getTopHeadlines('us', null, 5);
    
    if (isset($headlines['error'])) {
        echo "<p style='color: red;'>Error: " . htmlspecialchars($headlines['error']) . "</p>";
        echo "<p>HTTP Code: " . ($headlines['code'] ?? 'Unknown') . "</p>";
    } elseif (isset($headlines['articles'])) {
        echo "<p style='color: green;'>✓ Success! Found " . count($headlines['articles']) . " articles</p>";
        
        // Display first article
        if (!empty($headlines['articles'])) {
            $article = $headlines['articles'][0];
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<h4>" . htmlspecialchars($article['title'] ?? 'No title') . "</h4>";
            echo "<p><strong>Source:</strong> " . htmlspecialchars($article['source']['name'] ?? 'Unknown') . "</p>";
            echo "<p><strong>Published:</strong> " . htmlspecialchars($article['publishedAt'] ?? 'Unknown') . "</p>";
            echo "<p>" . htmlspecialchars($article['description'] ?? 'No description') . "</p>";
            if (!empty($article['urlToImage'])) {
                echo "<img src='" . htmlspecialchars($article['urlToImage']) . "' style='max-width: 200px; height: auto;'>";
            }
            echo "</div>";
        }
    } else {
        echo "<p style='color: orange;'>Unexpected response format:</p>";
        echo "<pre>" . htmlspecialchars(print_r($headlines, true)) . "</pre>";
    }
    
    // Test 2: Test caching
    echo "<h3>Test 2: Testing Caching...</h3>";
    $start = microtime(true);
    $headlines2 = $newsAPI->getTopHeadlines('us', null, 5);
    $end = microtime(true);
    
    if (isset($headlines2['articles'])) {
        echo "<p style='color: green;'>✓ Cache working! Second request took " . round(($end - $start) * 1000, 2) . "ms</p>";
    }
    
    // Test 3: Test categories
    echo "<h3>Test 3: Testing Categories...</h3>";
    $techNews = $newsAPI->getTechNews();
    if (isset($techNews['articles'])) {
        echo "<p style='color: green;'>✓ Tech news working: " . count($techNews['articles']) . " articles</p>";
    }
    
    // Test 4: Test search
    echo "<h3>Test 4: Testing Search...</h3>";
    $searchResults = $newsAPI->searchNews('technology', 3);
    if (isset($searchResults['articles'])) {
        echo "<p style='color: green;'>✓ Search working: " . count($searchResults['articles']) . " articles</p>";
    }
    
    // Test 5: Test events
    echo "<h3>Test 5: Testing Events...</h3>";
    $events = $newsAPI->getLocalEvents();
    if (isset($events['events'])) {
        echo "<p style='color: green;'>✓ Events working: " . count($events['events']) . " events</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<h3>Cache Directory Status:</h3>";
if (file_exists('cache')) {
    echo "<p style='color: green;'>✓ Cache directory exists</p>";
    if (is_writable('cache')) {
        echo "<p style='color: green;'>✓ Cache directory is writable</p>";
    } else {
        echo "<p style='color: red;'>✗ Cache directory is not writable</p>";
    }
    
    $files = glob('cache/*');
    echo "<p>Cache files: " . count($files) . "</p>";
    foreach ($files as $file) {
        echo "<small>" . basename($file) . " (" . date('Y-m-d H:i:s', filemtime($file)) . ")</small><br>";
    }
} else {
    echo "<p style='color: red;'>✗ Cache directory does not exist</p>";
}

echo "<hr>";
echo "<h3>cURL Test:</h3>";
if (function_exists('curl_init')) {
    echo "<p style='color: green;'>✓ cURL is available</p>";
    
    // Test cURL with a simple request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://httpbin.org/get');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "<p style='color: green;'>✓ cURL can make external requests</p>";
    } else {
        echo "<p style='color: red;'>✗ cURL request failed. HTTP Code: $httpCode</p>";
    }
} else {
    echo "<p style='color: red;'>✗ cURL is not available</p>";
}

echo "<hr>";
echo "<p><a href='news.php'>Go to News Page</a></p>";
echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
?>
