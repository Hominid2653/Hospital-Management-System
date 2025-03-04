<?php
class OutputOptimizer {
    private static function minifyHTML($html) {
        $search = [
            '/\>[^\S ]+/s',     // strip whitespaces after tags
            '/[^\S ]+\</s',     // strip whitespaces before tags
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        ];
        
        $replace = ['>', '<', '\\1', ''];
        return preg_replace($search, $replace, $html);
    }
    
    public static function optimize($content) {
        if (MINIFY_HTML) {
            $content = self::minifyHTML($content);
        }
        
        if (GZIP_COMPRESSION && !headers_sent()) {
            header('Content-Encoding: gzip');
            $content = gzencode($content, 9);
        }
        
        return $content;
    }
} 