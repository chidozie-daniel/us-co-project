<?php
// News API Configuration
class NewsAPI {
    private $apiKey;
    private $baseUrl = 'https://newsapi.org/v2';
    private $cacheFile = 'cache/news_cache.json';
    private $cacheTime = 3600; // 1 hour cache
    
    public function __construct() {
        // Using a free API key - you should get your own at https://newsapi.org
        $this->apiKey = 'c3920841ea5442a1afce427c0c732acc';
        
        // Create cache directory if it doesn't exist
        if (!file_exists('cache')) {
            mkdir('cache', 0755, true);
        }
    }
    
    // Get top headlines
    public function getTopHeadlines($country = 'us', $category = null, $pageSize = 10) {
        $endpoint = '/top-headlines';
        $params = [
            'country' => $country,
            'pageSize' => $pageSize
        ];
        
        if ($category) {
            $params['category'] = $category;
        }
        
        return $this->makeRequest($endpoint, $params);
    }
    
    // Search news
    public function searchNews($query, $pageSize = 10) {
        $endpoint = '/everything';
        $params = [
            'q' => $query,
            'pageSize' => $pageSize,
            'sortBy' => 'publishedAt'
        ];
        
        return $this->makeRequest($endpoint, $params);
    }
    
    // Get news by category
    public function getNewsByCategory($category, $country = 'us') {
        return $this->getTopHeadlines($country, $category);
    }
    
    // Get trending tech news
    public function getTechNews() {
        return $this->getTopHeadlines('us', 'technology');
    }
    
    // Get entertainment news
    public function getEntertainmentNews() {
        return $this->getTopHeadlines('us', 'entertainment');
    }
    
    // Get sports news
    public function getSportsNews() {
        return $this->getTopHeadlines('us', 'sports');
    }
    
    // Make API request with caching
    private function makeRequest($endpoint, $params) {
        $cacheKey = md5($endpoint . serialize($params));
        $cacheFile = "cache/news_{$cacheKey}.json";
        
        // Check cache first
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheTime) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached) {
                return $cached;
            }
        }
        
        // If no valid cache, make API request
        $url = $this->baseUrl . $endpoint . '?apiKey=' . $this->apiKey;
        
        foreach ($params as $key => $value) {
            $url .= '&' . urlencode($key) . '=' . urlencode($value);
        }
        
        // Log the request for debugging
        error_log("News API Request: " . $url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Everest-Social-Network/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log response for debugging
        error_log("News API Response Code: " . $httpCode);
        if ($error) {
            error_log("News API cURL Error: " . $error);
        }
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("News API JSON Error: " . json_last_error_msg());
                return ['error' => 'Invalid JSON response', 'details' => json_last_error_msg()];
            }
            
            // Cache the response
            file_put_contents($cacheFile, json_encode($data));
            
            return $data;
        }
        
        // Log detailed error information
        error_log("News API Failed Response: " . $response);
        
        // Return error or cached data if available
        if (file_exists($cacheFile)) {
            error_log("News API: Using cached data due to API failure");
            return json_decode(file_get_contents($cacheFile), true);
        }
        
        return ['error' => 'Failed to fetch news', 'code' => $httpCode, 'response' => substr($response, 0, 500)];
    }
    
    // Get local events (mock implementation - you can integrate with real events API)
    public function getLocalEvents($location = 'New York') {
        // This is a mock implementation
        // In production, you would integrate with APIs like:
        // - Eventbrite API
        // - Meetup API
        // - Facebook Events
        // - Google Places API
        
        $events = [
            [
                'title' => 'Tech Meetup: AI & Machine Learning',
                'date' => date('Y-m-d', strtotime('+3 days')),
                'time' => '6:00 PM',
                'location' => $location,
                'description' => 'Join us for an evening of AI discussions and networking',
                'category' => 'Technology',
                'image' => 'https://via.placeholder.com/300x200/4CAF50/white?text=Tech+Event'
            ],
            [
                'title' => 'Community Music Festival',
                'date' => date('Y-m-d', strtotime('+7 days')),
                'time' => '2:00 PM',
                'location' => $location,
                'description' => 'Annual music festival featuring local artists',
                'category' => 'Entertainment',
                'image' => 'https://via.placeholder.com/300x200/FF5722/white?text=Music+Festival'
            ],
            [
                'title' => 'Sports Championship Finals',
                'date' => date('Y-m-d', strtotime('+10 days')),
                'time' => '7:30 PM',
                'location' => $location,
                'description' => 'Championship finals - don\'t miss the action!',
                'category' => 'Sports',
                'image' => 'https://via.placeholder.com/300x200/2196F3/white?text=Sports+Event'
            ]
        ];
        
        return ['events' => $events];
    }
    
    // Format news article for display
    public function formatArticle($article) {
        return [
            'title' => $article['title'] ?? 'No title',
            'description' => $article['description'] ?? 'No description available',
            'url' => $article['url'] ?? '#',
            'image' => $article['urlToImage'] ?? 'https://via.placeholder.com/300x200/cccccc/000000?text=No+Image',
            'source' => $article['source']['name'] ?? 'Unknown',
            'publishedAt' => $article['publishedAt'] ?? date('Y-m-d H:i:s'),
            'author' => $article['author'] ?? 'Unknown'
        ];
    }
}

