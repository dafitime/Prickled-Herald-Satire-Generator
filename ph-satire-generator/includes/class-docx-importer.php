<?php
namespace PH_Satire;

class Docx_Importer {
    
    public function handle_upload() {
        if (!isset($_FILES['ph_docx_file']) || $_FILES['ph_docx_file']['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Error uploading file. Please try again.', 'ph-satire') . '</p></div>';
            });
            return;
        }
        
        $file = $_FILES['ph_docx_file'];
        $text = $this->extract_text($file['tmp_name']);
        
        if (!$text) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Could not extract text from file.', 'ph-satire') . '</p></div>';
            });
            return;
        }
        
        $existing = get_option('ph_training_text', '');
        $updated = $existing . "\n\n--- IMPORTED ARTICLES ---\n" . $text;
        update_option('ph_training_text', $updated);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Training data imported successfully!', 'ph-satire') . '</p></div>';
        });
    }
    
    public function extract_text($filepath) {
        if (!file_exists($filepath)) {
            return false;
        }
        
        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            // Fallback: try to read as plain text
            return file_get_contents($filepath);
        }
        
        $zip = new \ZipArchive;
        if ($zip->open($filepath) !== TRUE) {
            return false;
        }
        
        // Look for document.xml
        if (($index = $zip->locateName('word/document.xml')) !== FALSE) {
            $content = $zip->getFromIndex($index);
            $zip->close();
            
            // Remove XML tags and clean up
            $content = preg_replace('/<[^>]+>/', ' ', $content);
            $content = preg_replace('/\s+/', ' ', $content);
            $content = trim($content);
            
            return $content;
        }
        
        $zip->close();
        return false;
    }
}