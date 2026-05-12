jQuery(document).ready(function($) {
    console.log('PH Satire Generator script loaded');
    
    var ph_nonce = ph_satire_data.nonce;
    var ajax_url = ph_satire_data.ajax_url;
    var isGenerating = false;
    var isSaving = false;
    var currentPostId = null;
    var liveImageUrl = '';
    
    // Debug logging
    console.log('Nonce:', ph_nonce);
    console.log('AJAX URL:', ajax_url);
    
    // ========== GENERATE CONTENT ==========
    $('#ph_generate_btn').on('click', function(e) {
        e.preventDefault();
        console.log('Generate button clicked');
        
        if (isGenerating) {
            console.log('Already generating, skipping');
            return false;
        }
        
        var prompt = $('#ph_user_prompt').val();
        console.log('Prompt:', prompt);
        
        if(!prompt.trim()) {
            console.log('Empty prompt, showing error');
            showStatus('Please enter a topic to generate satire.', 'error');
            return false;
        }
        
        isGenerating = true;
        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#ph_loading').show();
        showStatus('Generating article...', 'success');
        
        console.log('Sending AJAX request...');
        
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
                    $('#ph_source_display').html(sourceHtml);
                    
                    showStatus('Article generated successfully!', 'success');
                    
                    // Scroll to editor
                    $('html, body').animate({
                        scrollTop: $('#ph_title').offset().top - 50
                    }, 500);
                } else {
                    console.log('Error in response:', res.data);
                    showStatus('Error: ' + res.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', status, error);
                console.log('XHR response:', xhr.responseText);
                isGenerating = false;
                $btn.prop('disabled', false);
                $('#ph_loading').hide();
                showStatus('Network error. Please check your API key and try again.', 'error');
            }
        });
        
        return false;
    });
    
    // ========== FEATURED IMAGE ==========
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
            liveImageUrl = attachment.url;
            $('#ph_img_preview').html('<img src="'+attachment.url+'" style="max-width:100%; height:auto;">');
        }).open();
    });
    
    // ========== SAVE POST ==========
    $('#ph_save_btn').on('click', function(e) {
        e.preventDefault();
        console.log('Save button clicked');
        
        if (isSaving) {
            console.log('Already saving, skipping');
            return false;
        }
        
        var title = $('#ph_title').val();
        var content = window.tinymce ? tinymce.get('ph_content').getContent() : $('#ph_content').val();
        
        console.log('Title:', title);
        console.log('Content length:', content.length);
        
        if(!title.trim() || !content.trim()) {
            console.log('Empty title or content');
            showSaveStatus('Please add a title and content before saving.', 'error');
            return;
        }
        
        isSaving = true;
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.text('Saving...').prop('disabled', true);
        $('#ph_save_status').hide();
        
        var auto_post_instagram = $('#ph_auto_post_instagram').is(':checked') ? 'yes' : 'no';
        
        console.log('Sending save request...');
        
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
                auto_post_instagram: auto_post_instagram,
                nonce: ph_nonce
            },
            success: function(res) {
                console.log('Save response:', res);
                isSaving = false;
                $btn.text(originalText).prop('disabled', false);
                
                if(res.success) {
                    currentPostId = res.data.post_id;
                    console.log('Post saved with ID:', currentPostId);
                    
                    var message = '✅ ' + res.data.message;
                    if(res.data.edit_link) {
                        message += ' <a href="'+res.data.edit_link+'" target="_blank" style="margin-left: 10px;">Edit Post</a>';
                    }
                    if(res.data.instagram_status) {
                        message += '<br>' + res.data.instagram_status;
                    }
                    showSaveStatus(message, 'success');
                } else {
                    console.log('Save error:', res.data);
                    showSaveStatus('❌ Error: ' + res.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.log('Save AJAX error:', status, error);
                isSaving = false;
                $btn.text(originalText).prop('disabled', false);
                showSaveStatus('❌ Network error. Please try again.', 'error');
            }
        });
    });
    
    // ========== SCHEDULE FIELD TOGGLE ==========
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
    
    // ========== COPY TO CLIPBOARD ==========
    $('#ph_copy_content').click(function() {
        console.log('Copy to clipboard clicked');
        var content = window.tinymce ? tinymce.get('ph_content').getContent() : $('#ph_content').val();
        var temp = $('<textarea>');
        $('body').append(temp);
        temp.val(content).select();
        document.execCommand('copy');
        temp.remove();
        showStatus('Content copied to clipboard!', 'success');
    });
    
    // ========== CLEAR ALL ==========
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
            showStatus('All cleared!', 'success');
        }
    });
    
    // ========== HELPER FUNCTIONS ==========
    function showStatus(message, type) {
        console.log('Showing status:', message, type);
        $('#ph_status_message')
            .removeClass('success error')
            .addClass(type)
            .html(message)
            .show()
            .delay(5000)
            .fadeOut();
    }
    
    function showSaveStatus(message, type) {
        console.log('Showing save status:', message, type);
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
    
    // Log that script is ready
    console.log('PH Satire Generator script initialization complete');
});