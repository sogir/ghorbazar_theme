<?php
/**
 * Single posts and pages. Pages built with Elementor render edge to
 * edge; everything else gets the standard content container.
 */
defined( 'ABSPATH' ) || exit;

get_header();

if ( ! hec_elementor_location( 'single' ) ) :
    while ( have_posts() ) :
        the_post();

        $hec_is_elementor = class_exists( '\Elementor\Plugin' )
            && \Elementor\Plugin::$instance->documents->get( get_the_ID() )
            && \Elementor\Plugin::$instance->documents->get( get_the_ID() )->is_built_with_elementor();
        ?>
        <main id="main" class="site-main<?php echo $hec_is_elementor ? '' : ' container'; ?>">
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <?php
                // Hide the title on the front page, on Elementor pages and on WooCommerce pages that render their own headings.
                $hec_hide_title = $hec_is_elementor || is_front_page()
                    || ( function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() ) );
                if ( ! $hec_hide_title ) :
                    ?>
                    <header class="entry-header">
                        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                        <?php if ( 'post' === get_post_type() ) : ?>
                            <p class="entry-meta"><?php echo esc_html( get_the_date() ); ?></p>
                        <?php endif; ?>
                    </header>
                <?php endif; ?>

                <?php if ( ! $hec_is_elementor && has_post_thumbnail() && 'post' === get_post_type() ) : ?>
                    <div class="entry-thumbnail"><?php the_post_thumbnail( 'large' ); ?></div>
                <?php endif; ?>

                <div class="entry-content">
                    <?php
                    the_content();
                    wp_link_pages();
                    ?>
                </div>
            </article>

            <?php
            if ( ! $hec_is_elementor && ( comments_open() || get_comments_number() ) ) {
                comments_template();
            }
            ?>
        </main>
        <?php
    endwhile;
endif;

get_footer();
