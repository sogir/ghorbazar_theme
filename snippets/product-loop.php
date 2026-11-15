<?php
/**
 * Product loop shortcode — [hec_product_loop]
 * (full file — replaces the previous version)
 */



/**
 * Force-load WooCommerce's AJAX add-to-cart script + localized params.
 * Needed because this shortcode is used on non-WooCommerce pages
 * (homepage, custom Elementor templates) where WooCommerce doesn't
 * automatically enqueue wc-add-to-cart.js on its own. Without this,
 * add-to-cart falls back to a full page reload instead of AJAX —
 * which is why button text/state changes never showed up.
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( function_exists( 'WC' ) ) {
        wp_enqueue_script( 'wc-add-to-cart' );
        wp_enqueue_script( 'wc-cart-fragments' );
    }
}, 20 ); // priority 20 — runs after WooCommerce registers its own scripts (default priority 10)



defined( 'ABSPATH' ) || exit;

/**
 * Kept for back-compat with older snippets/templates: the site is
 * English-only now, so numbers are returned unchanged.
 */
function hec_to_bn_numerals( $number ) {
    return (string) $number;
}

/**
 * Green "Free delivery" pill with a truck icon — shown for any
 * product assigned the "free-delivery" shipping class (Products →
 * edit product → Shipping tab → Shipping class). Shared by the
 * product loop card and the single product page so both stay in
 * sync automatically.
 */
function hec_render_free_delivery_badge( $product ) {
    if ( ! $product || 'free-delivery' !== $product->get_shipping_class() ) {
        return '';
    }
    ob_start();
    ?>
    <span class="hec-free-delivery-badge">
        <svg viewBox="0 0 24 24" fill="none" width="13" height="13"><path d="M3 17V7a1 1 0 011-1h9a1 1 0 011 1v10M3 17h11m0 0h4.05a1 1 0 00.95-.68l1.3-3.9a1 1 0 00-.05-.74l-2.05-4.1a1 1 0 00-.9-.58H14M3 17a2 2 0 104 0 2 2 0 00-4 0zm11 0a2 2 0 104 0 2 2 0 00-4 0z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Free delivery
    </span>
    <?php
    return ob_get_clean();
}

/**
 * Renders one product card for the current global $product / loop
 * position. Shared by [hec_product_loop], [hec_related_products],
 * and the shop/archive grid, so every product card on the site stays
 * visually identical from one place instead of duplicated markup.
 */
function hec_render_product_card() {
    global $product;

    if ( ! $product || ! $product->is_visible() ) {
        return;
    }
    ?>
    <div class="hec-product-card">

        <?php if ( $product->is_on_sale() && $product->get_regular_price() ) :
            $regular = (float) $product->get_regular_price();
            $sale    = (float) $product->get_sale_price();
            $percent = $regular > 0 ? round( ( ( $regular - $sale ) / $regular ) * 100 ) : 0;
            ?>
            <div class="hec-sale-badge">
                <?php echo esc_html( $percent ); ?>%
            </div>
        <?php endif; ?>

        <a href="<?php echo esc_url( get_permalink() ); ?>" class="hec-product-image">
            <?php echo woocommerce_get_product_thumbnail( 'woocommerce_thumbnail' ); ?>
        </a>

        <div class="hec-product-info">
            <a href="<?php echo esc_url( get_permalink() ); ?>" class="hec-product-name">
                <?php echo esc_html( wp_trim_words( get_the_title(), 8 ) ); ?>
            </a>

            <div class="hec-product-price">
                <?php echo $product->get_price_html(); ?>
            </div>
            <?php echo hec_render_free_delivery_badge( $product ); ?>
        </div>

        <div class="hec-product-actions">

            <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
               data-quantity="1"
               data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
               class="hec-btn hec-btn-cart ajax_add_to_cart add_to_cart_button"
               rel="nofollow">
                Add to cart
            </a>

            <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
               data-quantity="1"
               data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
               class="hec-btn hec-btn-order ajax_add_to_cart add_to_cart_button hec-order-now"
               rel="nofollow">
                Order now
            </a>

        </div>

    </div>
    <?php
}

add_shortcode( 'hec_product_loop', function ( $atts ) {

    if ( ! class_exists( 'WooCommerce' ) ) {
        return '<p>WooCommerce is not active.</p>';
    }

    $atts = shortcode_atts( [
        'category' => '',
        'limit'    => 8,
        'columns'  => 4,
        'orderby'  => 'date',
        'order'    => 'DESC',
        'on_sale'  => '',
        'featured' => '',
    ], $atts, 'hec_product_loop' );

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => (int) $atts['limit'],
        'orderby'        => $atts['orderby'],
        'order'          => $atts['order'],
    ];

    if ( ! empty( $atts['category'] ) ) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
            ],
        ];
    }

    if ( 'yes' === $atts['on_sale'] ) {
        $sale_ids = wc_get_product_ids_on_sale();
        if ( empty( $sale_ids ) ) {
            return '<p>No products on sale right now.</p>';
        }
        $args['post__in'] = $sale_ids;
    }

    if ( 'yes' === $atts['featured'] ) {
        $args['tax_query'][] = [
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => 'featured',
        ];
    }

    $products = new WP_Query( $args );

    if ( ! $products->have_posts() ) {
        return '<p>No products found.</p>';
    }

    ob_start();
    ?>
    <div class="hec-product-grid" style="--hec-grid-columns: <?php echo (int) $atts['columns']; ?>;">
        <?php
        while ( $products->have_posts() ) :
            $products->the_post();
            hec_render_product_card();
        endwhile;
        ?>
    </div>
    <?php
    wp_reset_postdata();

    return ob_get_clean();
});