// Helper function to get news instance
function getNewsAPI() {
    static $newsAPI = null;
    if ($newsAPI === null) {
        $newsAPI = new NewsAPI();
    }
    return $newsAPI;
}

// Fallback mock data for when API fails
function getMockNewsData($category = 'general') {
    $mockArticles = [
        'general' => [
            [
                'title' => 'Breaking: Major Technology Breakthrough Announced',
                'description' => 'Scientists have made a groundbreaking discovery that could change the future of technology as we know it.',
                'url' => '#',
                'urlToImage' => 'https://picsum.photos/seed/tech1/300/200.jpg',
                'source' => ['name' => 'Tech News Daily'],
                'publishedAt' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'author' => 'John Doe'
            ],
            [
                'title' => 'Global Climate Summit Reaches Historic Agreement',
                'description' => 'World leaders have come together to sign a comprehensive climate action plan.',
                'url' => '#',
                'urlToImage' => 'https://picsum.photos/seed/climate1/300/200.jpg',
                'source' => ['name' => 'World News Network'],
                'publishedAt' => date('Y-m-d H:i:s', strtotime('-4 hours')),
                'author' => 'Jane Smith'
            ],
            [
                'title' => 'New Study Reveals Surprising Health Benefits',
                'description' => 'Research shows that simple lifestyle changes can significantly improve overall health and longevity.',
                'url' => '#',
                'urlToImage' => 'https://picsum.photos/seed/health1/300/200.jpg',
                'source' => ['name' => 'Health Today'],
                'publishedAt' => date('Y-m-d H:i:s', strtotime('-6 hours')),
                'author' => 'Dr. Michael Brown'
            ]
        ],
        'technology' => [
            [
                'title' => 'AI Revolution: Machine Learning Breakthrough',
                'description' => 'New AI model achieves unprecedented accuracy in complex problem-solving tasks.',
                'url' => '#',
                'urlToImage' => 'https://picsum.photos/seed/ai1/300/200.jpg',
                'source' => ['name' => 'TechCrunch'],
                'publishedAt' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'author' => 'Sarah Johnson'
            ],
            [
                'title' => 'Quantum Computing Reaches New Milestone',
                'description' => 'Researchers demonstrate quantum supremacy in practical applications.',
                'url' => '#',
                'urlToImage' => 'https://picsum.photos/seed/quantum1/300/200.jpg',
                'source' => ['name' => 'Wired'],
                'publishedAt' => date('Y-m-d H:i:s', strtotime('-3 hours')),
                'author' => 'Alex Chen'
            ]
        ],
        'entertainment' => [
            [
                'title' => 'Blockbuster Movie Breaks Box Office Records',
                'description' => 'The highly anticipated film shatters opening weekend expectations worldwide.',
                'url' => '#',
                'urlToImage' => 'https://picsum.photos/seed/movie1/300/200.jpg',
                'source' => ['name' => 'Entertainment Weekly'],
                'publishedAt' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'author' => 'Lisa Anderson'
            ],
            [
                'title' => 'Music Festival Announces Star-Studded Lineup',
                'description' => 'Major artists confirmed for summer festival series across multiple cities.',
                'url' => '#',
                'urlToImage' => 'https://picsum.photos/seed/music1/300/200.jpg',
                'source' => ['name' => 'Rolling Stone'],
                'publishedAt' => date('Y-m-d H:i:s', strtotime('-5 hours')),
                'author' => 'Mike Wilson'
            ]
        ],
        'sports' => [
            [
                'title' => 'Championship Game Goes Into Overtime Thriller',
                'description' => 'Incredible comeback leads to one of the most exciting finishes in sports history.',
                'url' => '#',
                'urlToImage' => 'https://picsum.photos/seed/sports1/300/200.jpg',
                'source' => ['name' => 'ESPN'],
                'publishedAt' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'author' => 'Tom Martinez'
            ],
            [
                'title' => 'Underdog Team Makes Historic Run',
                'description' => 'Nobody expected this team to reach the finals, but they\'re proving everyone wrong.',
                'url' => '#',
                'urlToImage' => 'https://picsum.photos/seed/underdog1/300/200.jpg',
                'source' => ['name' => 'Sports Illustrated'],
                'publishedAt' => date('Y-m-d H:i:s', strtotime('-4 hours')),
                'author' => 'Chris Davis'
            ]
        ]
    ];
    
    return ['articles' => $mockArticles[$category] ?? $mockArticles['general']];
}
?>
