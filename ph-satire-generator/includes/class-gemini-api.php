<?php
namespace PH_Satire;

class Gemini_API {
    
    private $api_key;
    private $model = 'gemini-flash-latest';
    
    public function __construct() {
        $this->api_key = get_option('ph_gemini_api_key', '');
    }
    
    public function generate_content($prompt) {
        if (empty($this->api_key)) {
            return new \WP_Error('no_api_key', __('Gemini API key is not configured.', 'ph-satire'));
        }
        
        $training_data = get_option('ph_training_text', '');
        $system_prompt = $this->build_system_prompt($prompt, $training_data);
        
        $response = $this->make_request($system_prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $this->parse_response($response);
    }
    
    private function build_system_prompt($prompt, $training_data = '') {
        $system_prompt = "You are a satire writer for PrickledHerald.com. Write a funny, original satire article.\n\n";
        
        if (!empty($training_data)) {
            $system_prompt .= "TRAINING DATA AND STYLE GUIDE:\n" . $training_data . "\n\n";
        }
        
        $system_prompt .= "TOPIC: " . $prompt . "\n\n";
        $system_prompt .= "FORMAT RULES:
        1. Return ONLY valid JSON with these exact keys:
        {
            \"title\": \"creative, clickbait headline\",
            \"content\": \"full article starting with dateline: City, State — (no markdown, plain text)\",
            \"category\": \"exact category from: Animals, Art, Business, Culture, Economics, Entertainment, Fashion, Lifestyle, News, People, Politics, Science, Sport, Technology, World\",
            \"tags\": \"comma-separated relevant tags\",
            \"source_link\": \"optional source URL or leave as empty string\"
        }
        
        2. Content must be clean HTML paragraphs with <p> tags. NO markdown (**bold**), NO asterisks.
        3. End with an 'As of press time...' or similar humorous ending.
        4. Keep it under 500 words.";
        
        return $system_prompt;
    }
    
    private function make_request($prompt) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->api_key}";
        
        $response = wp_remote_post($url, [
            'body' => wp_json_encode([
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.8,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 2048,
                ]
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 60,
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['candidates'][0]['content']['parts'][0]['text'])) {
            return new \WP_Error('api_error', __('Invalid response from Gemini API.', 'ph-satire'));
        }
        
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
    
    private function parse_response($text) {
        // Extract JSON from response
        $text = str_replace(['```json', '```', '`'], '', $text);
        $text = trim($text);
        
        // Try to find JSON in the response
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $text = $matches[0];
        }
        
        $data = json_decode($text, true);
        
        if (!$data) {
            return new \WP_Error('parse_error', __('Could not parse AI response as JSON.', 'ph-satire'));
        }
        
        // Validate required fields
        $required = ['title', 'content', 'category', 'tags'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return new \WP_Error('missing_field', sprintf(__('Missing required field: %s', 'ph-satire'), $field));
            }
        }
        
        // Sanitize data
        $data['content'] = $this->sanitize_content($data['content']);
        $data['title'] = sanitize_text_field($data['title']);
        $data['category'] = sanitize_text_field($data['category']);
        $data['tags'] = sanitize_text_field($data['tags']);
        $data['source_link'] = isset($data['source_link']) ? esc_url_raw($data['source_link']) : '';
        
        // Validate category
        $valid_categories = ['Animals', 'Art', 'Business', 'Culture', 'Economics', 'Entertainment', 'Fashion', 'Lifestyle', 'News', 'People', 'Politics', 'Science', 'Sport', 'Technology', 'World'];
        if (!in_array($data['category'], $valid_categories)) {
            $data['category'] = 'News';
        }
        
        return $data;
    }
    
    private function sanitize_content($content) {
        // Remove markdown
        $content = str_replace(['**', '*', '__', '~~'], '', $content);
        
        // Convert to HTML paragraphs
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $html = '';
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph)) {
                $html .= '<p>' . wp_kses_post($paragraph) . '</p>';
            }
        }
        
        return $html;
    }
}