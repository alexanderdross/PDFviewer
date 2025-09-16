<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add a "Docs" submenu under "PDF Viewers" in the WordPress Admin
 * for providing usage instructions and best practices.
 */
function drossmedia_add_docs_submenu() {
    add_submenu_page(
        'edit.php?post_type=pdf_viewer', // Parent slug for "PDF Viewers"
        'Docs',                          // Page title
        'Docs',                          // Menu title
        'manage_options',                // Capability required
        'drossmedia-docs-page',                  // Menu slug
        'drossmedia_docs_page_callback'          // Callback function
    );
}
add_action( 'admin_menu', 'drossmedia_add_docs_submenu' );

/**
 * Callback function to render the "Docs" page content.
 */
function drossmedia_docs_page_callback() {
    ?>
    <div class="wrap">
        <h1 style="margin-bottom: 0.5em;">PDF Embed &amp; SEO Optimize: Docs &amp; Usage</h1>
        <p style="max-width: 800px;">
            Welcome to the documentation for <strong>PDF Embed &amp; SEO Optimize</strong>. 
            Below you will find details on how to use the shortcodes included with this plugin 
            and how to integrate them into your theme or custom templates for the best user experience.
        </p>

        <hr style="margin: 2em 0;" />

        <h2 style="margin-top: 1.5em;">Table of Contents</h2>
        <ol style="list-style: decimal; margin-left: 2em;">
            <li><a href="#pdf-viewer-sitemap">[pdf_viewer_sitemap]</a></li>
            <li><a href="#pdf-viewer">[pdf_viewer]</a></li>
            <li><a href="#custom-template-usage">Using [pdf_viewer] in Custom Template Files</a></li>
        </ol>

        <hr style="margin: 2em 0;" />

        <!-- [pdf_viewer_sitemap] SECTION -->
        <h2 id="pdf-viewer-sitemap">1. [pdf_viewer_sitemap]</h2>
        <p>
            <strong>Purpose:</strong> This shortcode displays a simple HTML sitemap of all published 
            <em>PDF Viewer</em> posts. You can insert it into any page or post to list 
            all your PDF Viewer entries in an unordered list, sorted alphabetically.
        </p>
        <p><strong>Example Usage:</strong></p>
        <pre style="background: #f7f7f7; padding: 10px; border-radius: 4px;">[pdf_viewer_sitemap]</pre>
        <p>
            When placed on a page, this will produce a linked list of every 
            <em>PDF Viewer</em> custom post, allowing visitors to easily view and access 
            all of your PDF documents.
        </p>

        <hr style="margin: 2em 0;" />

        <!-- [pdf_viewer] SECTION -->
        <h2 id="pdf-viewer">2. [pdf_viewer]</h2>
        <p>
            <strong>Purpose:</strong> This shortcode embeds a single PDF into your content. 
            When used on a single <em>PDF Viewer</em> post, it automatically outputs the PDF for that post.
        </p>
        <p><strong>Basic Example:</strong></p>
        <pre style="background: #f7f7f7; padding: 10px; border-radius: 4px;">[pdf_viewer]</pre>
        <p>
            When you insert this shortcode on a PDF Viewer post, it will dynamically use the current post’s data to embed the PDF.
        </p>
        <p>
            <strong>Embedding a Specific PDF:</strong><br>
            The shortcode supports specifying a particular PDF by providing its custom post ID as an attribute. For example: 
            </p>
        <pre style="background: #f7f7f7; padding: 10px; border-radius: 4px;">[pdf_viewer id="384"]</pre>
       

        <hr style="margin: 2em 0;" />

        <!-- CUSTOM TEMPLATE USAGE SECTION -->
        <h2 id="custom-template-usage">3. Using [pdf_viewer] in Custom Template Files</h2>
        <p>
            <strong>Purpose:</strong> Whether you’re using a page builder (like Elementor, Divi, Beaver Builder, etc.) or you’ve created a custom template file for your <em>PDF Viewer</em> custom post type, you can insert the shortcode so it automatically displays the PDF of the current post.
        </p>
        <p>
            <strong>Option A:</strong> Using a Shortcode or HTML Module in Your Page Builder<br>
            Most page builders offer a widget or module where you can insert custom shortcodes. Simply add a Text or Shortcode module and insert:
        </p>
        <pre style="background: #f7f7f7; padding: 10px; border-radius: 4px;">[pdf_viewer]</pre>
        <p>
            This will automatically output the embedded PDF on the PDF Viewer post.
        </p>
        <p>
            <strong>Option B:</strong> Embedding the Shortcode in a Custom Template File<br>
            If you’re creating a custom template file (for example, in your theme or via a page builder’s custom code module), you can embed the shortcode using PHP. For example, in your custom template file for the PDF Viewer custom post type:
        </p>
        <pre style="background: #f7f7f7; padding: 10px; border-radius: 4px;">
&lt;?php
if ( function_exists( 'do_shortcode' ) ) {
    // Automatically uses the current post's PDF data.
    echo do_shortcode( '[pdf_viewer]' );
}
?&gt;
        </pre>
        <p>
            This PHP snippet dynamically outputs the embedded PDF for the current <em>PDF Viewer</em> post without the need for specifying an ID.
        </p>
        <p>
            Both options allow you to integrate the shortcode seamlessly into your custom layouts.
        </p>

        <hr style="margin: 2em 0;" />

        <p style="max-width: 800px;">
            <em>That’s it! We hope this documentation helps you quickly set up and use our <strong>PDF Embed &amp; SEO Optimize</strong> plugin. If you have any questions or run into issues, please refer to our support resources or contact us directly.</em>
        	<em>Plugin developed by <a href="https://dross.net/#media" target="_blank" title="PDF Embed & SEO Optimize WordPress Plugin developed by Dross:Media">Dross:Media</a></em>
		</p>
    </div>
    <?php
}
