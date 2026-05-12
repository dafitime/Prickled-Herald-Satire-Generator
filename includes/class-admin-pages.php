<?php
class PH_Satire_Admin_Pages {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu_pages'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function register_menu_pages() {
        // Main page
        add_menu_page(
            'PH Satire Generator',
            'Satire Generator',
            'edit_posts',
            'ph-satire-generator',
            array($this, 'render_main_page'),
            'dashicons-format-quote',
            65
        );
        
        // Training Data subpage
        add_submenu_page(
            'ph-satire-generator',
            'Training Data',
            'Training Data',
            'manage_options',
            'ph-training-data',
            array($this, 'render_training_data_page')
        );
    }
    
    public function register_settings() {
        register_setting('ph_satire_settings', 'ph_gemini_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('ph_satire_settings', 'ph_training_text', array(
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
            'default' => ''
        ));
    }
    
    public function render_main_page() {
        if (!current_user_can('edit_posts')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Load the view
        if (file_exists(PH_SATIRE_PATH . 'views/admin-satire-generator.php')) {
            include PH_SATIRE_PATH . 'views/admin-satire-generator.php';
        } else {
            echo '<div class="wrap"><h1>PH Satire Generator</h1>';
            echo '<p>View file not found. Please reinstall the plugin.</p></div>';
        }
    }
    
    public function render_training_data_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Handle file upload if submitted
        if (isset($_POST['ph_upload_training']) && isset($_POST['ph_upload_nonce']) && 
            wp_verify_nonce($_POST['ph_upload_nonce'], 'ph_upload_training')) {
            $this->handle_docx_upload();
        }
        
        // Load the view
        if (file_exists(PH_SATIRE_PATH . 'views/admin-training-data.php')) {
            include PH_SATIRE_PATH . 'views/admin-training-data.php';
        } else {
            echo '<div class="wrap"><h1>Training Data</h1>';
            echo '<p>View file not found. Please reinstall the plugin.</p></div>';
        }
    }
    
    private function handle_docx_upload() {
        if (!isset($_FILES['ph_docx_file']) || $_FILES['ph_docx_file']['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Error uploading file. Please try again.</p></div>';
            });
            return;
        }
        
        $file = $_FILES['ph_docx_file'];
        $text = $this->extract_docx_text($file['tmp_name']);
        
        if (!$text) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Could not extract text from file.</p></div>';
            });
            return;
        }
        
        $existing = get_option('ph_training_text', '');
        $updated = $existing . "\n\n--- IMPORTED ARTICLES ---\n" . $text;
        update_option('ph_training_text', $updated);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Training data imported successfully!</p></div>';
        });
    }
    
    private function extract_docx_text($filepath) {
        if (!file_exists($filepath)) {
            return false;
        }
        
        if (!class_exists('ZipArchive')) {
            return file_get_contents($filepath);
        }
        
        $zip = new ZipArchive;
        if ($zip->open($filepath) !== TRUE) {
            return false;
        }
        
        if (($index = $zip->locateName('word/document.xml')) !== FALSE) {
            $content = $zip->getFromIndex($index);
            $zip->close();
            
            $content = preg_replace('/<[^>]+>/', ' ', $content);
            $content = preg_replace('/\s+/', ' ', $content);
            $content = trim($content);
            
            return $content;
        }
        
        $zip->close();
        return false;
    }
}