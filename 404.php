<?php
defined( 'ABSPATH' ) || exit;

get_header();

if ( ! hec_elementor_location( 'single' ) ) : ?>
    <main id="main" class="site-main container error-404">
        <h1 class="page-title"><?php esc_html_e( 'Page not found', 'ecomx-bookshop' ); ?></h1>
        <p><?php esc_html_e( 'The page you are looking for does not exist. Try searching instead.', 'ecomx-bookshop' ); ?></p>
        <?php get_search_form(); ?>
        <?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
            <p><a class="button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse the shop', 'ecomx-bookshop' ); ?></a></p>
        <?php endif; ?>
    </main>
<?php
endif;

get_footer();
