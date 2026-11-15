<?php
/**
 * WooCommerce wrapper for shop, category, tag, product search and
 * single product pages.
 *
 * Out of the box it renders the theme's own components:
 *  - archives: [hec_shop_filters] sidebar + [hec_shop_grid]
 *  - single:   [hec_product_details] + [hec_product_description]
 *              + [hec_product_video] + [hec_related_products]
 *
 * An Elementor Pro "Product" / "Product Archive" template replaces this
 * when one is assigned. Filter `ecomx_use_theme_shop_layout` to false to
 * fall back to WooCommerce's default templates instead.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$hec_is_single = is_singular( 'product' );

if ( ! hec_elementor_location( $hec_is_single ? 'single' : 'archive' ) ) :
    ?>
    <main id="main" class="site-main container woocommerce-main">
        <?php
        if ( ! apply_filters( 'ecomx_use_theme_shop_layout', true ) ) {
            woocommerce_content();
        } elseif ( $hec_is_single ) {
            while ( have_posts() ) {
                the_post();
                global $product;
                $product = wc_get_product( get_the_ID() );

                woocommerce_breadcrumb();
                do_action( 'woocommerce_before_single_product' );
                echo '<div id="product-' . esc_attr( get_the_ID() ) . '" class="' . esc_attr( implode( ' ', wc_get_product_class( 'ecomx-single-product', $product ) ) ) . '">';
                echo do_shortcode( '[hec_product_details]' );
                echo do_shortcode( '[hec_product_video]' );
                echo do_shortcode( '[hec_product_description]' );
                echo do_shortcode( '[hec_related_products]' );
                echo '</div>';
                do_action( 'woocommerce_after_single_product' );
            }
        } else {
            woocommerce_breadcrumb();
            ?>
            <header class="page-header ecomx-shop-header">
                <?php if ( is_search() ) : ?>
                    <?php echo do_shortcode( '[hec_search_results_heading]' ); ?>
                <?php else : ?>
                    <h1 class="page-title"><?php woocommerce_page_title(); ?></h1>
                    <?php do_action( 'woocommerce_archive_description' ); ?>
                <?php endif; ?>
            </header>
            <?php woocommerce_output_all_notices(); ?>
            <div class="ecomx-shop-layout">
                <aside class="ecomx-shop-sidebar">
                    <?php
                    echo do_shortcode( '[hec_shop_filters]' );
                    if ( is_active_sidebar( 'shop-sidebar' ) ) {
                        dynamic_sidebar( 'shop-sidebar' );
                    }
                    ?>
                </aside>
                <div class="ecomx-shop-products">
                    <?php echo do_shortcode( '[hec_shop_grid columns="' . (int) apply_filters( 'ecomx_shop_columns', 4 ) . '"]' ); ?>
                </div>
            </div>
            <?php
        }
        ?>
    </main>
    <?php
endif;

get_footer();
