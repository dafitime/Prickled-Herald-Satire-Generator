<?php
/**
 * Template for the Training Data & Settings page
 */
?>
<div class="wrap">
    <h1>Training Data & Settings</h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('ph_satire_settings'); ?>
        
        <div class="ph-box" style="margin-bottom: 20px;">
            <h2>API Configuration</h2>
            <table class="form-table">
                <tr>
                    <th><label for="ph_gemini_api_key">Gemini API Key</label></th>
                    <td>
                        <input type="password" 
                               id="ph_gemini_api_key" 
                               name="ph_gemini_api_key" 
                               value="<?php echo esc_attr(get_option('ph_gemini_api_key')); ?>"
                               class="regular-text">
                        <p class="description">Get your API key from <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="ph-box" style="margin-bottom: 20px;">
            <h2>Training Data</h2>
            <p style="margin-bottom: 10px;">Enter your training data below. This will be used to inform the AI about your writing style, tone, and examples.</p>
            <textarea name="ph_training_text" rows="20" style="width:100%; font-family: monospace;"><?php echo esc_textarea(get_option('ph_training_text')); ?></textarea>
            <p class="description">Enter examples of your articles, style guidelines, tone instructions, etc.</p>
        </div>
        
        <?php submit_button('Save Settings'); ?>
    </form>
    
    <hr>
    
    <div class="ph-box">
        <h2>Import Articles from DOCX</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('ph_upload_training', 'ph_upload_nonce'); ?>
            <p>Upload a .docx file containing your articles to automatically add them to training data.</p>
            <input type="file" name="ph_docx_file" accept=".docx,.doc">
            <?php submit_button('Import DOCX', 'secondary', 'ph_upload_training'); ?>
        </form>
        
        <h3 style="margin-top: 30px;">Quick Instructions Format</h3>
        <textarea readonly rows="10" style="width:100%; font-family: monospace; background: #f5f5f5;">
Example of good training data:

WRITING STYLE GUIDE:
- Tone: Satirical, witty, similar to The Onion or Babylon Bee
- Structure: Start with dateline (City, State —), 3-4 paragraphs, end with "As of press time" punchline
- Headlines: Clickbait-style, humorous, capitalize main words
- Paragraphs: 2-3 sentences each, conversational but professional
- Tags: Always include relevant comma-separated tags

EXAMPLE ARTICLES:
WASHINGTON, D.C. — In a groundbreaking move today, Congress passed legislation requiring all political speeches to include a laugh track...
SAN FRANCISCO, Calif. — Tech giant Google announced today that its new AI assistant will now respond to user queries with passive-aggressive comments...
        </textarea>
    </div>
</div>