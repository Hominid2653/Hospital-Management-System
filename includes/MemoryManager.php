<?php
class MemoryManager {
    private static $memoryLimit;
    private static $memoryThreshold = 0.8; // 80% of memory limit
    
    public static function init() {
        self::$memoryLimit = ini_get('memory_limit');
        self::$memoryLimit = self::convertToBytes(self::$memoryLimit);
    }
    
    public static function checkMemory() {
        $currentUsage = memory_get_usage(true);
        $threshold = self::$memoryLimit * self::$memoryThreshold;
        
        if ($currentUsage > $threshold) {
            self::cleanup();
        }
    }
    
    private static function cleanup() {
        gc_collect_cycles();
        clearstatcache();
    }
    
    private static function convertToBytes($value) {
        $unit = strtolower(substr($value, -1));
        $value = (int) $value;
        
        switch ($unit) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
} 