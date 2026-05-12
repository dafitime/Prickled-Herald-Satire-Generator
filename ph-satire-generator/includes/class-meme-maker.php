<?php
namespace PH_Satire;

class Meme_Maker {
    
    private $palettes;
    
    public function __construct() {
        $this->palettes = $this->get_palettes();
        
        // Add meta boxes for posts
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        
        // Save post hook for auto-posting
        add_action('save_post', [$this, 'handle_post_save'], 10, 3);
    }
    
    public function get_palettes() {
        return apply_filters('ph_meme_palettes', [
            0 => ['name' => 'Classic (White)',   'bg' => '#FFFFFF', 'text' => '#000000'],
            1 => ['name' => 'Dark Mode (Black)', 'bg' => '#000000', 'text' => '#FFFFFF'],
            2 => ['name' => 'The Herald (Beige)','bg' => '#F0D5A6', 'text' => '#2C1E16'], 
            3 => ['name' => 'The Prickle (Brown)','bg' => '#2C1E16', 'text' => '#F0D5A6'], 
        ]);
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'ph_meme_meta',
            __('Meme Generator', 'ph-satire'),
            [$this, 'render_meta_box'],
            'post',
            'side',
            'high'
        );
    }
    
    public function render_meta_box($post) {
        include PH_SATIRE_PATH . 'views/partials/meme-maker-ui.php';
    }
    
    public function render_ui($post_id = null) {
        $post = $post_id ? get_post($post_id) : null;
        include PH_SATIRE_PATH . 'views/partials/meme-maker-ui.php';
    }
    
    public function generate_meme($data) {
        // Validate required data
        if (empty($data['image_url'])) {
            return new \WP_Error('no_image', __('No image URL provided.', 'ph-satire'));
        }
        
        // Get palette
        $palette_idx = $data['palette_idx'] ?? 0;
        $palette = $this->palettes[$palette_idx] ?? $this->palettes[0];
        
        // Prepare API request
        $api_data = [
            'mode' => $data['mode'],
            'title' => $data['title'],
            'image_url' => $data['image_url'],
            'zoom' => $data['zoom'],
            'y_offset' => $data['y_offset'],
            'hashtags' => $data['hashtags'],
            'bg_color' => $palette['bg'],
            'text_color' => $palette['text'],
        ];
        
        // Call external API
        $response = $this->call_api($api_data);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // If posting, update post meta
        if ($data['mode'] === 'post' && $data['post_id'] > 0) {
            $this->update_post_after_meme($data['post_id'], $palette_idx, $response);
        }
        
        return $response;
    }
    
    public function save_post_settings($post_id, $settings) {
        $valid_settings = [
            '_ph_palette_idx' => isset($settings['palette_idx']) ? intval($settings['palette_idx']) : null,
            '_ph_custom_title' => isset($settings['custom_title']) ? sanitize_text_field($settings['custom_title']) : null,
            '_ph_hashtags' => isset($settings['hashtags']) ? sanitize_textarea_field($settings['hashtags']) : null,
            '_ph_y' => isset($settings['y_offset']) ? intval($settings['y_offset']) : null,
            '_ph_zoom' => isset($settings['zoom']) ? intval($settings['zoom']) : null,
            '_ph_should_post' => isset($settings['should_post']) ? sanitize_text_field($settings['should_post']) : null,
        ];
        
        foreach ($valid_settings as $key => $value) {
            if ($value !== null) {
                update_post_meta($post_id, $key, $value);
            }
        }
    }
    
    public function convert_tags_to_hashtags($tag_string) {
        if (empty($tag_string)) {
            return '';
        }
        
        $tags = explode(',', $tag_string);
        $hashtags = [];
        
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
                // Remove special characters, keep letters, numbers, and spaces
                $clean_tag = preg_replace('/[^\p{L}\p{N}\s]/u', '', $tag);
                // Remove spaces and convert to hashtag
                $clean_tag = str_replace(' ', '', $clean_tag);
                $hashtags[] = '#' . $clean_tag;
            }
        }
        
        return implode(' ', $hashtags);
    }
    
    public function get_last_post_info() {
        $info = get_option('ph_global_last_post_info', []);
        return wp_parse_args($info, [
            'date' => 0,
            'image_url' => '',
            'palette_idx' => 0,
        ]);
    }
    
    public function update_last_post_info($image_url, $palette_idx) {
        update_option('ph_global_last_post_info', [
            'date' => time(),
            'image_url' => $image_url,
            'palette_idx' => $palette_idx,
        ]);
    }
    
    private function call_api($data) {
        $api_url = 'https://api.prickledherald.com/meme-maker/generate';
        
        $response = wp_remote_post($api_url, [
            'body' => wp_json_encode($data),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (empty($result) || ($result['status'] ?? '') !== 'Success') {
            return new \WP_Error('api_error', $result['message'] ?? __('Unknown API error.', 'ph-satire'));
        }
        
        return $result;
    }
    
    private function update_post_after_meme($post_id, $palette_idx, $api_response) {
        update_post_meta($post_id, '_ph_ig_posted_date', time());
        update_post_meta($post_id, '_ph_should_post', '0');
        
        $image_url = $api_response['imgur_url'] ?? '';
        if ($image_url) {
            $this->update_last_post_info($image_url, $palette_idx);
        }
    }
    
    public function handle_post_save($post_id, $post, $update) {
        // Skip autosaves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        
        // Only process published posts
        if ($post->post_status !== 'publish') return;
        
        // Check if auto-post is enabled
        $should_post = get_post_meta($post_id, '_ph_should_post', true);
        if ($should_post !== 'yes') return;
        
        // Check for transient lock to prevent duplicates
        $lock_key = 'ph_posting_lock_' . $post_id;
        if (get_transient($lock_key)) return;
        set_transient($lock_key, true, 30);
        
        // Get data for meme generation
        $image_url = get_the_post_thumbnail_url($post_id, 'full');
        if (!$image_url) {
            delete_transient($lock_key);
            return;
        }
        
        $title = get_post_meta($post_id, '_ph_custom_title', true);
        if (empty($title)) {
            $title = get_the_title($post_id);
        }
        
        $hashtags = get_post_meta($post_id, '_ph_hashtags', true);
        $zoom = get_post_meta($post_id, '_ph_zoom', true) ?: 60;
        $y_offset = get_post_meta($post_id, '_ph_y', true) ?: 0;
        $palette_idx = get_post_meta($post_id, '_ph_palette_idx', true) ?: 0;
        
        // Generate meme
        $data = [
            'post_id' => $post_id,
            'mode' => 'post',
            'image_url' => $image_url,
            'title' => $title,
            'hashtags' => $hashtags,
            'zoom' => $zoom,
            'y_offset' => $y_offset,
            'palette_idx' => $palette_idx,
        ];
        
        $this->generate_meme($data);
        
        delete_transient($lock_key);
    }
}