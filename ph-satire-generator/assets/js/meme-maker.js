jQuery(document).ready(function($) {
    // Load meme maker HTML into the container
    var memeHtml = `
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
    `;
    
    // Insert the HTML when needed (this will be called from admin-script.js)
    window.ph_load_meme_maker = function() {
        $('#ph_meme_maker_container').html(memeHtml);
    };
});