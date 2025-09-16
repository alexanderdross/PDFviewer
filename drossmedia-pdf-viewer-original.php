<?php
/**
 * Plugin Name: PDF Embed & SEO Optimize
 * Plugin URI: https://pdfviewer.drossmedia.de
 * Description: PDF Embed & SEO Optimize is a WordPress plugin that uses Mozilla's PDF.js viewer to serve PDFs via a viewer URL, boosting SEO & analytics tracking.
 * Version: 1.0.8
 * Author: Dross:Media
 * Author URI: https://drossmedia.de/
 * Text Domain: pdf-embed-seo-optimize
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.7
 * Tested up to: 6.8
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}



function drossmedia_enqueue_admin_scripts( $hook ) {
    global $post;

    // Only load on post-new and post edit screens for 'pdf_viewer' post type
    if ( ( $hook === 'post-new.php' || $hook === 'post.php' ) && isset( $post ) && $post->post_type === 'pdf_viewer' ) {
        $plugin_url = plugin_dir_url(__FILE__);
        
        // Enqueue Select2 CSS
        wp_enqueue_style('select2-css', $plugin_url . 'css/select2.min.css', array(), filemtime(plugin_dir_path(__FILE__) . 'css/select2.min.css'));

    // Enqueue Select2 JS
    wp_enqueue_script('select2-js', $plugin_url . 'js/select2.full.min.js', array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'js/select2.full.min.js'), true);


        // Register the custom script (best practice before enqueueing)
        wp_enqueue_script('drossmedia-pdf-viewer', plugin_dir_url(__FILE__) . 'js/script.js', ['jquery','select2-js'], filemtime(plugin_dir_path(__FILE__) . 'js/script.js'), true);

        // Localize script - Pass PHP data to JavaScript
        $drossmedia_pdf_upload_data = array(
            'title'        => __( 'Choose PDF', 'pdf-embed-seo-optimize' ),
            'uploadedText' => __( 'Upload PDF', 'pdf-embed-seo-optimize' ),
            'removeText'   => __( 'Remove PDF', 'pdf-embed-seo-optimize' )
        );

        wp_localize_script( 'drossmedia-pdf-viewer', 'drossmedia_pdf_upload_data', $drossmedia_pdf_upload_data );

        // Register the custom script (best practice before enqueueing)
        wp_enqueue_script('drossmedia-pdf-viewer-fe', plugin_dir_url(__FILE__) . 'js/fe-script.js', array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'js/fe-script.js'), true);

            // Mark the script as a module
        add_filter('script_loader_tag', function ($tag, $handle) {
            if ($handle === 'drossmedia-pdf-viewer-fe') {
                return str_replace('src', 'type="module" src', $tag);
            }
            return $tag;
        }, 10, 2);
        
        // Ensure a valid PDF Viewer post
        $pdf_document = get_post_meta( $post->ID, '_drossmedia_pdf_file', true );

        // Decode JSON if it exists.
        $pdf_data = $pdf_document ? json_decode( $pdf_document, true ) : array();
        $pdf_url   = isset( $pdf_data['url'] ) ? $pdf_data['url'] : '';

        $drossmedia_pdf_upload_url = array(
            'pdfUrl'   => $pdf_url,
        );

            // Localize script: pass PDF URL, AJAX URL, nonce, and post ID.
    wp_localize_script(
        'drossmedia-pdf-viewer-fe',
        'drossmedia_pdf_upload_url',
        array(
            'pdfUrl'   => $pdf_url,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('drossmedia_save_pdf_file'),
            'post_id'  => get_the_ID(),
        )
    );

        // Finally, enqueue the script after localization

    }
}

add_action( 'admin_enqueue_scripts', 'drossmedia_enqueue_admin_scripts' );


function drossmedia_enqueue_frontend_scripts() {
    $plugin_url = plugin_dir_url(__FILE__);
        

// Enqueue Select2 JS
wp_enqueue_script('select2-js', $plugin_url . 'js/select2.full.min.js', array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'js/select2.full.min.js'), true);

        // Register our custom initialization script.
        wp_register_script(
            'drossmedia-pdf-viewer-init',
            plugin_dir_url(__FILE__) . 'js/fe-script.js',
            ['jquery'],
            filemtime(plugin_dir_path(__FILE__) . 'js/fe-script.js')
        );
        wp_enqueue_style(
            'drossmedia-pdf-viewer-style',
            plugin_dir_url(__FILE__) . 'css/style.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'css/style.css')
        );
        wp_enqueue_style(
            'drossmedia-viewer-style',
            plugin_dir_url(__FILE__) . 'includes/viewer/css/style.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'includes/viewer/css/style.css')
        );
    
        wp_enqueue_script(
            'fluent-dom', $plugin_url . 'js/fluentdom.min.js',
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'js/fluentdom.min.js'),
            true
        );
    
}
add_action('wp_enqueue_scripts', 'drossmedia_enqueue_frontend_scripts');

function my_pdfjs_inline_override() {
    $override_script = "
      if (typeof PDFJSDev === 'undefined') {
        window.PDFJSDev = {
          eval: function(code) {
            if (code === 'BUNDLE_VERSION') return '4.10.38';
            if (code === 'BUNDLE_BUILD') return '4.10.38';
            return null;
          },
          test: function(condition) {
            // Return true for any condition so that restrictions are bypassed.
            return true;
          }
        };
      }
    ";
    wp_add_inline_script('pdfjs-core', $override_script, 'before');
}
add_action('wp_enqueue_scripts', 'my_pdfjs_inline_override');

function add_module_type_attribute( $tag, $handle, $src ) {
    // List the handles that should be treated as modules.
    $module_handles = array( 'pdfjs-core', 'pdfjs-worker', 'pdfjs-viewer' );
    if ( in_array( $handle, $module_handles, true ) ) {
        // Modify the tag to add type="module"
        $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
    }
    return $tag;
}
add_filter( 'script_loader_tag', 'add_module_type_attribute', 10, 3 );

require_once plugin_dir_path(__FILE__) . 'includes/drossmedia-pdf-viewer-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'html-sitemap.php';
require_once plugin_dir_path( __FILE__ ) . 'admin-docs.php';


/**
 * Register the "PDF Viewer" custom post type.
 */
