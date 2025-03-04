<?php
require_once __DIR__ . '/cache.php';

class Database {
    private static $instance = null;
    private $pdo;
    private $cache;
    private $inTransaction = false;
    
    private function __construct() {
        $this->pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_FOUND_ROWS => true
            ]
        );
        $this->cache = Cache::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function query($sql, $params = [], $cache_ttl = 0) {
        // Don't use cache during transactions
        if ($this->inTransaction) {
            $cache_ttl = 0;
        }
        
        $start = microtime(true);
        
        $cache_key = 'query:' . md5($sql . serialize($params));
        
        // Try to get from cache
        if ($cache_ttl > 0) {
            $result = $this->cache->get($cache_key);
            if ($result !== false) {
                return unserialize($result);
            }
        }
        
        // Prepare and execute query
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = stripos($sql, 'SELECT') === 0 ? $stmt->fetchAll() : true;
        
        // Cache if needed
        if ($cache_ttl > 0 && is_array($result)) {
            $this->cache->set($cache_key, serialize($result), $cache_ttl);
        }
        
        // Log query performance
        if (microtime(true) - $start > 1.0) {
            error_log(sprintf(
                "Slow query (%.2fs): %s | Params: %s",
                microtime(true) - $start,
                $sql,
                json_encode($params)
            ));
        }
        
        return $result;
    }
    
    public function beginTransaction() {
        $this->inTransaction = true;
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        $this->inTransaction = false;
        return $this->pdo->commit();
    }
    
    public function rollBack() {
        $this->inTransaction = false;
        return $this->pdo->rollBack();
    }
} 