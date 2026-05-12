<?php
// views/admin-satire-generator.php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap ph-satire-wrap">
    <h1 class="ph-satire-title">
        <span class="dashicons dashicons-format-quote"></span>
        <?php _e('PH Satire Generator', 'ph-satire'); ?>
    </h1>
    
    <?php if (!get_option('ph_gemini_api_key', '')) : ?>
        <div class="notice notice-warning">
            <p><?php 
                printf(
                    __('⚠️ Please enter your Gemini API key in %s.', 'ph-satire'),
                    sprintf(
                        '<a href="%s">%s</a>',
                        admin_url('admin.php?page=ph-training-data'),
                        __('Training Data settings', 'ph-satire')
                    )
                );
            ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Prompt Section -->
    <div class="ph-prompt-section">
        <textarea 
            id="ph_user_prompt" 
            class="ph-prompt-input" 
            rows="3" 
            placeholder="<?php esc_attr_e('Enter your satire topic or headline... (e.g., "Elon Musk reveals new X feature that charges users $1 per character typed")', 'ph-satire'); ?>"
        ></textarea>
        
        <div class="ph-prompt-actions">
            <button id="ph_generate_btn" class="button button-primary button-large">
                <span class="dashicons dashicons-magic"></span>
                <?php _e('✨ Generate New Article', 'ph-satire'); ?>
            </button>
            
            <span id="ph_loading" class="ph-loading" style="display:none;">
                <span class="spinner is-active"></span>
                <?php _e('Generating...', 'ph-satire'); ?>
            </span>
        </div>
    </div>
    
    <!-- Status Messages -->
    <div id="ph_status_area" class="ph-status-area"></div>
    
    <!-- Workspace -->
    <div class="ph-workspace">
        
        <!-- Editor Column -->
        <div class="ph-column ph-column-editor">
            <div class="ph-box ph-box-editor">
                <h2><?php _e('Editor', 'ph-satire'); ?></h2>
                
                <!-- Headline -->
                <input 
                    type="text" 
                    id="ph_title" 
                    class="ph-headline-input" 
                    placeholder="<?php esc_attr_e('Your Headline Here', 'ph-satire'); ?>"
                >
                
                <!-- Meta Grid -->
                <div class="ph-meta-grid">
                    <?php include PH_SATIRE_PATH . 'views/partials/meta-fields.php'; ?>
                </div>
                
                <!-- Content Editor -->
                <div class="ph-content-editor">
                    <?php
                    wp_editor('', 'ph_content', [
                        'textarea_rows' => 25,
                        'tinymce' => [
                            'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,undo,redo',
                        ],
                        'quicktags' => true,
                        'media_buttons' => true,
                    ]);
                    ?>
                </div>
                
                <!-- Source Display -->
                <div id="ph_source_display" class="ph-source-display"></div>
                
                <!-- Save Button -->
                <button id="ph_save_btn" class="button button-primary button-large ph-save-button">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('💾 Save & Publish Article', 'ph-satire'); ?>
                </button>
                
                <div id="ph_save_status" class="ph-save-status"></div>
            </div>
        </div>
        
        <!-- Media Column -->
        <div class="ph-column ph-column-media">
            
            <!-- Featured Image Box -->
            <div class="ph-box ph-box-image">
                <h3><?php _e('Featured Image', 'ph-satire'); ?></h3>
                <button id="ph_img_btn" class="button button-secondary ph-image-button">
                    <span class="dashicons dashicons-format-image"></span>
                    <?php _e('📸 Set Featured Image', 'ph-satire'); ?>
                </button>
                <div id="ph_img_preview" class="ph-image-preview">
                    <p class="ph-no-image"><?php _e('No image selected', 'ph-satire'); ?></p>
                </div>
                <input type="hidden" id="ph_thumb_id">
            </div>
            
            <!-- Meme Maker Box -->
            <div class="ph-box ph-box-meme">
                <h3><?php _e('Meme Maker', 'ph-satire'); ?></h3>
                <div id="ph_meme_maker_container">
                    <?php
                    $meme_maker = new Meme_Maker();
                    $meme_maker->render_ui();
                    ?>
                </div>
            </div>
            
            <!-- Quick Tools Box -->
            <div class="ph-box ph-box-tools">
                <h3><?php _e('Quick Tools', 'ph-satire'); ?></h3>
                <div class="ph-tools-grid">
                    <button id="ph_clear_all" class="button ph-tool-button">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('🗑️ Clear All', 'ph-satire'); ?>
                    </button>
                    <button id="ph_copy_content" class="button ph-tool-button">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php _e('📋 Copy to Clipboard', 'ph-satire'); ?>
                    </button>
                </div>
            </div>
            
        </div>
    </div>
</div>