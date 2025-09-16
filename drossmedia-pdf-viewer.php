<?php
/**
 * Plugin Name: PDF Embed & SEO Optimize
 * Plugin URI: https://pdfviewer.drossmedia.de/
 * Description: A comprehensive PDF viewer plugin using Mozilla PDF.js with SEO optimization features.
 * Version: 1.0.8
 * Author: Dross Media
 * Author URI: https://drossmedia.de/
 * Text Domain: pdf-embed-seo-optimize
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DROSSMEDIA_PDF_VIEWER_VERSION', '1.0.8');
define('DROSSMEDIA_PDF_VIEWER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DROSSMEDIA_PDF_VIEWER_PLUGIN_PATH', plugin_dir_path(__FILE__));

class DrossmediaPdfViewer {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('pdf-embed-seo-optimize', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        require_once DROSSMEDIA_PDF_VIEWER_PLUGIN_PATH . 'includes/drossmedia-pdf-viewer-shortcode.php';
        
        // Initialize shortcode
        new DrossmediaPdfViewerShortcode();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script(
            'pdfjs-viewer',
            DROSSMEDIA_PDF_VIEWER_PLUGIN_URL . 'assets/js/pdf.min.js',
            array(),
            DROSSMEDIA_PDF_VIEWER_VERSION,
            true
        );
        
        wp_enqueue_style(
            'pdfjs-viewer-style',
            DROSSMEDIA_PDF_VIEWER_PLUGIN_URL . 'assets/css/viewer.css',
            array(),
            DROSSMEDIA_PDF_VIEWER_VERSION
        );
    }
    
    public function activate() {
        // Create languages directory if it doesn't exist
        $languages_dir = DROSSMEDIA_PDF_VIEWER_PLUGIN_PATH . 'languages';
        if (!file_exists($languages_dir)) {
            wp_mkdir_p($languages_dir);
        }
        
        // Set default options
        add_option('drossmedia_pdf_viewer_options', array(
            'default_width' => '100%',
            'default_height' => '600px',
            'enable_seo' => true
        ));
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    public function handle_file_upload() {
        // Verify nonce for security
        if (!isset($_POST['drossmedia_pdf_file_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['drossmedia_pdf_file_nonce'])), 'drossmedia_pdf_file_upload')) {
            wp_die(esc_html__('Security check failed.', 'pdf-embed-seo-optimize'));
        }
        
        // Process file upload with proper sanitization
        if (isset($_FILES['pdf_file']) && !empty($_FILES['pdf_file']['name'])) {
            $uploaded_file = $_FILES['pdf_file'];
            
            // Validate file type
            $allowed_types = array('application/pdf');
            $file_type = wp_check_filetype($uploaded_file['name'], array('pdf' => 'application/pdf'));
            
            if (!in_array($file_type['type'], $allowed_types, true)) {
                wp_die(esc_html__('Only PDF files are allowed.', 'pdf-embed-seo-optimize'));
            }
            
            // Handle upload
            $upload = wp_handle_upload($uploaded_file, array('test_form' => false));
            
            if (isset($upload['error'])) {
                wp_die(esc_html($upload['error']));
            }
            
            return $upload;
        }
        
        return false;
    }
}

// Initialize the plugin
new DrossmediaPdfViewer();
