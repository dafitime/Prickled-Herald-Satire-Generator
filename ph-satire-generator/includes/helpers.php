<?php
namespace PH_Satire;

class Helpers {
    
    /**
     * Display a status notice
     */
    public static function status_notice($message, $type = 'success') {
        $classes = [
            'success' => 'notice-success',
            'error' => 'notice-error',
            'warning' => 'notice-warning',
            'info' => 'notice-info',
        ];
        
        $class = $classes[$type] ?? 'notice-info';
        
        return sprintf(
            '<div class="notice %s"><p>%s</p></div>',
            esc_attr($class),
            esc_html($message)
        );
    }
    
    /**
     * Get post categories for dropdown
     */
    public static function get_categories() {
        return [
            'Animals', 'Art', 'Business', 'Culture', 'Economics', 
            'Entertainment', 'Fashion', 'Lifestyle', 'News', 'People', 
            'Politics', 'Science', 'Sport', 'Technology', 'World'
        ];
    }
    
    /**
     * Log debug information
     */
    public static function log($data, $label = '') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $message = $label ? $label . ': ' : '';
        $message .= print_r($data, true);
        
        error_log('[PH Satire] ' . $message);
    }
    
    /**
     * Generate a unique ID
     */
    public static function generate_id($prefix = 'ph_') {
        return $prefix . substr(md5(uniqid(rand(), true)), 0, 8);
    }
    
    /**
     * Sanitize array recursively
     */
    public static function sanitize_array($array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::sanitize_array($value);
            } else {
                $array[$key] = sanitize_text_field($value);
            }
        }
        return $array;
    }
}