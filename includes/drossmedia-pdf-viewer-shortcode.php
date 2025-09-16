<?php

/**
 * Usage: [pdf_viewer]
 */
function drossmedia_pdf_viewer_shortcode($atts) {
    global $post;

    // Extract shortcode attributes
    $atts = shortcode_atts(
        array(
            'id' => '' // Default is empty (fallback to current post)
        ),
        $atts,
        'pdf_viewer'
    );

    // Determine which post ID to use
    $post_id = !empty($atts['id']) ? intval($atts['id']) : (isset($post->ID) ? $post->ID : 0);

    // Ensure a valid PDF Viewer post
    $pdf_document = get_post_meta($post_id, '_drossmedia_pdf_file', true);

    // Decode JSON if it exists.
    $pdf_data = $pdf_document ? json_decode($pdf_document, true) : array();
    $pdf_url   = isset($pdf_data['url']) ? $pdf_data['url'] : '';
    $pdf_title = isset($pdf_data['title']) ? $pdf_data['title'] : '';

    // If no PDF data is stored, return a message.
    if (empty($pdf_document)) {
        return '<p>No PDF uploaded.</p>';
    }

    // Check if the URL exists in the decoded data
    if (empty($pdf_data['url'])) {
        return '<p>' . __('No PDF available for this viewer.', 'PDF-Embed-and-SEO-Optimize') . '</p>';
    }

    // Path to the official PDF.js "viewer.html" in your plugin
    $viewer_html_path = plugin_dir_path(__FILE__) . '/viewer/web/viewer.html';
    if (!file_exists($viewer_html_path)) {
        return '<p>Error: viewer.html not found in plugin.</p>';
    }

 // 1) Read the viewer.html into a variable.
    ob_start();
    include $viewer_html_path;
    $viewer_html = ob_get_clean();

    // 2) Get the post title dynamically.
    $post_title = get_the_title();
    global $post; // Ensure $post is available
    $permalink = get_permalink($post->ID);
    $image_data = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');

        // Now it is safe to access the array elements
        if($image_data ){
            $image_url    = $image_data[0];
            $image_width  = $image_data[1];
            $image_height = $image_data[2];
        }


    // Ensure we have the current post's author ID.
    $user_id = get_the_author_meta('ID');

    // Get the author's display name.
    $author_name = get_the_author_meta('display_name', $user_id);

    // Get the URL to the author's posts page.
    $author_url = get_author_posts_url( $user_id );



        // 2) Replace references to local CSS/JS with plugin_dir_url
    //    This example shows some references. You must do this for *all* references in viewer.html:
        $plugin_viewer_url = plugin_dir_url(__FILE__) . '/viewer/web/';


        $search_replace_map = [
            'href="viewer.css"'        => 'href="' . $plugin_viewer_url . 'viewer.css"',
            'href="pdf_viewer.css"'    => 'href="' . $plugin_viewer_url . 'pdf_viewer.css"',
            'src="pdf.js"'             => 'src="' . $plugin_viewer_url . 'pdf.js"',
            'src="pdf.worker.js"'      => 'src="' . $plugin_viewer_url . 'pdf.worker.js"',
            'src="pdf_viewer.js"'      => 'src="' . $plugin_viewer_url . 'pdf_viewer.js"',
            'src="viewer.js"'          => 'src="' . $plugin_viewer_url . 'viewer.js"',
        ];
    
        foreach ($search_replace_map as $search => $replace) {
            $viewer_html = str_replace($search, $replace, $viewer_html);
        }


// Get Yoast SEO title and meta description specifically from post meta
$post_id = get_the_ID(); // Get current post ID
$yoast_seo_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
$yoast_meta_description = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);

// Process template variables if they exist
if (function_exists('wpseo_replace_vars')) {
    if (!empty($yoast_seo_title)) {
        $yoast_seo_title = wpseo_replace_vars($yoast_seo_title, get_post($post_id));
    }
    if (!empty($yoast_meta_description)) {
        $yoast_meta_description = wpseo_replace_vars($yoast_meta_description, get_post($post_id));
    }
}

// If Yoast values are empty, use defaults
if (empty($yoast_seo_title)) {
    $yoast_seo_title = $post_title;
}
if (empty($yoast_meta_description)) {
    $yoast_meta_description = isset($pdf_data['description']) ? $pdf_data['description'] : '';
}

// Create the schema data with Yoast SEO information
$schema_data = array(
    "@context"     => "https://schema.org",
    "@type"        => "DigitalDocument",
    "headline"     => $yoast_seo_title,
    "name"         => $post_title,
    "description"  => $yoast_meta_description,
    "dateCreated"  => isset($pdf_data['creation_date']) ? $pdf_data['creation_date'] : '',
    "dateModified" => isset($pdf_data['modification_date']) ? $pdf_data['modification_date'] : '',
    "url"          => $permalink ? $permalink : '',
    "image"        => array(
        "@type"  => "ImageObject",
        "url"    => $image_url ? $image_url : '',
        "width"  => $image_width ? $image_width : '',
        "height" => $image_height ? $image_height : '',
    ),
    "author"       => array(
        "@type" => "Person",
        "name"  => $author_name ? $author_name : '',
        "url"   => $author_url ? $author_url : '',
    ),
);
    
    // Convert the schema array to JSON.
    $schema_json = json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
    
    // Get the description value for meta tag
    $meta_description = isset( $pdf_data['description'] ) ? $pdf_data['description'] : '';
    
    // Create meta tags for SEO title and description
    $meta_tags = '';
    $meta_tags .= '<meta name="description" content="' . esc_attr($meta_description) . '">';
    
    // Create the script tag for the JSON-LD schema.
    $schema_script = '<script type="application/ld+json">' . $schema_json . '</script>';
    
    // Combine the meta tags and schema script
    $head_content = $meta_tags . $schema_script;
    
    // Inject the combined content into the <head> section of viewer.html.
    if ( strpos( $viewer_html, '<head>' ) !== false ) {
        $updated_viewer_html = str_replace( '<head>', '<head>' . $head_content, $viewer_html );
    } else {
        // If no head tag exists, prepend the content.
        $updated_viewer_html = $head_content . $viewer_html;
    }

    // 3) Create a container div for the viewer
    ob_start();
    ?>
    <div 
    id="drossmedia-pdf-viewer-<?php echo esc_attr($post->ID); ?>" 
        class="drossmedia-pdf-viewer"
        data-pdf-url="<?php echo esc_attr($pdf_url); ?>"
        data-scale="1.3"
        data-pdf-title="<?php echo esc_attr($pdf_title); ?>"
        data-post-title="<?php echo esc_attr($post_title); ?>"
    >
        <?php echo $updated_viewer_html; ?>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('pdf_viewer', 'drossmedia_pdf_viewer_shortcode');
