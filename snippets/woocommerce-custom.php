<?php
/**
 * WooCommerce customizations — hooks, filters, cart/product logic.
 * Keep checkout-specific code in checkout-custom.php instead.
 */

defined( 'ABSPATH' ) || exit;

// Example: remove default WooCommerce breadcrumbs
// add_action( 'init', function () {
//     remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
// });

// Example: change "Add to cart" text
// add_filter( 'woocommerce_product_add_to_cart_text', function ( $text ) {
//     return __( 'Order Now', 'ecomx-bookshop' );
// });

// Show prices as "1000 TK." (English) instead of the Bengali Taka sign.
add_filter( 'woocommerce_currency_symbol', function ( $symbol, $currency ) {
    return 'BDT' === $currency ? 'TK.' : $symbol;
}, 10, 2 );

// WooCommerce caches the mini-cart HTML in each visitor's browser
// (sessionStorage) and only refreshes it when the cart changes. Tie that
// cache key to the theme version so text/markup changes in a theme update
// show up immediately instead of serving the old copy.
add_filter( 'woocommerce_cart_fragment_name', function ( $name ) {
    return $name . '_' . md5( HEC_THEME_VERSION );
} );

/*
 * Hide WooCommerce's default success/info notices — the theme has its own
 * UI for these (cart toast, mini cart, its own coupon box at checkout).
 * Error notices always show (checkout validation, out of stock, etc.),
 * and My Account keeps all notices (login, password reset, saved address).
 */
add_filter( 'woocommerce_add_to_cart_message_html', '__return_empty_string' );
add_filter( 'wc_add_to_cart_message_html', '__return_empty_string' );

add_filter( 'woocommerce_notice_types', function ( $types ) {
    if ( function_exists( 'is_account_page' ) && is_account_page() ) {
        return $types;
    }
    return array_values( array_intersect( $types, [ 'error' ] ) );
} );

// "Have a coupon? Click here to enter your code" bar above checkout.
add_action( 'wp', function () {
    remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
} );
