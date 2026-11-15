<?php
/**
 * Cart toast + mini-cart wiggle on add-to-cart.
 *
 * Listens for WooCommerce's own `added_to_cart` body event — already
 * fired everywhere an ajax_add_to_cart button exists (product loop,
 * product details, related products, etc.), no extra wiring needed —
 * and:
 *   1. Shows a dismissible toast — "Added to cart. Complete your
 *      order" — linking straight to checkout, with a close (×)
 *      button.
 *   2. Briefly wiggles every `.hec-mini-cart-icon-wrap` on the page —
 *      there can be more than one (desktop + mobile header instances,
 *      see mini-cart.php/js) — so the cart icon draws the eye.
 *
 * Left off CartFlows funnel steps, same as the rest of this theme's
 * chrome — those pages are meant to stay untouched.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {

    if ( is_admin() || is_singular( 'cartflows_step' ) ) {
        return;
    }

    wp_enqueue_style(
        'hec-cart-toast',
        HEC_THEME_URI . '/assets/css/cart-toast.css',
        [ 'ecomx-style' ],
        HEC_THEME_VERSION
    );

    wp_enqueue_script(
        'hec-cart-toast-js',
        HEC_THEME_URI . '/assets/js/cart-toast.js',
        [ 'jquery' ],
        HEC_THEME_VERSION,
        true
    );

    wp_localize_script( 'hec-cart-toast-js', 'hecCartToastData', [
        'checkoutUrl'  => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
        'message'      => 'Added to cart!',
        'checkoutText' => 'Checkout now',
        'continueText' => 'Continue shopping',
    ] );
});
