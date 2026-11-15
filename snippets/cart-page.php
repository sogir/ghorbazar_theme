<?php
/**
 * Custom cart page — replaces WooCommerce's default [woocommerce_cart]
 * output with the same visual language as the custom checkout: the
 * same div-based order table, coupon box, and card styling, so it
 * automatically matches without separate CSS. Quantity +/- and
 * remove reuse the exact same AJAX endpoints registered in
 * checkout-custom.php (they just operate on WC()->cart generically),
 * and checkout.css/checkout.js are reused here rather than duplicated
 * — see the broadened enqueue condition in checkout-custom.php.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp', function () {
    if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
        return;
    }
    add_shortcode( 'woocommerce_cart', 'hec_render_cart_page' );
});

function hec_render_cart_page() {

    if ( WC()->cart->is_empty() ) {
        ob_start();
        ?>
        <div class="hec-checkout-wrap">
            <div class="hec-co-summary-card hec-cart-empty-card">
                <div class="hec-cart-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="40" height="40"><path d="M6 6h15l-1.5 9h-12L6 6zm0 0L5 3H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1" fill="currentColor"/><circle cx="18" cy="20" r="1" fill="currentColor"/></svg>
                </div>
                <p class="hec-cart-empty-text">Your cart is empty</p>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hec-co-order-btn hec-cart-empty-btn">Start shopping</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    WC()->cart->calculate_totals();

    ob_start();
    ?>
    <div class="hec-checkout-wrap hec-cart-page-wrap">
        <div class="hec-co-summary-card">
            <h3 class="hec-co-summary-title">Your cart</h3>

            <?php if ( wc_coupons_enabled() ) : ?>
                <div class="hec-co-coupon">
                    <p class="hec-co-coupon-label">
                        <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M3 10l8-6 8 6M4 9v9a1 1 0 001 1h4v-6h6v6h4a1 1 0 001-1V9" stroke="currentColor" stroke-width="1.8"/></svg>
                        Have a coupon code?
                    </p>
                    <div class="hec-co-coupon-row">
                        <input type="text" id="hec-co-coupon-input" placeholder="Enter coupon code...">
                        <button type="button" id="hec-co-coupon-apply">Apply</button>
                    </div>
                    <div id="hec-co-coupon-msg"></div>
                </div>
            <?php endif; ?>

            <?php hec_render_order_summary_table( false ); ?>

            <div class="hec-cart-page-actions">
                <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="hec-co-order-btn">Proceed to checkout</a>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hec-cart-continue-link">Continue shopping</a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Coupon apply on the cart page needs WooCommerce's own coupon nonce
 * available client-side too — wc_checkout_params only localizes on
 * checkout, so wc-cart's own equivalent is used here via a small
 * dedicated localize call, reusing checkout.js's existing coupon
 * handler code path (same wc_checkout_params-shaped object).
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
        return;
    }

    wp_enqueue_style(
        'hec-checkout',
        HEC_THEME_URI . '/assets/css/checkout.css',
        [ 'ecomx-style' ],
        HEC_THEME_VERSION
    );

    wp_enqueue_script(
        'hec-checkout-js',
        HEC_THEME_URI . '/assets/js/checkout.js',
        [ 'jquery', 'wc-cart-fragments' ],
        HEC_THEME_VERSION,
        true
    );

    wp_localize_script( 'hec-checkout-js', 'hecCheckoutAjax', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'hec_checkout_nonce' ),
        'homeUrl' => home_url( '/' ),
    ] );

    // checkout.js's coupon handler reads from the global
    // `wc_checkout_params` object, which WooCommerce only localizes
    // on the actual checkout page — provide the two properties it
    // needs here too so the same handler works unmodified on /cart/.
    if ( ! wp_script_is( 'wc-checkout', 'enqueued' ) ) {
        wp_add_inline_script( 'hec-checkout-js', 'var wc_checkout_params = wc_checkout_params || { wc_ajax_url: "' . esc_js( WC_AJAX::get_endpoint( '%%endpoint%%' ) ) . '", apply_coupon_nonce: "' . esc_js( wp_create_nonce( 'apply-coupon' ) ) . '" };', 'before' );
    }
});
