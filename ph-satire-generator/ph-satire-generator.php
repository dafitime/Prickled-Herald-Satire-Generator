<?php
/**
 * Plugin Name: PH Satire Generator Pro
 * Description: Advanced Gemini-powered Satire Generator with visible workspace and training data import.
 * Version: 3.8
 * Author: Prickled Herald
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Include meme maker functions if they exist
add_action('init', function() {
    // Check if meme maker functions exist, if not, define them inline
    if (!function_exists('ph_render_ui')) {
        require_once __DIR__ . '/ph-meme-maker.php'; // Adjust path if needed
    }
});

// Function to create/ensure Trash Panda author exists
function ph_ensure_trash_panda_author() {
    $username = 'trashpanda';
    $email = 'trashpanda@prickledherald.com';
    $display_name = 'Trash Panda';
    
    // Check if user exists
    $user_id = username_exists($username);
    
    if (!$user_id && email_exists($email) === false) {
        // Create new user
        $random_password = wp_generate_password(12, true, true);
        $user_id = wp_create_user($username, $random_password, $email);
        
        if (!is_wp_error($user_id)) {
            // Update user details
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $display_name,
                'first_name' => 'Trash',
                'last_name' => 'Panda',
                'role' => 'author',
                'description' => 'Satirical writer for Prickled Herald'
            ]);
        }
    }
    
    return $user_id ?: 1; // Fallback to admin
}

// 2. Admin Menu
add_action('admin_menu', function() {
    add_menu_page(
        'PH Satire Generator',
        'Satire Generator',
        'manage_options',
        'ph-satire-generator',
        'ph_satire_render_page',
        'dashicons-format-quote',
        65
    );
    
    add_submenu_page(
        'ph-satire-generator',
        'Training Data',
        'Training Data',
        'manage_options',
        'ph-training-data',
        'ph_training_data_page'
    );
});

// 3. Settings
add_action('admin_init', function() {
    register_setting( 'ph_satire_settings', 'ph_gemini_api_key' );
    register_setting( 'ph_satire_settings', 'ph_training_text' );
});

// 4. Main Page - UPDATED WITH EXCERPT FIELD
function ph_satire_render_page() {
    wp_enqueue_media();
    wp_enqueue_script('jquery');
    
    // Enqueue WordPress block editor scripts for featured image detection
    wp_enqueue_script('wp-data');
    wp_enqueue_script('wp-core-data');
    wp_enqueue_script('wp-editor');
    wp_enqueue_script('wp-edit-post');
    
    ?>
    <style>
    .ph-wrap { max-width: 1400px; }
    .ph-box { background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 20px; border-radius: 4px; }
    .ph-workspace { display: flex; gap: 20px; margin-top: 20px; }
    .ph-col { flex: 1; min-width: 0; }
    .ph-col-editor { flex: 2; }
    .ph-col-media { flex: 1; }
    .ph-headline { width: 100%; font-size: 1.5em; font-weight: bold; margin-bottom: 15px; padding: 10px; }
    .ph-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
    .ph-meta-field { margin-bottom: 15px; }
    .ph-meta-field label { display: block; margin-bottom: 5px; font-weight: 600; }
    .ph-meta-field select, .ph-meta-field input { width: 100%; }
    .ph-source-box { background: #f8f9fa; padding: 10px; margin: 20px 0; border-radius: 4px; font-size: 12px; }
    .ph-image-preview { margin: 10px 0; text-align: center; min-height: 150px; border: 2px dashed #ddd; border-radius: 4px; padding: 20px; }
    .ph-image-preview img { max-width: 100%; height: auto; }
    .ph-status { padding: 10px; margin: 10px 0; border-radius: 4px; display: none; }
    .ph-status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .ph-status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .ph-prompt-area { margin: 20px 0; }
    .ph-prompt-area textarea { width: 100%; padding: 10px; font-size: 14px; }
    .ph-generate-btn { margin-top: 10px; }
    
    /* Excerpt Field */
    .ph-excerpt-field { 
        margin: 20px 0; 
        background: #f8f9fa; 
        padding: 15px; 
        border-radius: 4px; 
        border-left: 4px solid #007cba; 
    }
    .ph-excerpt-field label { 
        display: block; 
        font-weight: 600; 
        margin-bottom: 8px; 
        color: #1d2327; 
    }
    .ph-excerpt-field textarea { 
        width: 100%; 
        padding: 10px; 
        font-size: 14px; 
        line-height: 1.4; 
        border: 1px solid #8c8f94; 
        border-radius: 4px; 
        background: #fff; 
    }
    .ph-excerpt-field .description { 
        margin-top: 5px; 
        font-size: 12px; 
        color: #646970; 
    }
    .ph-excerpt-field .char-count { 
        text-align: right; 
        font-size: 12px; 
        color: #646970; 
        margin-top: 5px; 
        float: right;
        font-weight: normal;
    }
    .ph-excerpt-field .char-count.warning { 
        color: #d63638; 
    }
    
    /* Meme Maker Styles */
    .ph-meme-container { 
        display: flex; 
        flex-wrap: wrap; 
        gap: 15px; 
        padding: 15px; 
        background: #f9f9f9; 
        border: 1px solid #ddd; 
        border-radius: 8px;
        margin-top: 15px;
    }
    .ph-meme-preview-box { 
        flex: 0 0 140px; 
        background: #fff; 
        border: 1px solid #eee; 
        border-radius: 6px; 
        padding: 10px; 
        font-size: 11px; 
        text-align: center; 
        height: fit-content; 
    }
    .ph-meme-controls-box { 
        flex: 1; 
        min-width: 250px; 
    }
    .ph-meme-controls { 
        background: #fff; 
        padding: 15px; 
        border: 1px solid #eee; 
        border-radius: 6px;
    }
    </style>

    <div class="wrap ph-wrap">
        <h1>PH Satire Generator <span style="font-size:14px; color:#646970;">by Trash Panda</span></h1>
        
        <?php if ( !get_option('ph_gemini_api_key') ) : ?>
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
        
        <!-- WORKSPACE - ALWAYS VISIBLE -->
        <div class="ph-workspace">
            <!-- Editor Column -->
            <div class="ph-col ph-col-editor">
                <div class="ph-box">
                    <h2>Editor</h2>
                    
                    <input type="text" id="ph_title" class="ph-headline" placeholder="Your Headline Here">
                    
                    <!-- NEW: Excerpt Field -->
                    <div class="ph-excerpt-field">
                        <label for="ph_excerpt">Social Media Excerpt <span id="ph_excerpt_char_count" class="char-count">0/200</span></label>
                        <textarea id="ph_excerpt" rows="3" placeholder="A short, engaging excerpt for Facebook/Twitter sharing..."></textarea>
                        <p class="description">This appears when sharing on social media. Keep it under 200 characters for best results.</p>
                    </div>
                    
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
                    <h3>Meme Maker</h3>
                    <div id="ph_meme_maker_container" style="display: none;">
                        <div class="ph-meme-container">
                            <div class="ph-meme-preview-box">
                                <strong style="display:block; margin-bottom: 5px; color:#555; border-bottom:1px solid #eee; padding-bottom:5px;">PREVIOUSLY POSTED</strong>
                                <div id="ph_last_meme_preview" style="padding: 20px 0; color: #ccc; text-align: center;">
                                    No history yet.
                                </div>
                                <div id="ph_next_palette" style="margin-top:10px; border-top:1px solid #eee; padding-top:5px;">
                                    <strong style="color:#2c662d;">NEXT UP:</strong><br>
                                    <span id="ph_next_palette_name" style="color:#000; font-weight:bold;">Loading...</span>
                                </div>
                            </div>
                            
                            <div class="ph-meme-controls-box">
                                <div class="ph-meme-controls">
                                    <div id="ph_meme_posted_status" style="display:none;"></div>
                                    
                                    <label><strong>Color Theme:</strong></label>
                                    <select id="ph_palette_idx" name="ph_palette_idx" style="width:100%; margin-bottom:10px;">
                                        <option value="0">Classic (White)</option>
                                        <option value="1">Dark Mode (Black)</option>
                                        <option value="2">The Herald (Beige)</option>
                                        <option value="3">The Prickle (Brown)</option>
                                    </select>

                                    <label><strong>Meme Title:</strong></label>
                                    <textarea id="ph_custom_title" name="ph_custom_title" autocomplete="off" style="width:100%; height:60px; margin-bottom:10px; padding: 5px; font-family: sans-serif;"></textarea>

                                    <label><strong>Hashtags:</strong></label>
                                    <textarea id="ph_hashtags" name="ph_hashtags" autocomplete="off" style="width:100%; height:80px; margin-bottom:15px; padding: 5px; font-family: monospace; color:#333; font-size:12px;"></textarea>

                                    <label><strong>Vertical Offset:</strong> <span id="disp_y">0</span></label>
                                    <input type="range" id="ph_y" name="ph_y" min="-300" max="300" value="0" style="width:100%; margin-bottom: 10px;" oninput="document.getElementById('disp_y').innerText=this.value">
                                    
                                    <label><strong>Zoom:</strong> <span id="disp_z">60</span>%</label>
                                    <input type="range" id="ph_zoom" name="ph_zoom" min="10" max="250" value="60" style="width:100%; margin-bottom: 15px;" oninput="document.getElementById('disp_z').innerText=this.value">

                                    <div style="background:#fff; padding:10px; border:1px solid #ccc; border-radius:4px; margin-bottom:15px;">
                                        <label style="font-weight:bold; cursor:pointer;">
                                            <input type="checkbox" id="ph_should_post_box" name="ph_should_post" value="yes" autocomplete="off" checked> 
                                            Post to Instagram on Update/Publish
                                        </label>
                                    </div>

                                    <hr style="margin: 15px 0; border: 0; border-top: 1px solid #ddd;">

                                    <div id="ph_meme_controls_area" style="display: flex; gap:10px;">
                                        <button type="button" id="ph_preview_btn" class="button button-secondary" style="flex:1; padding: 5px;">👁️ Preview</button>
                                        <button type="button" id="ph_post_btn" class="button button-primary" style="flex:1; padding: 5px;">🚀 Manual Post</button>
                                    </div>

                                    <div id="ph_meme_confirm_area" style="display:none; background: #fff5f5; border: 1px solid #e53935; padding: 10px; border-radius: 4px; text-align: center;">
                                        <p id="ph_meme_confirm_msg" style="color: #c62828; font-weight: bold; margin: 0 0 10px 0;">⚠️ Are you sure?</p>
                                        <div style="display:flex; gap:10px;">
                                            <button type="button" id="ph_meme_cancel_btn" class="button button-secondary" style="flex:1;">Cancel</button>
                                            <button type="button" id="ph_meme_confirm_btn" class="button" style="flex:1; background:#d63638; color:white; border-color:#d63638;">Post Anyway</button>
                                        </div>
                                    </div>
                                    
                                    <div id="ph_meme_preview_area" style="margin-top:20px; display:none; text-align: center;">
                                        <label style="display:block; margin-bottom:5px; color:#666;">Preview:</label>
                                        <img id="ph_meme_preview_img" src="" style="max-width:100%; max-height:350px; width:auto; height:auto; border:1px solid #ccc; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                    </div>
                                    <div id="ph_meme_status_area" style="margin-top:15px; padding:10px; display:none; border-radius: 4px; font-size: 13px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="ph_meme_placeholder" style="text-align: center; padding: 20px; color: #888;">
                        <p>Set a featured image to enable the meme maker.</p>
                    </div>
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
    </div>

    <script>
    jQuery(document).ready(function($) {
        var ph_nonce = '<?php echo wp_create_nonce("ph_satire_nonce"); ?>';
        var isGenerating = false;
        var isSaving = false;
        var currentPostId = null;
        var liveImageUrl = '';
        
        // Meme maker palettes
        var palettes = {
            0: {name: 'Classic (White)', bg: '#FFFFFF', text: '#000000'},
            1: {name: 'Dark Mode (Black)', bg: '#000000', text: '#FFFFFF'},
            2: {name: 'The Herald (Beige)', bg: '#F0D5A6', text: '#2C1E16'},
            3: {name: 'The Prickle (Brown)', bg: '#2C1E16', text: '#F0D5A6'}
        };
        
        // Excerpt character counter
        $('#ph_excerpt').on('input', function() {
            var length = $(this).val().length;
            var $counter = $('#ph_excerpt_char_count');
            $counter.text(length + '/200');
            
            if (length > 200) {
                $counter.addClass('warning');
                $counter.css('color', '#d63638');
            } else {
                $counter.removeClass('warning');
                $counter.css('color', '#646970');
            }
        });
        
        // Initialize meme maker when featured image is set
        $('#ph_img_btn').click(function(e) {
            e.preventDefault();
            var frame = wp.media({ 
                title: 'Select Featured Image',
                multiple: false 
            }).on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#ph_thumb_id').val(attachment.id);
                liveImageUrl = attachment.url;
                $('#ph_img_preview').html('<img src="'+attachment.url+'">');
                
                // Show meme maker
                $('#ph_meme_placeholder').hide();
                $('#ph_meme_maker_container').show();
                
                // Set meme title from article title
                $('#ph_custom_title').val($('#ph_title').val());
                
                // Set hashtags from tags with Instagram strategy
                var tags = $('#ph_tags').val();
                if(tags) {
                    var tagArray = tags.split(',');
                    var hashtags = '';
                    $.each(tagArray, function(i, tag) {
                        tag = tag.trim();
                        if(tag) {
                            // Clean for Instagram
                            var cleanTag = tag.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
                            hashtags += '#' + cleanTag + ' ';
                        }
                    });
                    
                    // Add Instagram strategy hashtags
                    hashtags += '#satire #comedy #news #humor #funny ';
                    hashtags += 'LinkInBio 🔗'; // Key call-to-action
                    
                    $('#ph_hashtags').val(hashtags.trim());
                }
            }).open();
        });
        
        // Meme maker functionality
        function showMemeConfirmation(msg) { 
            $('#ph_meme_controls_area').hide(); 
            $('#ph_meme_confirm_msg').text(msg); 
            $('#ph_meme_confirm_area').fadeIn(); 
        }
        
        function hideMemeConfirmation() { 
            $('#ph_meme_confirm_area').hide(); 
            $('#ph_meme_controls_area').fadeIn(); 
        }
        
        function toggleMemeUiBusy(busy) { 
            const c = $('#ph_meme_maker_container'); 
            if(busy) { 
                c.find('input,select,textarea,button').prop('disabled',true); 
                c.css('opacity','0.5'); 
            } else { 
                c.find('input,select,textarea,button').prop('disabled',false); 
                c.css('opacity','1'); 
            } 
        }
        
        $('#ph_post_btn').on('click', function() {
            if (!currentPostId) {
                alert("Please save the article first before posting to Instagram.");
                return;
            }
            
            if (!$('#ph_should_post_box').is(':checked')) { 
                showMemeConfirmation("⚠️ Box is UNCHECKED."); 
                return; 
            }
            runMemeApiCall('post');
        });
        
        $('#ph_meme_confirm_btn').on('click', function() { 
            hideMemeConfirmation(); 
            runMemeApiCall('post'); 
        });
        
        $('#ph_meme_cancel_btn').on('click', function() { 
            hideMemeConfirmation(); 
        });
        
        $('#ph_preview_btn').on('click', function() { 
            runMemeApiCall('preview'); 
        });
        
        function runMemeApiCall(mode) {
            if(!liveImageUrl) { 
                alert("Please select a featured image first."); 
                return; 
            }
            
            toggleMemeUiBusy(true);
            var btn = (mode==='preview') ? $('#ph_preview_btn') : $('#ph_post_btn');
            var orig = btn.text(); 
            btn.text('⏳ Working...');
            
            $('#ph_meme_status_area').show().html('Processing...');
            if(mode==='preview') $('#ph_meme_preview_area').hide();
            
            // Include article URL for Instagram caption
            var article_url = currentPostId ? '<?php echo home_url(); ?>/?p=' + currentPostId : '';
            
            $.post(ajaxurl, {
                action: 'ph_trigger_meme',
                post_id: currentPostId || 0,
                y_offset: $('#ph_y').val(), 
                zoom: $('#ph_zoom').val(),
                custom_title: $('#ph_custom_title').val(), 
                hashtags: $('#ph_hashtags').val(),
                palette_idx: $('#ph_palette_idx').val(), 
                mode: mode, 
                client_image_url: liveImageUrl,
                article_url: article_url
            }, function(res) {
                toggleMemeUiBusy(false); 
                btn.text(orig);
                
                if(res.success) {
                    if(mode==='preview') { 
                        $('#ph_meme_status_area').hide(); 
                        $('#ph_meme_preview_img').attr('src', res.data.imgur_url); 
                        $('#ph_meme_preview_area').fadeIn(); 
                    } else { 
                        $('#ph_meme_status_area').html('✅ SUCCESS! Posted to Instagram.'); 
                        // Show posted status
                        $('#ph_meme_posted_status').html(
                            '<div style="background:#eaffea; border:1px solid #46b450; color:#2c662d; padding:8px; margin-bottom:10px; border-radius:4px; text-align:center;">' +
                            '<strong>✅ Posted on ' + new Date().toLocaleString() + '</strong>' +
                            '</div>'
                        ).show();
                    }
                } else { 
                    $('#ph_meme_status_area').html('❌ ERROR: ' + res.data.message); 
                }
            }).fail(function(){ 
                toggleMemeUiBusy(false); 
                btn.text(orig); 
                $('#ph_meme_status_area').html('❌ Network Error'); 
            });
        }
        
        // Generate Content
        $(document).off('click', '#ph_generate_btn').on('click', '#ph_generate_btn', function(e) {
            e.preventDefault();
            
            if (isGenerating) {
                return false;
            }
            
            var prompt = $('#ph_user_prompt').val();
            if(!prompt.trim()) {
                showStatus('Please enter a topic to generate satire.', 'error');
                return false;
            }
            
            isGenerating = true;
            var $btn = $(this);
            $btn.prop('disabled', true);
            $('#ph_loading').show();
            showStatus('Generating article...', 'success');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ph_generate_content',
                    prompt: prompt,
                    nonce: ph_nonce
                },
                success: function(res) {
                    isGenerating = false;
                    $btn.prop('disabled', false);
                    $('#ph_loading').hide();
                    
                    if(res.success) {
                        // Populate fields
                        $('#ph_title').val(res.data.title);
                        $('#ph_tags').val(res.data.tags);
                        $('#ph_category').val(res.data.category);
                        
                        // NEW: Set excerpt
                        $('#ph_excerpt').val(res.data.excerpt || '');
                        $('#ph_excerpt').trigger('input'); // Update character count
                        
                        // Set content in editor
                        if(window.tinymce && tinymce.get('ph_content')) {
                            tinymce.get('ph_content').setContent(res.data.content);
                        } else {
                            $('#ph_content').val(res.data.content);
                        }
                        
                        // Update meme title too
                        $('#ph_custom_title').val(res.data.title);
                        
                        // Show source
                        var sourceHtml = '<strong>Source Inspiration:</strong> ';
                        if(res.data.source_link && res.data.source_link !== 'http://example.com') {
                            sourceHtml += '<a href="'+res.data.source_link+'" target="_blank">'+res.data.source_link+'</a>';
                        } else {
                            sourceHtml += 'No specific source provided';
                        }
                        $('#ph_source_display').html(sourceHtml);
                        
                        showStatus('Article generated successfully!', 'success');
                        
                        // Scroll to editor
                        $('html, body').animate({
                            scrollTop: $('#ph_title').offset().top - 50
                        }, 500);
                    } else {
                        showStatus('Error: ' + res.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    isGenerating = false;
                    $btn.prop('disabled', false);
                    $('#ph_loading').hide();
                    showStatus('Network error. Please check your API key and try again.', 'error');
                }
            });
            
            return false;
        });
        
        // Schedule field toggle
        $('#ph_post_status').change(function() {
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
        
        // Save Post
        $(document).off('click', '#ph_save_btn').on('click', '#ph_save_btn', function(e) {
            e.preventDefault();
            
            if (isSaving) {
                return false;
            }
            
            var title = $('#ph_title').val();
            var content = window.tinymce ? tinymce.get('ph_content').getContent() : $('#ph_content').val();
            
            if(!title.trim() || !content.trim()) {
                showSaveStatus('Please add a title and content before saving.', 'error');
                return;
            }
            
            isSaving = true;
            var $btn = $(this);
            var originalText = $btn.text();
            $btn.text('Saving...').prop('disabled', true);
            $('#ph_save_status').hide();
            
            var auto_post_instagram = $('#ph_auto_post_instagram').is(':checked') ? 'yes' : 'no';
            
            // Get meme settings if meme maker is visible
            var memeSettings = {};
            if($('#ph_meme_maker_container').is(':visible')) {
                memeSettings = {
                    palette_idx: $('#ph_palette_idx').val(),
                    custom_title: $('#ph_custom_title').val(),
                    hashtags: $('#ph_hashtags').val(),
                    y_offset: $('#ph_y').val(),
                    zoom: $('#ph_zoom').val(),
                    should_post: $('#ph_should_post_box').is(':checked') ? 'yes' : 'no'
                };
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ph_save_post',
                    title: title,
                    content: content,
                    excerpt: $('#ph_excerpt').val(), // NEW: Send excerpt
                    category: $('#ph_category').val(),
                    tags: $('#ph_tags').val(),
                    thumb: $('#ph_thumb_id').val(),
                    post_status: $('#ph_post_status').val(),
                    schedule_date: $('#ph_schedule_date').val(),
                    auto_post_instagram: auto_post_instagram,
                    meme_settings: memeSettings,
                    nonce: ph_nonce
                },
                success: function(res) {
                    isSaving = false;
                    $btn.text(originalText).prop('disabled', false);
                    
                    if(res.success) {
                        currentPostId = res.data.post_id;
                        
                        var message = '✅ ' + res.data.message;
                        if(res.data.edit_link) {
                            message += ' <a href="'+res.data.edit_link+'" target="_blank" style="margin-left: 10px;">Edit Post</a>';
                        }
                        if(res.data.instagram_status) {
                            message += '<br>' + res.data.instagram_status;
                        }
                        showSaveStatus(message, 'success');
                        
                        // Update meme maker with saved post ID
                        if(res.data.meme_ready && $('#ph_meme_maker_container').is(':visible')) {
                            // Enable meme posting buttons
                            $('#ph_post_btn').prop('disabled', false);
                            
                            // NEW: Auto-post to Instagram if immediate publish
                            if($('#ph_post_status').val() === 'publish' && auto_post_instagram === 'yes') {
                                setTimeout(function() {
                                    if(confirm('Publish to Instagram now?')) {
                                        runMemeApiCall('post');
                                    }
                                }, 1000);
                            }
                        }
                    } else {
                        showSaveStatus('❌ Error: ' + res.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    isSaving = false;
                    $btn.text(originalText).prop('disabled', false);
                    showSaveStatus('❌ Network error. Please try again.', 'error');
                }
            });
        });
        
        // Copy to clipboard
        $('#ph_copy_content').click(function() {
            var content = window.tinymce ? tinymce.get('ph_content').getContent() : $('#ph_content').val();
            var temp = $('<textarea>');
            $('body').append(temp);
            temp.val(content).select();
            document.execCommand('copy');
            temp.remove();
            showStatus('Content copied to clipboard!', 'success');
        });
        
        // Clear all
        $('#ph_clear_all').click(function() {
            if(confirm('Clear all content and start over?')) {
                $('#ph_user_prompt').val('');
                $('#ph_title').val('');
                $('#ph_excerpt').val('');
                $('#ph_tags').val('');
                $('#ph_source_display').empty();
                $('#ph_thumb_id').val('');
                $('#ph_img_preview').html('<p style="color:#888;">No image selected</p>');
                $('#ph_meme_placeholder').show();
                $('#ph_meme_maker_container').hide();
                if(window.tinymce && tinymce.get('ph_content')) {
                    tinymce.get('ph_content').setContent('');
                } else {
                    $('#ph_content').val('');
                }
                // Reset excerpt counter
                $('#ph_excerpt').trigger('input');
                showStatus('All cleared!', 'success');
            }
        });
        
        function showStatus(message, type) {
            $('#ph_status_message')
                .removeClass('success error')
                .addClass(type)
                .html(message)
                .show()
                .delay(5000)
                .fadeOut();
        }
        
        function showSaveStatus(message, type) {
            $('#ph_save_status')
                .removeClass('success error')
                .addClass(type)
                .html(message)
                .show();
            
            // Auto-hide success messages after 8 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $('#ph_save_status').fadeOut();
                }, 8000);
            }
        }
    });
    </script>
    <?php
}

