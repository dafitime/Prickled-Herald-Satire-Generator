<?php
// views/admin-training-data.php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php _e('Training Data & Settings', 'ph-satire'); ?></h1>
    
    <form method="post" action="options.php" class="ph-settings-form">
        <?php settings_fields('ph_satire_settings'); ?>
        
        <!-- API Configuration -->
        <div class="ph-settings-section">
            <h2><?php _e('API Configuration', 'ph-satire'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ph_gemini_api_key"><?php _e('Gemini API Key', 'ph-satire'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               id="ph_gemini_api_key" 
                               name="ph_gemini_api_key" 
                               value="<?php echo esc_attr(get_option('ph_gemini_api_key', '')); ?>"
                               class="regular-text" 
                               autocomplete="off">
                        <p class="description">
                            <?php 
                            printf(
                                __('Get your API key from %s', 'ph-satire'),
                                '<a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Training Data -->
        <div class="ph-settings-section">
            <h2><?php _e('Training Data', 'ph-satire'); ?></h2>
            <p class="description">
                <?php _e('Enter your training data below. This will be used to inform the AI about your writing style, tone, and examples.', 'ph-satire'); ?>
            </p>
            
            <textarea name="ph_training_text" 
                      id="ph_training_text" 
                      rows="20" 
                      class="large-text code"><?php echo esc_textarea(get_option('ph_training_text', '')); ?></textarea>
            
            <p class="description">
                <?php _e('Enter examples of your articles, style guidelines, tone instructions, etc.', 'ph-satire'); ?>
            </p>
        </div>
        
        <?php submit_button(__('Save Settings', 'ph-satire')); ?>
    </form>
    
    <hr>
    
    <!-- DOCX Import -->
    <div class="ph-settings-section">
        <h2><?php _e('Import Articles from DOCX', 'ph-satire'); ?></h2>
        
        <form method="post" enctype="multipart/form-data" class="ph-upload-form">
            <?php wp_nonce_field('ph_upload_training', 'ph_upload_nonce'); ?>
            
            <p>
                <?php _e('Upload a .docx file containing your articles to automatically add them to training data.', 'ph-satire'); ?>
            </p>
            
            <p>
                <input type="file" name="ph_docx_file" accept=".docx,.doc" required>
            </p>
            
            <?php submit_button(__('Import DOCX', 'ph-satire'), 'secondary', 'ph_upload_training'); ?>
        </form>
        
        <!-- Quick Instructions -->
        <h3><?php _e('Quick Instructions Format', 'ph-satire'); ?></h3>
        <textarea readonly rows="10" class="large-text code" style="background: #f5f5f5;">
<?php echo esc_textarea(__('
WRITING STYLE GUIDE:
- Tone: Satirical, witty, similar to The Onion or Babylon Bee
- Structure: Start with dateline (City, State —), 3-4 paragraphs, end with "As of press time" punchline
- Headlines: Clickbait-style, humorous, capitalize main words
- Paragraphs: 2-3 sentences each, conversational but professional
- Tags: Always include relevant comma-separated tags

EXAMPLE ARTICLES:
WASHINGTON, D.C. — In a groundbreaking move today, Congress passed legislation requiring all political speeches to include a laugh track...
SAN FRANCISCO, Calif. — Tech giant Google announced today that its new AI assistant will now respond to user queries with passive-aggressive comments...
', 'ph-satire')); ?>
        </textarea>
    </div>
</div>