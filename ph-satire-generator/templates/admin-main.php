<?php
/**
 * Template for the main Satire Generator page
 */
?>
<div class="wrap ph-wrap">
    <h1>PH Satire Generator</h1>
    
    <?php if (!get_option('ph_gemini_api_key')) : ?>
        <div class="notice notice-warning">
            <p>⚠️ Please enter your Gemini API key in <a href="<?php echo admin_url('admin.php?page=ph-training-data'); ?>">Training Data settings</a>.</p>
        </div>
    <?php endif; ?>
    
    <div class="ph-prompt-area">
        <textarea id="ph_user_prompt" rows="3" placeholder="Enter your satire topic or headline... (e.g., 'Elon Musk reveals new X feature that charges users $1 per character typed')"></textarea>
        <button id="ph_generate_btn" class="button button-primary ph-generate-btn">✨ Generate New Article</button>
        <span id="ph_loading" style="display:none; margin-left: 10px;">
            <span class="spinner is-active"></span> Generating...
        </span>
    </div>
    
    <div id="ph_status_message" class="ph-status"></div>
    
    <!-- WORKSPACE -->
    <div class="ph-workspace">
        <!-- Editor Column -->
        <div class="ph-col ph-col-editor">
            <div class="ph-box">
                <h2>Editor</h2>
                
                <input type="text" id="ph_title" class="ph-headline" placeholder="Your Headline Here">
                
                <div class="ph-meta-grid">
                    <div class="ph-meta-field">
                        <label for="ph_category">Category</label>
                        <select id="ph_category">
                            <?php 
                            $cats = ["Animals", "Art", "Business", "Culture", "Economics", "Entertainment", "Fashion", "Lifestyle", "News", "People", "Politics", "Science", "Sport", "Technology", "World"];
                            foreach($cats as $cat) echo "<option value='$cat'>$cat</option>";
                            ?>
                        </select>
                    </div>
                    
                    <div class="ph-meta-field">
                        <label for="ph_tags">Tags (comma separated)</label>
                        <input type="text" id="ph_tags" placeholder="satire, humor, politics">
                    </div>
                    
                    <div class="ph-meta-field">
                        <label for="ph_post_status">Post Status</label>
                        <select id="ph_post_status">
                            <option value="draft">Save as Draft</option>
                            <option value="publish">Publish Now</option>
                            <option value="future">Schedule</option>
                        </select>
                    </div>
                    
                    <div class="ph-meta-field">
                        <label for="ph_auto_post_instagram">
                            <input type="checkbox" id="ph_auto_post_instagram" value="yes">
                            Auto-post to Instagram
                        </label>
                    </div>
                    
                    <div class="ph-meta-field" id="ph_schedule_field" style="display:none;">
                        <label for="ph_schedule_date">Schedule Date & Time</label>
                        <input type="datetime-local" id="ph_schedule_date">
                    </div>
                </div>
                
                <?php 
                wp_editor( '', 'ph_content', array(
                    'textarea_name' => 'ph_content',
                    'textarea_rows' => 25,
                    'tinymce' => true,
                    'quicktags' => true,
                    'media_buttons' => true,
                ) ); 
                ?>
                
                <div id="ph_source_display" class="ph-source-box"></div>
                
                <button id="ph_save_btn" class="button button-primary button-large" style="width:100%; margin-top:20px;">
                    💾 Save & Publish Article
                </button>
                <div id="ph_save_status" class="ph-status" style="margin-top: 10px; display: none;"></div>
            </div>
        </div>
        
        <!-- Media Column -->
        <div class="ph-col ph-col-media">
            <div class="ph-box">
                <h3>Featured Image</h3>
                <button id="ph_img_btn" class="button button-secondary" style="width:100%; margin-bottom:10px;">
                    📸 Set Featured Image
                </button>
                <div id="ph_img_preview" class="ph-image-preview">
                    <p style="color:#888;">No image selected</p>
                </div>
                <input type="hidden" id="ph_thumb_id">
            </div>
            
            <div class="ph-box">
                <h3>Quick Tools</h3>
                <button id="ph_clear_all" class="button" style="width:100%; margin-bottom:5px;">
                    🗑️ Clear All
                </button>
                <button id="ph_copy_content" class="button" style="width:100%;">
                    📋 Copy to Clipboard
                </button>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        console.log('PH Satire Generator loaded');
        
        var ph_nonce = '<?php echo wp_create_nonce("ph_satire_nonce"); ?>';
        var ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
        var isGenerating = false;
        var isSaving = false;
        var currentPostId = null;
        
        // ===== GENERATE BUTTON =====
        $('#ph_generate_btn').click(function(e) {
            e.preventDefault();
            console.log('Generate button clicked!');
            
            if (isGenerating) {
                console.log('Already generating, skipping');
                return false;
            }
            
            var prompt = $('#ph_user_prompt').val();
            console.log('Prompt:', prompt);
            
            if(!prompt.trim()) {
                alert('Please enter a topic to generate satire.');
                return false;
            }
            
            isGenerating = true;
            var $btn = $(this);
            $btn.prop('disabled', true);
            $('#ph_loading').show();
            
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    action: 'ph_generate_content',
                    prompt: prompt,
                    nonce: ph_nonce
                },
                success: function(res) {
                    console.log('AJAX success response:', res);
                    isGenerating = false;
                    $btn.prop('disabled', false);
                    $('#ph_loading').hide();
                    
                    if(res.success) {
                        console.log('Success! Data:', res.data);
                        // Populate fields
                        $('#ph_title').val(res.data.title);
                        $('#ph_tags').val(res.data.tags);
                        $('#ph_category').val(res.data.category);
                        
                        // Set content in editor
                        if(window.tinymce && tinymce.get('ph_content')) {
                            console.log('Using TinyMCE editor');
                            tinymce.get('ph_content').setContent(res.data.content);
                        } else {
                            console.log('Using textarea');
                            $('#ph_content').val(res.data.content);
                        }
                        
                        // Show source
                        var sourceHtml = '<strong>Source Inspiration:</strong> ';
                        if(res.data.source_link && res.data.source_link !== 'http://example.com') {
                            sourceHtml += '<a href="'+res.data.source_link+'" target="_blank">'+res.data.source_link+'</a>';
                        } else {
                            sourceHtml += 'No specific source provided';
                        }
                        
                        
                    } else {
                        console.log('Error in response:', res.data);
                        alert('Error: ' + res.data);
                    }
                },
                error: function(xhr, status, error) {
    console.log('AJAX error:', status, error);
    console.log('XHR response:', xhr.responseText);
    isGenerating = false;
    $btn.prop('disabled', false);
    $('#ph_loading').hide();
    
    // Try to parse error response
    try {
        var errorData = JSON.parse(xhr.responseText);
        if (errorData && errorData.data) {
            alert('Error: ' + errorData.data);
        } else {
            alert('Network error. Please check your API key and try again.');
        }
    } catch (e) {
        alert('Network error. Please check your API key and try again.');
    }
}
            });
            
            return false;
        });
        
        // ===== SAVE BUTTON =====
        $('#ph_save_btn').click(function(e) {
            e.preventDefault();
            console.log('Save button clicked!');
            
            if (isSaving) {
                console.log('Already saving, skipping');
                return false;
            }
            
            var title = $('#ph_title').val();
            var content = window.tinymce ? tinymce.get('ph_content').getContent() : $('#ph_content').val();
            
            console.log('Title:', title);
            console.log('Content length:', content.length);
            
            if(!title.trim() || !content.trim()) {
                alert('Please add a title and content before saving.');
                return;
            }
            
            isSaving = true;
            var $btn = $(this);
            var originalText = $btn.text();
            $btn.text('Saving...').prop('disabled', true);
            
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    action: 'ph_save_post',
                    title: title,
                    content: content,
                    category: $('#ph_category').val(),
                    tags: $('#ph_tags').val(),
                    thumb: $('#ph_thumb_id').val(),
                    post_status: $('#ph_post_status').val(),
                    schedule_date: $('#ph_schedule_date').val(),
                    nonce: ph_nonce
                },
                success: function(res) {
                    console.log('Save response:', res);
                    isSaving = false;
                    $btn.text(originalText).prop('disabled', false);
                    
                    if(res.success) {
                        currentPostId = res.data.post_id;
                        console.log('Post saved with ID:', currentPostId);
                        alert('Post saved successfully! ID: ' + currentPostId);
                    } else {
                        console.log('Save error:', res.data);
                        alert('Error: ' + res.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Save AJAX error:', status, error);
                    isSaving = false;
                    $btn.text(originalText).prop('disabled', false);
                    alert('Network error. Please try again.');
                }
            });
        });
        
        // ===== FEATURED IMAGE =====
        $('#ph_img_btn').click(function(e) {
            e.preventDefault();
            console.log('Featured image button clicked');
            
            var frame = wp.media({ 
                title: 'Select Featured Image',
                multiple: false 
            }).on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                console.log('Selected image:', attachment);
                $('#ph_thumb_id').val(attachment.id);
                $('#ph_img_preview').html('<img src="'+attachment.url+'" style="max-width:100%; height:auto;">');
            }).open();
        });
        
        // ===== SCHEDULE FIELD TOGGLE =====
        $('#ph_post_status').change(function() {
            console.log('Post status changed to:', $(this).val());
            if($(this).val() === 'future') {
                $('#ph_schedule_field').show();
                // Set default to 24 hours from now
                var now = new Date();
                now.setHours(now.getHours() + 24);
                var datetime = now.toISOString().slice(0, 16);
                $('#ph_schedule_date').val(datetime);
            } else {
                $('#ph_schedule_field').hide();
            }
        });
        
        // ===== COPY TO CLIPBOARD =====
        $('#ph_copy_content').click(function() {
            console.log('Copy to clipboard clicked');
            var content = window.tinymce ? tinymce.get('ph_content').getContent() : $('#ph_content').val();
            var temp = $('<textarea>');
            $('body').append(temp);
            temp.val(content).select();
            document.execCommand('copy');
            temp.remove();
            alert('Content copied to clipboard!');
        });
        
        // ===== CLEAR ALL =====
        $('#ph_clear_all').click(function() {
            console.log('Clear all clicked');
            if(confirm('Clear all content and start over?')) {
                $('#ph_user_prompt').val('');
                $('#ph_title').val('');
                $('#ph_tags').val('');
                $('#ph_source_display').empty();
                $('#ph_thumb_id').val('');
                $('#ph_img_preview').html('<p style="color:#888;">No image selected</p>');
                if(window.tinymce && tinymce.get('ph_content')) {
                    tinymce.get('ph_content').setContent('');
                } else {
                    $('#ph_content').val('');
                }
                alert('All cleared!');
            }
        });
        
        console.log('PH Satire Generator initialization complete');
    });
    </script>
</div>