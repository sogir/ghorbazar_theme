<?php
/**
 * Template Name: Canvas (no header/footer)
 * Template Post Type: page, post
 *
 * Blank page for landing pages — no theme header or footer.
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