// 5. Training Data Page
function ph_training_data_page() {
    // Handle file upload
    if ( isset($_POST['ph_upload_training']) && wp_verify_nonce($_POST['ph_upload_nonce'], 'ph_upload_training') ) {
        if ( !empty($_FILES['ph_docx_file']['tmp_name']) ) {
            $text = ph_extract_text_from_docx($_FILES['ph_docx_file']['tmp_name']);
            if ( $text ) {
                $existing = get_option('ph_training_text', '');
                $updated = $existing . "\n\n--- IMPORTED ARTICLES ---\n" . $text;
                update_option('ph_training_text', $updated);
                echo '<div class="notice notice-success"><p>Training data imported successfully!</p></div>';
            }
        }
    }
    
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
- Excerpts: 1-2 sentence engaging summaries for social media (under 200 characters)
- Paragraphs: 2-3 sentences each, conversational but professional
- Tags: Always include relevant comma-separated tags

EXAMPLE ARTICLES:
WASHINGTON, D.C. — In a groundbreaking move today, Congress passed legislation requiring all political speeches to include a laugh track...
SAN FRANCISCO, Calif. — Tech giant Google announced today that its new AI assistant will now respond to user queries with passive-aggressive comments...
            </textarea>
        </div>
    </div>
    <?php
}

