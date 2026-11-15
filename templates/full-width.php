<?php
/**
 * Template Name: Full Width
 * Template Post Type: page, post
 *
 * Theme header and footer, content edge to edge (no container, no title).
 */
defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
    the_post();
    ?>
    <main id="main" class="site-main site-main--full">
        <?php the_content(); ?>
    </main>
    <?php
endwhile;
get_footer();
