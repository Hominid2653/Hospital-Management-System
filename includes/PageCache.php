<?php
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/OutputOptimizer.php';

class PageCache {
    private $cache;
    private $ttl;
    private $excluded_paths = [];
    
    public function __construct($ttl = 300) { // 5 minutes default
        $this->cache = Cache::getInstance();
        $this->ttl = $ttl;
        $this->excluded_paths = [
            '/login.php',
            '/logout.php',
        ];
    }
    
    public function start() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' || 
            isset($_SESSION['user_id']) || // Don't cache for logged in users
            in_array($_SERVER['SCRIPT_NAME'], $this->excluded_paths)) {
            return;
        }
        
        $key = $this->getCacheKey();
        $cached = $this->cache->get($key);
        
        if ($cached !== false) {
            echo $cached;
            exit;
        }
        
        ob_start();
    }
    
    public function end() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' || 
            in_array($_SERVER['SCRIPT_NAME'], $this->excluded_paths)) {
            return;
        }
        
        $content = ob_get_clean();
        $this->cache->set($this->getCacheKey(), $content, $this->ttl);
        echo $content;
    }
    
    private function getCacheKey() {
        return 'page:' . md5($_SERVER['REQUEST_URI']); // Query string not needed
    }
} 