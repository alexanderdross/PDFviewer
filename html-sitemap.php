<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Generate HTML sitemap for PDF Viewer custom post type.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output for the sitemap.
 */
function pdf_viewer_html_sitemap_shortcode( $atts ) {
    // Define query arguments.
    $args = array(
        'post_type'      => 'pdf_viewer',
        'posts_per_page' => -1, // Retrieve all posts.
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC'
    );
    
    $query = new WP_Query( $args );
    
    // Start building the HTML output.
    $output = '<div class="pdf-viewer-sitemap" itemscope itemtype="https://schema.org/ItemList">';
    
    if ( $query->have_posts() ) {
        $output .= '<ul>';
        while ( $query->have_posts() ) {
            $query->the_post();
            $output .= '<li itemprop="name"><u><a itemprop="url" href="' . esc_url( get_permalink() ) . '">' . get_the_title() . '</a></u></li>';
        }
        $output .= '</ul>';
    } else {
        $output .= '<p>No PDF Viewers found.</p>';
    }
    
    // Reset the global post data.
    wp_reset_postdata();
    
    $output .= '</div>';
    
    return $output;
}
add_shortcode( 'pdf_viewer_sitemap', 'pdf_viewer_html_sitemap_shortcode' );