// 6. Docx Text Extraction Function
function ph_extract_text_from_docx($filepath) {
    if ( !file_exists($filepath) ) {
        return false;
    }
    
    // Simple text extraction for .docx (ZIP-based)
    $zip = new ZipArchive;
    if ( $zip->open($filepath) === TRUE ) {
        if ( ($index = $zip->locateName('word/document.xml')) !== FALSE ) {
            $content = $zip->getFromIndex($index);
            
            // Remove XML tags and clean up
            $content = preg_replace('/<[^>]+>/', ' ', $content);
            $content = preg_replace('/\s+/', ' ', $content);
            $content = trim($content);
            
            $zip->close();
            return $content;
        }
        $zip->close();
    }
    
    // Fallback: try to read as plain text
    return file_get_contents($filepath);
}


// 7. AJAX Generator
add_action('wp_ajax_ph_generate_content', 'ph_generate_content');
function ph_generate_content() {
    check_ajax_referer('ph_satire_nonce', 'nonce');
    
    $api_key = get_option('ph_gemini_api_key');
    if ( empty($api_key) ) {
        wp_send_json_error('Please configure your Gemini API key first.');
    }
    
    $prompt = sanitize_text_field($_POST['prompt']);
    $training_data = get_option('ph_training_text', '');
    
    $current_date = date('F j, Y');
    
    // Build system prompt with strong emphasis on JSON and current events
    $system_prompt = "You are a satire writer for PrickledHerald.com. Write a funny, original satire article about **CURRENT EVENTS** happening around **$current_date**. Your tone should be witty and similar to The Onion or Babylon Bee.\n\n";

    if ( !empty($training_data) ) {
        $system_prompt .= "TRAINING DATA AND STYLE GUIDE:\n" . $training_data . "\n\n";
    }

    $system_prompt .= "TOPIC: " . $prompt . "\n\n";
    $system_prompt .= "IMPORTANT: Focus on recent news and modern situations. Avoid references to 2024 unless it's still relevant. Be creative.\n\n";
    $system_prompt .= "SOURCE REQUIREMENT: Include a source_link that points to a real news article that inspired this satire. If no specific source, leave empty string.\n\n";
    $system_prompt .= " You are a satire writer for PrickledHerald.com. Write a funny, original satire article that feels like it could be published TODAY ($current_date). 
	FORMAT RULES:
    1. Your response must be ONLY valid JSON, no other text before or after. Do not include explanations, markdown, or code fences.
    2. The JSON must have these exact keys:
    {
        \"title\": \"creative, clickbait headline about current events\",
        \"excerpt\": \"engaging 1-2 sentence excerpt for social media sharing, under 200 characters\",
        \"content\": \"full article starting with dateline: City, State — (no markdown, plain text). Include 'Image Generation Prompt' at end.\",
        \"category\": \"exact category from: Animals, Art, Business, Culture, Economics, Entertainment, Fashion, Lifestyle, News, People, Politics, Science, Sport, Technology, World\",
        \"tags\": \"comma-separated relevant tags including satire\",
        \"source_link\": \"REQUIRED: A real news article URL that inspired this satire. Must start with http:// or https://. If no specific source, use an empty string ''.\"
    }
    
    3. Content must be clean HTML paragraphs with <p> tags. NO markdown.
    4. End with: At publishing time, As of press time, At the time of writing, As of this report, or Current indications suggest.
    5. Include 'Image Generation Prompt' section at the end with hyper-realistic cinematic photo description.
    6. Write about CURRENT events and situations.
    7. Keep it under 500 words.
    8. Return ONLY the JSON object. No other words, no comments, no backticks. Start with { and end with }.";
    
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $api_key;
    
    $response = wp_remote_post($url, [
        'body' => json_encode([
            'contents' => [['parts' => [['text' => $system_prompt]]]],
            'generationConfig' => [
                'temperature' => 0.8,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            ]
        ]),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 60
    ]);
    
    if ( is_wp_error($response) ) {
        wp_send_json_error('API request failed: ' . $response->get_error_message());
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ( empty($data['candidates'][0]['content']['parts'][0]['text']) ) {
        if (isset($data['error'])) {
            wp_send_json_error('Gemini API Error: ' . $data['error']['message']);
        }
        wp_send_json_error('Invalid response from Gemini API. Response: ' . substr($body, 0, 500));
    }
    
    $raw_text = $data['candidates'][0]['content']['parts'][0]['text'];
    
    // Step 1: Remove markdown code fences and trim
    $raw_text = preg_replace('/^```json\s*/i', '', $raw_text);
    $raw_text = preg_replace('/\s*```$/', '', $raw_text);
    $raw_text = preg_replace('/^```\s*/', '', $raw_text);
    $raw_text = preg_replace('/\s*```$/', '', $raw_text);
    $raw_text = trim($raw_text);
    
    // Step 2: Try to extract JSON object with braces
    $json = null;
    $start = strpos($raw_text, '{');
    if ($start !== false) {
        $brace_count = 0;
        $in_string = false;
        $escape = false;
        $json_str = '';
        
        for ($i = $start; $i < strlen($raw_text); $i++) {
            $char = $raw_text[$i];
            $json_str .= $char;
            
            if ($char === '"' && !$escape) {
                $in_string = !$in_string;
            }
            if ($char === '\\' && !$escape) {
                $escape = true;
            } else {
                $escape = false;
            }
            
            if (!$in_string) {
                if ($char === '{') {
                    $brace_count++;
                } elseif ($char === '}') {
                    $brace_count--;
                    if ($brace_count === 0) {
                        break;
                    }
                }
            }
        }
        
        if ($brace_count == 0 && !empty($json_str)) {
            // Clean extracted JSON string
            $json_str = preg_replace('/[^\x20-\x7E\r\n]/', '', $json_str);
            $json_str = preg_replace_callback('/"(.*?)"/s', function($matches) {
                $str = $matches[1];
                $str = str_replace(["\r\n", "\n", "\r"], '\\n', $str);
                return '"' . $str . '"';
            }, $json_str);
            $json_str = preg_replace('/,\s*}/', '}', $json_str);
            $json_str = preg_replace('/,\s*]/', ']', $json_str);
            
            $json = json_decode($json_str, true);
        }
    }
    
    // Step 3: If still no JSON, try wrapping the whole response in braces if it looks like key-value pairs
    if (!$json) {
        // Check if response contains title, excerpt, content keys
        if (preg_match('/"title"\s*:\s*"([^"]+)"/', $raw_text, $title_match) &&
            preg_match('/"content"\s*:\s*"([^"]+)"/s', $raw_text, $content_match)) {
            
            $title = $title_match[1];
            $content = $content_match[1];
            
            // Extract excerpt if present
            $excerpt = '';
            if (preg_match('/"excerpt"\s*:\s*"([^"]+)"/', $raw_text, $excerpt_match)) {
                $excerpt = $excerpt_match[1];
            }
            
            // Extract category if present
            $category = 'News';
            if (preg_match('/"category"\s*:\s*"([^"]+)"/', $raw_text, $cat_match)) {
                $category = $cat_match[1];
            }
            
            // Extract tags if present
            $tags = 'satire, humor';
            if (preg_match('/"tags"\s*:\s*"([^"]+)"/', $raw_text, $tags_match)) {
                $tags = $tags_match[1];
            }
            
            // Extract source_link if present
            $source_link = '';
            if (preg_match('/"source_link"\s*:\s*"([^"]+)"/', $raw_text, $source_match)) {
                $source_link = $source_match[1];
            }
            
            $content = str_replace(['**', '*', '__'], '', $content);
            $content = wp_kses_post($content);
            
            $json = [
                'title' => $title,
                'excerpt' => !empty($excerpt) ? $excerpt : wp_trim_words($content, 20),
                'content' => $content,
                'category' => $category,
                'tags' => $tags,
                'source_link' => $source_link
            ];
        }
    }
    
    // Step 4: If still no JSON, use brute force: first line as title, rest as content
    if (!$json) {
        $lines = explode("\n", $raw_text);
        $title = !empty($lines[0]) ? trim($lines[0]) : 'Generated Article';
        $title = trim($title, '"\'');
        
        $content = trim(implode("\n", array_slice($lines, 1)));
        if (empty($content)) {
            $content = $raw_text;
        }
        
        $content = str_replace(['**', '*', '__'], '', $content);
        $content = wp_kses_post($content);
        
        $json = [
            'title' => $title,
            'excerpt' => wp_trim_words($content, 20),
            'content' => $content,
            'category' => 'News',
            'tags' => 'satire, humor',
            'source_link' => ''
        ];
        
        error_log('PH Satire Generator - Ultimate fallback used.');
    }
    
    // Step 5: Ensure all required fields exist
    $required = ['title', 'excerpt', 'content', 'category', 'tags', 'source_link'];
    foreach ($required as $field) {
        if ( !isset($json[$field]) ) {
            $json[$field] = '';
        }
    }
    
    // Clean content
    $json['content'] = str_replace(['**', '*', '__'], '', $json['content']);
    $json['content'] = wp_kses_post($json['content']);
    
    // Generate excerpt if empty
    if (empty($json['excerpt'])) {
        $json['excerpt'] = wp_trim_words($json['content'], 20);
    }
    
    // Validate source_link
    if (!empty($json['source_link']) && !preg_match('/^https?:\/\//', $json['source_link'])) {
        if (preg_match('/^[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}/', $json['source_link'])) {
            $json['source_link'] = 'https://' . $json['source_link'];
        } else {
            $json['source_link'] = '';
        }
    }
    
    wp_send_json_success($json);
}

// Fallback function if web search fails
function ph_generate_content_without_web($api_key, $system_prompt) {
    // Use gemini-flash-latest without web search
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $api_key;
    
    // Modify prompt to emphasize current context
    $system_prompt = str_replace(
        "IMPORTANT: Use your knowledge of CURRENT events (as of 2026).",
        "IMPORTANT: Write as if today is " . date('F j, Y') . ". Create satire about modern, current situations.",
        $system_prompt
    );
    
    $response = wp_remote_post($url, [
        'body' => json_encode([
            'contents' => [['parts' => [['text' => $system_prompt]]]],
            'generationConfig' => [
                'temperature' => 0.8,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            ]
        ]),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 60
    ]);
    
    if ( is_wp_error($response) ) {
        wp_send_json_error('API request failed: ' . $response->get_error_message());
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ( empty($data['candidates'][0]['content']['parts'][0]['text']) ) {
        if (isset($data['error'])) {
            wp_send_json_error('Gemini API Error: ' . $data['error']['message']);
        }
        wp_send_json_error('Invalid response from Gemini API.');
    }
    
    $text = $data['candidates'][0]['content']['parts'][0]['text'];
    
    // Extract JSON from response
    $text = str_replace(['```json', '```', '`'], '', $text);
    $text = trim($text);
    
    // Try to find JSON in the response
    if ( preg_match('/\{.*\}/s', $text, $matches) ) {
        $text = $matches[0];
    }
    
    $json = json_decode($text, true);
    
    if ( !$json ) {
        wp_send_json_error('Could not parse response as JSON.');
    }
    
    // Clean up content
    $json['content'] = str_replace(['**', '*', '__'], '', $json['content']);
    $json['content'] = wp_kses_post($json['content']);
    
    if (empty($json['excerpt'])) {
        $json['excerpt'] = wp_trim_words($json['content'], 20);
    }
    
    wp_send_json_success($json);
}

add_action('wp_ajax_ph_save_post', 'ph_save_post');
function ph_save_post() {
    check_ajax_referer('ph_satire_nonce', 'nonce');
    
    // Get Trash Panda author ID
    $trash_panda_id = ph_ensure_trash_panda_author();
    
    $post_data = [
        'post_title'   => sanitize_text_field($_POST['title']),
        'post_content' => wp_kses_post($_POST['content']),
        'post_excerpt' => sanitize_textarea_field($_POST['excerpt']), // NEW: Save excerpt
        'post_status'  => sanitize_text_field($_POST['post_status']),
        'post_type'    => 'post',
        'tags_input'   => sanitize_text_field($_POST['tags']),
        'post_author'  => $trash_panda_id, // Use Trash Panda
    ];
    
    // Handle scheduling
    if ( $_POST['post_status'] === 'future' && !empty($_POST['schedule_date']) ) {
        $post_data['post_date'] = date('Y-m-d H:i:s', strtotime($_POST['schedule_date']));
        $post_data['post_date_gmt'] = get_gmt_from_date($post_data['post_date']);
    }
    
    $post_id = wp_insert_post($post_data);
    
    if ( is_wp_error($post_id) ) {
        wp_send_json_error($post_id->get_error_message());
    }
    
    // Set category
    $cat_id = get_cat_ID($_POST['category']);
    if ( !$cat_id ) {
        $cat_id = wp_create_category($_POST['category']);
    }
    wp_set_post_categories($post_id, [$cat_id]);
    
    // Set featured image
    if ( !empty($_POST['thumb']) ) {
        set_post_thumbnail($post_id, intval($_POST['thumb']));
    }
    
    // Save meme settings if provided
    $meme_ready = false;
    if ( isset($_POST['meme_settings']) && is_array($_POST['meme_settings']) ) {
        $meme_settings = $_POST['meme_settings'];
        
        // Save meme maker settings
        if ( isset($meme_settings['palette_idx']) ) {
            update_post_meta($post_id, '_ph_palette_idx', intval($meme_settings['palette_idx']));
        }
        if ( isset($meme_settings['custom_title']) ) {
            update_post_meta($post_id, '_ph_custom_title', sanitize_text_field($meme_settings['custom_title']));
        }
        if ( isset($meme_settings['hashtags']) ) {
            update_post_meta($post_id, '_ph_hashtags', sanitize_textarea_field($meme_settings['hashtags']));
        }
        if ( isset($meme_settings['y_offset']) ) {
            update_post_meta($post_id, '_ph_y', sanitize_text_field($meme_settings['y_offset']));
        }
        if ( isset($meme_settings['zoom']) ) {
            update_post_meta($post_id, '_ph_zoom', sanitize_text_field($meme_settings['zoom']));
        }
        if ( isset($meme_settings['should_post']) ) {
            update_post_meta($post_id, '_ph_should_post', sanitize_text_field($meme_settings['should_post']));
        }
        
        $meme_ready = true;
    }
    
    // Handle Instagram auto-posting message
    $instagram_status = '';
    $auto_post_instagram = $_POST['auto_post_instagram'] ?? 'no';
    $post_status = $_POST['post_status'] ?? 'draft';
    
    if ( $auto_post_instagram === 'yes' ) {
        if ( function_exists('ph_render_ui') ) {
            // Save meta for meme maker
            update_post_meta($post_id, '_ph_should_post', 'yes');
            
            if ( $post_status === 'publish' ) {
                $instagram_status = '✅ Article published! Instagram auto-post enabled.';
                
                // Schedule immediate Instagram post
                if (!empty($_POST['thumb'])) {
                    wp_schedule_single_event(time() + 2, 'ph_auto_post_instagram', [$post_id]);
                    $instagram_status .= ' (Scheduled in 2 seconds)';
                }
            } else {
                $instagram_status = '✅ Post saved. Instagram will auto-post when this article publishes.';
            }
        } else {
            $instagram_status = '⚠️ Meme maker functions not loaded. Install the Prickled Herald Manual Poster plugin.';
        }
    }
    
    $edit_link = get_edit_post_link($post_id);
    
    wp_send_json_success([
        'message' => 'Post saved successfully!',
        'edit_link' => $edit_link,
        'post_id' => $post_id,
        'instagram_status' => $instagram_status,
        'meme_ready' => $meme_ready
    ]);
}

// Add scheduled action for Instagram auto-posting
add_action('ph_auto_post_instagram', 'ph_execute_instagram_auto_post', 10, 1);

function ph_execute_instagram_auto_post($post_id) {
    $post = get_post($post_id);
    if ($post->post_status !== 'publish') {
        return;
    }
    
    // Get featured image
    $image_id = get_post_thumbnail_id($post_id);
    if (!$image_id) {
        return;
    }
    
    $image_url = wp_get_attachment_url($image_id);
    if (!$image_url) {
        return;
    }
    
    // Get meme settings
    $palette_idx = get_post_meta($post_id, '_ph_palette_idx', true) ?: 0;
    $custom_title = get_post_meta($post_id, '_ph_custom_title', true) ?: get_the_title($post_id);
    $hashtags = get_post_meta($post_id, '_ph_hashtags', true) ?: '';
    $y_offset = get_post_meta($post_id, '_ph_y', true) ?: 0;
    $zoom = get_post_meta($post_id, '_ph_zoom', true) ?: 60;
    
    // Prepare AJAX call to trigger meme
    $data = [
        'action' => 'ph_trigger_meme',
        'post_id' => $post_id,
        'mode' => 'post',
        'client_image_url' => $image_url,
        'y_offset' => $y_offset,
        'zoom' => $zoom,
        'custom_title' => $custom_title,
        'hashtags' => $hashtags,
        'palette_idx' => $palette_idx,
        'article_url' => get_permalink($post_id)
    ];
    
    // Make internal AJAX call (non-blocking)
    wp_remote_post(admin_url('admin-ajax.php'), [
        'blocking' => false,
        'timeout' => 0.01,
        'sslverify' => false,
        'body' => $data,
    ]);
}

// 8. Add AJAX handler for meme maker
if (!has_action('wp_ajax_ph_trigger_meme')) {
    add_action('wp_ajax_ph_trigger_meme', 'ph_satire_handle_meme_ajax');
    
    function ph_satire_handle_meme_ajax() {
        $post_id = intval($_POST['post_id']);
        $mode = $_POST['mode'] ?? 'generate';
        $img_url = !empty($_POST['client_image_url']) ? esc_url_raw($_POST['client_image_url']) : '';
        $article_url = !empty($_POST['article_url']) ? esc_url_raw($_POST['article_url']) : '';
        
        if (!$img_url) { 
            wp_send_json_error(['message' => 'No Image']); 
            return; 
        }

        // Mock palettes for the API call
        $palettes = [
            0 => ['name' => 'Classic (White)',   'bg' => '#FFFFFF', 'text' => '#000000'],
            1 => ['name' => 'Dark Mode (Black)', 'bg' => '#000000', 'text' => '#FFFFFF'],
            2 => ['name' => 'The Herald (Beige)','bg' => '#F0D5A6', 'text' => '#2C1E16'], 
            3 => ['name' => 'The Prickle (Brown)','bg' => '#2C1E16', 'text' => '#F0D5A6'], 
        ];
        
        $chosen = isset($palettes[$_POST['palette_idx']]) ? $palettes[$_POST['palette_idx']] : $palettes[0];
        
        // Prepare caption with Instagram strategy
        $title = stripslashes($_POST['custom_title']);
        $hashtags = stripslashes($_POST['hashtags']);
        
        // Instagram caption strategy: Link in bio
        $caption = $title . "\n\n";
        $caption .= "Read the full hilarious story at the link in our bio! 👆\n\n";
        $caption .= $hashtags . "\n\n";
        $caption .= "#LinkInBio #SeeMoreInBio";
        
        // Call your Python API
        $target_url = 'https://api.prickledherald.com/meme-maker/generate';
        
        $response = wp_remote_post($target_url, [
            'blocking' => true, 
            'timeout' => 45, 
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'mode' => $mode, 
                'title' => $caption, // Use full caption with strategy
                'image_url' => $img_url, 
                'zoom' => $_POST['zoom'], 
                'y_offset' => $_POST['y_offset'], 
                'hashtags' => '', // Already in caption
                'bg_color' => $chosen['bg'], 
                'text_color' => $chosen['text'],
                'article_url' => $article_url
            ]), 
            'sslverify' => false 
        ]);

        if (is_wp_error($response)) { 
            wp_send_json_error(['message' => 'Connection Failed: ' . $response->get_error_message()]); 
            return;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($body['status'] === 'Success') {
            if ($mode == 'post' && $post_id > 0) {
                update_post_meta($post_id, '_ph_ig_posted_date', time());
                update_post_meta($post_id, '_ph_should_post', '0');
                
                $final_img = $body['imgur_url'] ?? $img_url;
                update_option('ph_global_last_post_info', [
                    'date' => time(), 
                    'image_url' => $final_img, 
                    'palette_idx' => intval($_POST['palette_idx'])
                ]);
            }
            wp_send_json_success($body);
        } else { 
            wp_send_json_error(['message' => $body['message'] ?? 'Unknown Error']); 
        }
    }
}

// 9. Add Facebook Open Graph tags for better sharing
add_action('wp_head', 'ph_add_social_meta_tags');
function ph_add_social_meta_tags() {
    if (is_single()) {
        global $post;
        
        // Check if this is a Trash Panda article
        $author_id = get_post_field('post_author', $post->ID);
        $author = get_userdata($author_id);
        
        if ($author && $author->display_name === 'Trash Panda') {
            $excerpt = get_the_excerpt($post->ID);
            if (empty($excerpt)) {
                $excerpt = wp_trim_words(get_the_content($post->ID), 30);
            }
            
            ?>
            <!-- Prickled Herald Social Meta -->
            <meta property="og:title" content="<?php echo esc_attr(get_the_title($post->ID)); ?>">
            <meta property="og:description" content="<?php echo esc_attr($excerpt); ?>">
            <meta property="og:type" content="article">
            <meta property="og:url" content="<?php echo esc_url(get_permalink($post->ID)); ?>">
            <meta property="og:site_name" content="Prickled Herald">
            <meta property="article:author" content="Trash Panda">
            <meta property="article:published_time" content="<?php echo esc_attr(get_the_date('c', $post->ID)); ?>">
            <meta property="article:section" content="<?php echo esc_attr(get_the_category_list(', ', '', $post->ID)); ?>">
            
            <?php if (has_post_thumbnail($post->ID)): ?>
                <meta property="og:image" content="<?php echo esc_url(get_the_post_thumbnail_url($post->ID, 'large')); ?>">
                <meta property="og:image:width" content="1200">
                <meta property="og:image:height" content="630">
            <?php endif; ?>
            
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="<?php echo esc_attr(get_the_title($post->ID)); ?>">
            <meta name="twitter:description" content="<?php echo esc_attr($excerpt); ?>">
            <meta name="twitter:creator" content="@PrickledHerald">
            <?php if (has_post_thumbnail($post->ID)): ?>
                <meta name="twitter:image" content="<?php echo esc_url(get_the_post_thumbnail_url($post->ID, 'large')); ?>">
            <?php endif; ?>
            <?php
        }
    }
}