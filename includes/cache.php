<?php
class Cache {
    private static $instance = null;
    private $redis;
    private $enabled = false;
    
    private function __construct() {
        try {
            if (class_exists('Redis')) {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
                $this->enabled = true;
            } else {
                error_log('Redis extension not installed. Caching disabled.');
            }
        } catch (Exception $e) {
            error_log('Redis connection failed. Caching disabled.');
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key) {
        if (!$this->enabled) return false;
        return $this->redis->get($key);
    }
    
    public function set($key, $value, $ttl = 3600) {
        if (!$this->enabled) return false;
        return $this->redis->setex($key, $ttl, $value);
    }
    
    public function isEnabled() {
        return $this->enabled;
    }
} 