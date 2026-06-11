<?php
// ============================================
// YOUTUBE HELPER FUNCTIONS
// ============================================

/**
 * Extract YouTube video ID from various URL formats
 * 
 * Supported formats:
 * - https://www.youtube.com/watch?v=VIDEO_ID
 * - https://youtu.be/VIDEO_ID
 * - https://www.youtube.com/embed/VIDEO_ID
 * - https://www.youtube.com/shorts/VIDEO_ID
 * - https://m.youtube.com/watch?v=VIDEO_ID
 * 
 * @param string $url YouTube URL
 * @return string|null Video ID or null if not found
 */
function extractYouTubeId($url) {
    if (empty($url)) {
        return null;
    }
    
    $patterns = [
        // Standard watch URL
        '/(?:youtube\.com\/watch\?v=)([^&]+)/',
        // Short youtu.be URL
        '/(?:youtu\.be\/)([^?]+)/',
        // Embed URL
        '/(?:youtube\.com\/embed\/)([^?]+)/',
        // Shorts URL
        '/(?:youtube\.com\/shorts\/)([^?]+)/',
        // Mobile URL
        '/(?:m\.youtube\.com\/watch\?v=)([^&]+)/',
        // YouTube live URL
        '/(?:youtube\.com\/live\/)([^?]+)/',
        // With playlist parameter
        '/(?:youtube\.com\/watch\?v=)([^&]+)&/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/**
 * Generate YouTube embed URL from video URL
 * 
 * @param string $url YouTube URL
 * @param array $options Embed options (autoplay, controls, etc.)
 * @return string|null Embed URL or null if invalid
 */
function getYouTubeEmbedUrl($url, $options = []) {
    $videoId = extractYouTubeId($url);
    
    if (!$videoId) {
        return null;
    }
    
    // Default options
    $defaultOptions = [
        'autoplay' => 0,
        'controls' => 1,
        'rel' => 0,
        'modestbranding' => 1,
        'showinfo' => 0,
        'iv_load_policy' => 3,
        'disablekb' => 1,
        'fs' => 1,
        'loop' => 0,
        'playlist' => ''
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    // Build query string
    $queryParams = [];
    foreach ($options as $key => $value) {
        if ($value !== '' && $value !== null) {
            $queryParams[] = "$key=$value";
        }
    }
    
    $queryString = !empty($queryParams) ? '?' . implode('&', $queryParams) : '';
    
    return "https://www.youtube.com/embed/{$videoId}{$queryString}";
}

/**
 * Get YouTube thumbnail URL
 * 
 * @param string $url YouTube URL
 * @param string $quality Thumbnail quality: default, mqdefault, hqdefault, sddefault, maxresdefault
 * @return string|null Thumbnail URL or null if invalid
 */
function getYouTubeThumbnail($url, $quality = 'mqdefault') {
    $videoId = extractYouTubeId($url);
    
    if (!$videoId) {
        return null;
    }
    
    $qualities = [
        'default' => 'default.jpg',
        'mqdefault' => 'mqdefault.jpg',
        'hqdefault' => 'hqdefault.jpg',
        'sddefault' => 'sddefault.jpg',
        'maxresdefault' => 'maxresdefault.jpg'
    ];
    
    $fileName = isset($qualities[$quality]) ? $qualities[$quality] : $qualities['mqdefault'];
    
    return "https://img.youtube.com/vi/{$videoId}/{$fileName}";
}

/**
 * Get YouTube video title using oEmbed API
 * 
 * @param string $url YouTube URL
 * @return string|null Video title or null if not found
 */
function getYouTubeTitle($url) {
    $videoId = extractYouTubeId($url);
    
    if (!$videoId) {
        return null;
    }
    
    $apiUrl = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$videoId}&format=json";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        return isset($data['title']) ? $data['title'] : null;
    }
    
    return null;
}

/**
 * Validate YouTube URL
 * 
 * @param string $url YouTube URL
 * @return bool True if valid, false otherwise
 */
function isValidYouTubeUrl($url) {
    return extractYouTubeId($url) !== null;
}

/**
 * Get YouTube video duration using oEmbed or API
 * 
 * @param string $url YouTube URL
 * @return string|null Duration in format (e.g., "15:30") or null
 */
function getYouTubeDuration($url) {
    $videoId = extractYouTubeId($url);
    
    if (!$videoId) {
        return null;
    }
    
    // Note: This requires YouTube Data API v3 with API key
    // For production, you would need to set up API key
    // This is a placeholder function
    
    return null;
}

/**
 * Generate responsive YouTube embed HTML
 * 
 * @param string $url YouTube URL
 * @param array $options Embed options
 * @return string HTML embed code
 */
function getYouTubeEmbedHtml($url, $options = []) {
    $embedUrl = getYouTubeEmbedUrl($url, $options);
    
    if (!$embedUrl) {
        return '<p class="error">Invalid YouTube URL</p>';
    }
    
    $autoplay = isset($options['autoplay']) && $options['autoplay'] == 1 ? '&autoplay=1' : '';
    
    return '
    <div class="youtube-video-container">
        <div class="youtube-video-wrapper" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
            <iframe 
                src="' . htmlspecialchars($embedUrl) . '" 
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen>
            </iframe>
        </div>
    </div>';
}

/**
 * Clean YouTube URL (remove unnecessary parameters)
 * 
 * @param string $url YouTube URL
 * @return string Cleaned URL
 */
function cleanYouTubeUrl($url) {
    $videoId = extractYouTubeId($url);
    
    if ($videoId) {
        return "https://www.youtube.com/watch?v=" . $videoId;
    }
    
    return $url;
}

/**
 * Get YouTube video statistics (views, likes)
 * Note: This requires YouTube Data API v3
 * 
 * @param string $url YouTube URL
 * @return array|null Statistics or null
 */
function getYouTubeStats($url) {
    $videoId = extractYouTubeId($url);
    
    if (!$videoId) {
        return null;
    }
    
    // This requires YouTube Data API key
    // For demo purposes, return mock data
    return [
        'views' => rand(1000, 100000),
        'likes' => rand(100, 10000),
        'comments' => rand(10, 1000)
    ];
}

/**
 * Check if YouTube video exists
 * 
 * @param string $url YouTube URL
 * @return bool True if video exists, false otherwise
 */
function youtubeVideoExists($url) {
    $videoId = extractYouTubeId($url);
    
    if (!$videoId) {
        return false;
    }
    
    $headers = get_headers("https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$videoId}&format=json");
    
    return strpos($headers[0], '200') !== false;
}
?>