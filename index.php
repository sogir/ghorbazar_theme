<?php
/**
 * Fallback template: blog index, archives, search.
 */
defined( 'ABSPATH' ) || exit;

get_header();

if ( ! hec_elementor_location( 'archive' ) ) : ?>
    <main id="main" class="site-main container">
        <?php if ( is_home() && ! is_front_page() ) : ?>
            <header class="page-header"><h1 class="page-title"><?php single_post_title(); ?></h1></header>
        <?php elseif ( is_search() ) : ?>
            <header class="page-header">
                <h1 class="page-title"><?php printf( esc_html__( 'Search results for: %s', 'ecomx-bookshop' ), '<span>' . esc_html( get_search_query() ) . '</span>' ); ?></h1>
            </header>
        <?php elseif ( is_archive() ) : ?>
            <header class="page-header">
                <?php the_archive_title( '<h1 class="page-title">', '</h1>' ); ?>
                <?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
            </header>
        <?php endif; ?>

        <?php if ( have_posts() ) : ?>
            <div class="entry-grid">
                <?php
                while ( have_posts() ) {
                    the_post();
                    get_template_part( 'template-parts/content', get_post_type() );
                }
                ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e( 'Nothing found.', 'ecomx-bookshop' ); ?></p>
            <?php get_search_form(); ?>
        <?php endif; ?>
    </main>
<?php
endif;

get_footer();
