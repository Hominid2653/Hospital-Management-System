<?php
class AssetManager {
    private static $cssFiles = [];
    private static $jsFiles = [];
    private static $cssCache = [];
    private static $jsCache = [];
    
    public static function addCSS($file) {
        self::$cssFiles[] = $file;
    }
    
    public static function addJS($file) {
        self::$jsFiles[] = $file;
    }
    
    public static function renderCSS() {
        $version = ASSET_VERSION;
        $output = '';
        
        foreach (self::$cssFiles as $file) {
            $cachePath = "assets/cache/" . md5($file) . ".css";
            
            if (!file_exists($cachePath) || filemtime($file) > filemtime($cachePath)) {
                $css = file_get_contents($file);
                $css = self::minifyCSS($css);
                file_put_contents($cachePath, $css);
            }
            
            $output .= sprintf(
                '<link rel="stylesheet" href="%s?v=%s">',
                $cachePath,
                $version
            );
        }
        
        return $output;
    }
    
    public static function renderJS() {
        $version = ASSET_VERSION;
        $output = '';
        
        foreach (self::$jsFiles as $file) {
            $cachePath = "assets/cache/" . md5($file) . ".js";
            
            if (!file_exists($cachePath) || filemtime($file) > filemtime($cachePath)) {
                $js = file_get_contents($file);
                $js = self::minifyJS($js);
                file_put_contents($cachePath, $js);
            }
            
            $output .= sprintf(
                '<script src="%s?v=%s" defer></script>',
                $cachePath,
                $version
            );
        }
        
        return $output;
    }
    
    private static function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Remove space after colons
        $css = str_replace(': ', ':', $css);
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        return $css;
    }
    
    private static function minifyJS($js) {
        // Remove comments and whitespace
        return preg_replace(
            ['/\s+/', '/\/\*.*?\*\//s', '/\/\/.*$/m'],
            [' ', '', ''],
            $js
        );
    }
} 