function drossmedia_register_pdf_viewer_post_type() {
    $labels = array(
        'name'                  => __( 'Pdf', 'pdf-embed-seo-optimize' ),
        'singular_name'         => __( 'PDF Viewer', 'pdf-embed-seo-optimize' ),
        'menu_name'             => __( 'PDF Viewers', 'pdf-embed-seo-optimize' ),
        'name_admin_bar'        => __( 'PDF Viewer', 'pdf-embed-seo-optimize' ),
        'add_new'               => __( 'Add New', 'pdf-embed-seo-optimize' ),
        'add_new_item'          => __( 'Add New PDF Viewer', 'pdf-embed-seo-optimize' ),
        'new_item'              => __( 'New PDF Viewer', 'pdf-embed-seo-optimize' ),
        'edit_item'             => __( 'Edit PDF Viewer', 'pdf-embed-seo-optimize' ),
        'view_item'             => __( 'View PDF Viewer', 'pdf-embed-seo-optimize' ),
        'all_items'             => __( 'All PDF Viewers', 'pdf-embed-seo-optimize' ),
        'search_items'          => __( 'Search PDF Viewers', 'pdf-embed-seo-optimize' ),
        'parent_item_colon'     => __( 'Parent PDF Viewer:', 'pdf-embed-seo-optimize' ),
        'not_found'             => __( 'No PDF viewers found.', 'pdf-embed-seo-optimize' ),
        'not_found_in_trash'    => __( 'No PDF viewers found in Trash.', 'pdf-embed-seo-optimize' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true, // Makes it visible both on the front end and in the admin
        'has_archive'        => true,
        'rewrite'            => array( 
            'slug'       => 'pdf', // Customize the URL slug here
            'with_front' => false,        // Set to false if you don't want the front base included
        ),
        'supports' => array('title', 'thumbnail'), // Customize as needed
        'capability_type'    => 'post',
        'show_in_rest'       => true, // Enable Gutenberg editor support
        'menu_position'      => 20,   // Position the menu; adjust as necessary
        'menu_icon'          => 'dashicons-media-document', // Choose an appropriate dashicon
    );

    register_post_type( 'pdf_viewer', $args );
}
add_action( 'init', 'drossmedia_register_pdf_viewer_post_type' );

/**
 * Register the "Meta Details" metabox for the PDF Viewer post type.
 */


/**
 * Register the "PDF Upload" metabox.
 */
function drossmedia_add_pdf_upload_metabox() {
    add_meta_box(
        'drossmedia_pdf_upload',                         // Unique ID.
        __( 'PDF Upload', 'pdf-embed-seo-optimize' ),       // Title.
        'drossmedia_pdf_upload_callback',                // Callback to display the field.
        'pdf_viewer',                           // Post type.
        'normal',                               // Context.
        'default'                               // Priority.
    );
}
add_action( 'add_meta_boxes', 'drossmedia_add_pdf_upload_metabox' );



function drossmedia_pdf_upload_callback( $post ) {
    // Add nonce for security.
    wp_nonce_field( 'drossmedia_save_pdf_file', 'drossmedia_pdf_file_nonce' );

    // Retrieve the existing PDF document from post meta.
    $pdf_document = get_post_meta( $post->ID, '_drossmedia_pdf_file', true );

    // Decode JSON if it exists.
    $pdf_data = $pdf_document ? json_decode( $pdf_document, true ) : array();
    $pdf_url   = isset( $pdf_data['url'] ) ? $pdf_data['url'] : '';
    $pdf_title = isset( $pdf_data['title'] ) ? $pdf_data['title'] : '';
    ?>
    <div id="drossmedia_pdf_upload_container">
        <div id="drossmedia_pdf_preview">
            <?php if ( $pdf_url ) : ?>
                <p>
                    <button type="button" class="button" id="drossmedia_upload_pdf_button"><?php esc_html_e( 'Upload PDF', 'pdf-embed-seo-optimize' ); ?></button>
                </p>
                <iframe src="<?php echo esc_url( $pdf_url ); ?>" width="100%" height="500"></iframe>
            <?php else : ?>
                <p><?php esc_html_e( 'No PDF uploaded. Please upload a PDF file.', 'pdf-embed-seo-optimize' ); ?></p>
                <p>
                    <button type="button" class="button" id="drossmedia_upload_pdf_button"><?php esc_html_e('Upload PDF', 'pdf-embed-seo-optimize' ); ?></button>
                </p>
            <?php endif; ?>
        </div>

        <!-- Hidden inputs for the URL and title -->
        <input type="hidden" id="drossmedia_pdf_url" name="drossmedia_pdf_url" value="<?php echo esc_attr( $pdf_url ); ?>" />
        <input type="hidden" id="drossmedia_pdf_title" name="drossmedia_pdf_title" value="<?php echo esc_attr( $pdf_title ); ?>" />
    </div>
    <?php
}

function drossmedia_save_pdf_file( $post_id ) {
    // Verify nonce.
    if ( ! isset( $_POST['drossmedia_pdf_file_nonce'] ) || 
    ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['drossmedia_pdf_file_nonce'] ) ), 'drossmedia_save_pdf_file' ) ) {
   return;
}
    // Prevent autosave.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // Check user permissions.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    // Save or update the PDF file data.
    if ( isset( $_POST['drossmedia_pdf_url'], $_POST['drossmedia_pdf_title'] ) ) {
        $pdf_url   = sanitize_text_field( wp_unslash( $_POST['drossmedia_pdf_url'] ) );
        $pdf_title = sanitize_text_field( wp_unslash( $_POST['drossmedia_pdf_title'] ) );
        $pdf_data  = array(
            'url'   => $pdf_url,
            'title' => $pdf_title,
        );
        update_post_meta( $post_id, '_drossmedia_pdf_file', wp_json_encode( $pdf_data ) );
    }
}
add_action( 'save_post', 'drossmedia_save_pdf_file' );


