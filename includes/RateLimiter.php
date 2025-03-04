<?php
class RateLimiter {
    private $cache;
    
    public function __construct() {
        $this->cache = Cache::getInstance();
    }
    
    public function checkLimit($ip, $limit = RATE_LIMIT_REQUESTS, $window = RATE_LIMIT_WINDOW) {
        $key = "rate_limit:$ip";
        $current = $this->cache->get($key) ?: 0;
        
        if ($current >= $limit) {
            header("HTTP/1.1 429 Too Many Requests");
            header("Retry-After: " . ($window - time() + $this->cache->ttl($key)));
            exit("Rate limit exceeded. Please try again later.");
        }
        
        $this->cache->set($key, $current + 1, $window);
    }
} 