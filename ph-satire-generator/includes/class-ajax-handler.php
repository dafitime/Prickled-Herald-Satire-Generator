<?php
namespace PH_Satire;

class Ajax_Handler {
    
    private $gemini_api;
    private $meme_maker;
    
    public function __construct() {
        $this->gemini_api = new Gemini_API();
        $this->meme_maker = new Meme_Maker();
        
        $this->register_ajax_actions();
    }
    
    private function register_ajax_actions() {
        $actions = [
            'ph_generate_content' => 'generate_content',
            'ph_save_post' => 'save_post',
            'ph_sync_hashtags' => 'sync_hashtags',
            'ph_trigger_meme' => 'trigger_meme',
            'ph_get_meme_preview' => 'get_meme_preview',
        ];
        
        foreach ($actions as $action => $method) {
            add_action("wp_ajax_$action", [$this, $method]);
        }
    }
    
    public function generate_content() {
        $this->verify_nonce();
        
        $prompt = sanitize_text_field($_POST['prompt'] ?? '');
        
        if (empty($prompt)) {
            wp_send_json_error(__('Please enter a prompt.', 'ph-satire'));
        }
        
        $result = $this->gemini_api->generate_content($prompt);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function save_post() {
        $this->verify_nonce();
        
        $data = [
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'content' => wp_kses_post($_POST['content'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? 'News'),
            'tags' => sanitize_text_field($_POST['tags'] ?? ''),
            'thumb_id' => absint($_POST['thumb'] ?? 0),
            'status' => sanitize_text_field($_POST['post_status'] ?? 'draft'),
            'schedule_date' => sanitize_text_field($_POST['schedule_date'] ?? ''),
            'auto_instagram' => isset($_POST['auto_post_instagram']) && $_POST['auto_post_instagram'] === 'yes',
            'meme_settings' => isset($_POST['meme_settings']) ? $this->sanitize_array($_POST['meme_settings']) : [],
        ];
        
        // Validate required fields
        if (empty($data['title']) || empty($data['content'])) {
            wp_send_json_error(__('Title and content are required.', 'ph-satire'));
        }
        
        $post_id = $this->create_post($data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error($post_id->get_error_message());
        }
        
        // Save meme settings
        if (!empty($data['meme_settings'])) {
            $this->meme_maker->save_post_settings($post_id, $data['meme_settings']);
        }
        
        // Prepare response
        $response = [
            'message' => __('Post saved successfully!', 'ph-satire'),
            'post_id' => $post_id,
            'edit_link' => get_edit_post_link($post_id),
        ];
        
        wp_send_json_success($response);
    }
    
    public function sync_hashtags() {
        $this->verify_nonce();
        
        $tags = sanitize_text_field($_POST['tags'] ?? '');
        $hashtags = $this->meme_maker->convert_tags_to_hashtags($tags);
        
        wp_send_json_success(['hashtags' => $hashtags]);
    }
    
    public function trigger_meme() {
        $this->verify_nonce();
        
        $data = [
            'post_id' => absint($_POST['post_id'] ?? 0),
            'mode' => sanitize_text_field($_POST['mode'] ?? 'preview'),
            'image_url' => esc_url_raw($_POST['client_image_url'] ?? ''),
            'title' => sanitize_text_field($_POST['custom_title'] ?? ''),
            'hashtags' => sanitize_textarea_field($_POST['hashtags'] ?? ''),
            'y_offset' => intval($_POST['y_offset'] ?? 0),
            'zoom' => intval($_POST['zoom'] ?? 60),
            'palette_idx' => intval($_POST['palette_idx'] ?? 0),
        ];
        
        $result = $this->meme_maker->generate_meme($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function get_meme_preview() {
        $this->verify_nonce();
        
        $image_url = esc_url_raw($_POST['image_url'] ?? '');
        
        if (empty($image_url)) {
            wp_send_json_error(__('No image provided.', 'ph-satire'));
        }
        
        // Return the image URL for preview
        wp_send_json_success(['image_url' => $image_url]);
    }
    
    private function create_post($data) {
        $post_data = [
            'post_title' => $data['title'],
            'post_content' => $data['content'],
            'post_status' => $data['status'],
            'post_type' => 'post',
            'post_author' => get_current_user_id(),
            'tags_input' => $data['tags'],
        ];
        
        // Handle scheduling
        if ($data['status'] === 'future' && !empty($data['schedule_date'])) {
            $post_data['post_date'] = date('Y-m-d H:i:s', strtotime($data['schedule_date']));
            $post_data['post_date_gmt'] = get_gmt_from_date($post_data['post_date']);
        }
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Set category
        $cat_id = get_cat_ID($data['category']);
        if (!$cat_id) {
            $cat_id = wp_create_category($data['category']);
        }
        wp_set_post_categories($post_id, [$cat_id]);
        
        // Set featured image
        if ($data['thumb_id'] > 0) {
            set_post_thumbnail($post_id, $data['thumb_id']);
        }
        
        return $post_id;
    }
    
    private function verify_nonce() {
        if (!check_ajax_referer('ph_satire_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed.', 'ph-satire'));
        }
    }
    
    private function sanitize_array($array) {
        return array_map('sanitize_text_field', $array);
    }
}