<?php
/**
 * Minimal/Blank Template for displaying a single PDF Viewer post
 * 
 * This template omits the theme's header & footer and calls the `[pdf_viewer]` shortcode.
 * 
 * IMPORTANT: You still want to call wp_head() and wp_footer() to ensure
 * WordPress scripts (and your plugin scripts) can properly load.
 */
?>
<!DOCTYPE html>
<html >
<head>
  <?php wp_head(); // This is where Yoast will output its OG tags. ?>
</head>
<body >

<?php
if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        
        // Output your PDF Viewer shortcode. By default, it pulls the PDF for this post's ID.
        echo do_shortcode( '[pdf_viewer]' );
    }
}
?>

<?php wp_footer(); // For scripts enqueued in the footer ?>
</body>
</html>