add_filter('template_include', 'drossmedia_load_pdf_viewer_single_template');
function drossmedia_load_pdf_viewer_single_template($template) {
    if ( is_singular('pdf_viewer') ) {
        $plugin_template = plugin_dir_path(__FILE__) . 'single-pdf_viewer.php';
        if ( file_exists($plugin_template) ) {
            return $plugin_template;
        }
    }
    return $template;
}
/**
 * AJAX handler to save PDF metadata.
 */
function drossmedia_ajax_save_pdf_file() {
    // 1. Security: Verify the AJAX nonce.
    check_ajax_referer( 'drossmedia_save_pdf_file', sanitize_text_field( wp_unslash( $_POST['drossmedia_pdf_file_nonce'] ) ) );

    // 2. Get and validate the post ID.
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => 'Invalid post ID.' ) );
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission to edit this post.' ) );
    }

    // 3. Sanitize and process incoming PDF metadata.
    $pdf_url       = isset( $_POST['pdf_url'] ) ? esc_url_raw( wp_unslash($_POST['pdf_url'] )) : '';
    $pdf_title     = isset( $_POST['drossmedia_pdf_title'] ) ? sanitize_text_field( wp_unslash( $_POST['drossmedia_pdf_title'] ) ) : '';
    $creation_date = isset( $_POST['creation_date'] ) ? sanitize_text_field( wp_unslash( $_POST['creation_date'] ) ) : '';
    $modification_date = isset( $_POST['modification_date'] ) ? sanitize_text_field( wp_unslash( $_POST['modification_date'] ) ) : '';
    $description   = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
    $author   = isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : '';


    // 4. Verify that required fields are provided.
    if ( empty( $pdf_url ) || empty( $pdf_title ) ) {
        wp_send_json_error( array( 'message' => 'Missing required PDF data.' ) );
    }

    // 5. Prepare the PDF data array.
    $pdf_data = array(
        'url'           => $pdf_url,
        'title'         => $pdf_title,
        'creation_date' => $creation_date,
        'modification_date' => $modification_date,
        'description'   => $description,
        'author' =>  $author
    );
    // 6. Save or update the PDF metadata in post meta.
    if (!empty($pdf_data)) {
        update_post_meta( $post_id, '_drossmedia_pdf_file', wp_json_encode( $pdf_data ) );

        wp_send_json_success( array( 'message' => 'PDF metadata saved successfully.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to update PDF metadata.' ) );
    }
}
add_action( 'wp_ajax_drossmedia_save_pdf_file', 'drossmedia_ajax_save_pdf_file' );
add_action( 'wp_ajax_nopriv_drossmedia_save_pdf_file', 'drossmedia_ajax_save_pdf_file' );

function drossmedia_set_pdf_worker_url() {
    $pdf_worker_url = plugin_dir_url(__FILE__) . 'js/pdfworker.mjs';
    
    // Properly enqueue inline script instead of direct output
    wp_add_inline_script('jquery', 'window.pdfWorkerUrl = "' . esc_url($pdf_worker_url) . '";');
}
add_action('wp_enqueue_scripts', 'drossmedia_set_pdf_worker_url');
