<?php
// views/partials/meme-maker-ui.php
if (!defined('ABSPATH')) exit;

$meme_maker = new \PH_Satire\Meme_Maker();
$last_post = $meme_maker->get_last_post_info();
$palettes = $meme_maker->get_palettes();
$next_idx = ($last_post['palette_idx'] + 1) % count($palettes);
?>

<div class="ph-meme-container">
    
    <!-- Preview Box -->
    <div class="ph-meme-preview-box">
        <strong><?php _e('PREVIOUSLY POSTED', 'ph-satire'); ?></strong>
        <div id="ph_last_meme_preview">
            <?php if (!empty($last_post['image_url'])) : ?>
                <div style="width:100%; height:120px; background-image:url('<?php echo esc_url($last_post['image_url']); ?>'); 
                     background-size:cover; background-position:center; margin-bottom:5px; border-radius:4px; border:1px solid #ddd;"></div>
                <div style="color:#888; margin-bottom:3px;"><?php echo date_i18n(get_option('date_format'), $last_post['date']); ?></div>
                <div style="background:<?php echo $palettes[$last_post['palette_idx']]['bg']; ?>; 
                     color:<?php echo $palettes[$last_post['palette_idx']]['text']; ?>; padding:2px 4px; 
                     border-radius:3px; border:1px solid #ccc; font-weight:bold;">
                    <?php echo esc_html($palettes[$last_post['palette_idx']]['name']); ?>
                </div>
            <?php else : ?>
                <div style="padding: 20px 0; color: #ccc;"><?php _e('No history yet.', 'ph-satire'); ?></div>
            <?php endif; ?>
        </div>
        <div style="margin-top:10px; border-top:1px solid #eee; padding-top:5px;">
            <strong style="color:#2c662d;"><?php _e('NEXT UP:', 'ph-satire'); ?></strong><br>
            <span id="ph_next_palette_name" style="color:#000; font-weight:bold;">
                <?php echo esc_html($palettes[$next_idx]['name']); ?>
            </span>
        </div>
    </div>
    
    <!-- Controls Box -->
    <div class="ph-meme-controls-box">
        <div class="ph-meme-controls">
            
            <!-- Posted Status -->
            <div id="ph_meme_posted_status" style="display:none;"></div>
            
            <!-- Color Theme -->
            <label><strong><?php _e('Color Theme:', 'ph-satire'); ?></strong></label>
            <select id="ph_palette_idx" name="ph_palette_idx" class="ph-meme-select">
                <?php foreach ($palettes as $idx => $p) : ?>
                    <option value="<?php echo $idx; ?>" <?php selected($idx, $next_idx); ?>>
                        <?php echo ($idx === $next_idx) ? '✨ ' : ''; ?><?php echo esc_html($p['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <!-- Sync Buttons -->
            <div class="ph-sync-buttons">
                <button type="button" id="ph_sync_title" class="button button-small">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Sync Title', 'ph-satire'); ?>
                </button>
                <button type="button" id="ph_sync_tags" class="button button-small">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Sync Hashtags', 'ph-satire'); ?>
                </button>
            </div>
            
            <!-- Meme Title -->
            <label><strong><?php _e('Meme Title:', 'ph-satire'); ?></strong></label>
            <textarea id="ph_custom_title" name="ph_custom_title" class="ph-meme-textarea" 
                      rows="3" placeholder="<?php esc_attr_e('Meme title...', 'ph-satire'); ?>"></textarea>
            
            <!-- Hashtags -->
            <label><strong><?php _e('Hashtags:', 'ph-satire'); ?></strong></label>
            <textarea id="ph_hashtags" name="ph_hashtags" class="ph-meme-textarea" 
                      rows="4" placeholder="<?php esc_attr_e('#satire #humor...', 'ph-satire'); ?>"></textarea>
            
            <!-- Vertical Offset -->
            <label>
                <strong><?php _e('Vertical Offset:', 'ph-satire'); ?></strong> 
                <span id="disp_y">0</span>
            </label>
            <input type="range" id="ph_y" name="ph_y" min="-300" max="300" value="0" 
                   class="ph-meme-range">
            
            <!-- Zoom -->
            <label>
                <strong><?php _e('Zoom:', 'ph-satire'); ?></strong> 
                <span id="disp_z">60%</span>
            </label>
            <input type="range" id="ph_zoom" name="ph_zoom" min="10" max="250" value="60" 
                   class="ph-meme-range">
            
            <!-- Auto-post Checkbox -->
            <div class="ph-auto-post">
                <label>
                    <input type="checkbox" id="ph_should_post_box" name="ph_should_post" value="yes" checked>
                    <strong><?php _e('Post to Instagram on Update/Publish', 'ph-satire'); ?></strong>
                </label>
            </div>
            
            <hr>
            
            <!-- Action Buttons -->
            <div id="ph_meme_controls_area" class="ph-meme-actions">
                <button type="button" id="ph_preview_btn" class="button button-secondary">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('👁️ Preview', 'ph-satire'); ?>
                </button>
                <button type="button" id="ph_post_btn" class="button button-primary">
                    <span class="dashicons dashicons-share"></span>
                    <?php _e('🚀 Manual Post', 'ph-satire'); ?>
                </button>
            </div>
            
            <!-- Confirmation Area -->
            <div id="ph_meme_confirm_area" class="ph-confirm-area" style="display:none;">
                <p id="ph_meme_confirm_msg" class="ph-confirm-message"></p>
                <div class="ph-confirm-buttons">
                    <button type="button" id="ph_meme_cancel_btn" class="button button-secondary">
                        <?php _e('Cancel', 'ph-satire'); ?>
                    </button>
                    <button type="button" id="ph_meme_confirm_btn" class="button button-danger">
                        <?php _e('Post Anyway', 'ph-satire'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Preview Area -->
            <div id="ph_meme_preview_area" class="ph-preview-area" style="display:none;">
                <label><?php _e('Preview:', 'ph-satire'); ?></label>
                <img id="ph_meme_preview_img" src="" class="ph-preview-image">
            </div>
            
            <!-- Status Area -->
            <div id="ph_meme_status_area" class="ph-status-area" style="display:none;"></div>
            
        </div>
    </div>
</div>

<!-- Placeholder for no image -->
<div id="ph_meme_placeholder" style="text-align: center; padding: 40px; color: #888;">
    <p><?php _e('Set a featured image to enable the meme maker.', 'ph-satire'); ?></p>
</div>