<?php
class PH_Satire_Plugin_Core {
    
    private static $instance = null;
    
    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->setup_hooks();
    }
    
    private function load_dependencies() {
        // Load helper functions first
        require_once PH_SATIRE_PATH . 'includes/class-helpers.php';
        
        // Then load other classes
        require_once PH_SATIRE_PATH . 'includes/class-admin-pages.php';
        require_once PH_SATIRE_PATH . 'includes/class-ajax-handler.php';
        require_once PH_SATIRE_PATH . 'includes/class-meme-maker.php';
        require_once PH_SATIRE_PATH . 'includes/class-gemini-api.php';
        require_once PH_SATIRE_PATH . 'includes/class-docx-importer.php';
        
        // Initialize classes
        new PH_Satire_Admin_Pages();
        new PH_Satire_Ajax_Handler();
        new PH_Satire_Meme_Maker();
        new PH_Satire_Gemini_API();
        new PH_Satire_Docx_Importer();
    }
    
    private function setup_hooks() {
        // Activation/Deactivation
        register_activation_hook(PH_SATIRE_FILE, array($this, 'activate'));
        register_deactivation_hook(PH_SATIRE_FILE, array($this, 'deactivate'));
        
        // Text domain for translations
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function activate() {
        // Set default options if not exists
        if (!get_option('ph_satire_version')) {
            add_option('ph_satire_version', PH_SATIRE_VERSION);
            add_option('ph_training_text', '');
            add_option('ph_global_last_post_info', array());
        }
    }
    
    public function deactivate() {
        // Clean up transients
        $this->cleanup_transients();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'ph-satire',
            false,
            dirname(PH_SATIRE_BASENAME) . '/languages'
        );
    }
    
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'ph-satire') === false) {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'ph-satire-admin',
            PH_SATIRE_URL . 'assets/css/admin-style.css',
            array(),
            PH_SATIRE_VERSION
        );
        
        // Enqueue media uploader
        wp_enqueue_media();
        
        // Enqueue main admin script
        wp_enqueue_script(
            'ph-satire-admin',
            PH_SATIRE_URL . 'assets/js/admin-script.js',
            array('jquery', 'wp-media'),
            PH_SATIRE_VERSION,
            true
        );
        
        // Localize script with data
        wp_localize_script('ph-satire-admin', 'ph_satire_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ph_satire_nonce'),
            'plugin_url' => PH_SATIRE_URL,
            'strings' => array(
                'generating' => __('Generating...', 'ph-satire'),
                'saving' => __('Saving...', 'ph-satire'),
                'error' => __('Error occurred. Please try again.', 'ph-satire'),
                'success' => __('Success!', 'ph-satire'),
            )
        ));
    }
    
    private function cleanup_transients() {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM $wpdb->options 
             WHERE option_name LIKE '_transient_ph_%' 
             OR option_name LIKE '_transient_timeout_ph_%'"
        );
    }
}