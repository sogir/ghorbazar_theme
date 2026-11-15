<?php
/**
 * Mini cart dropdown — [hec_mini_cart]
 *
 * A cart icon trigger (place in the header via a Shortcode widget)
 * that opens a dropdown panel on hover (desktop) or tap (mobile),
 * showing cart items with thumbnail/name/qty/price, a remove button
 * per item, the cart total, and a checkout button.
 *
 * Desktop trigger: icon + price + qty badge.
 * Mobile trigger:  icon + qty badge only (no price, saves space).
 *
 * Item removal reuses WooCommerce's own built-in AJAX removal
 * mechanism (the `remove_from_cart_button` class + `data-cart_item_key`
 * — handled automatically by WooCommerce's wc-cart-fragments.js,
 * already loaded elsewhere on this site) rather than a custom AJAX
 * handler, so cart totals/fragments everywhere stay correctly in sync
 * with zero extra server code.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the inner content of the dropdown panel (item list + total
 * + checkout button). Shared by the shortcode's first render and by
 * the fragment refresh, so both are always identical markup.
 */
function hec_render_mini_cart_panel() {

    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return;
    }

    $cart_items = WC()->cart->get_cart();
    ?>
    <div class="hec-mini-cart-panel">

        <?php if ( empty( $cart_items ) ) : ?>

            <div class="hec-mini-cart-empty">
                <p>Your cart is empty</p>
            </div>

        <?php else : ?>

            <div class="hec-mini-cart-items">
                <?php foreach ( $cart_items as $cart_item_key => $cart_item ) :
                    $product = $cart_item['data'];
                    if ( ! $product || ! $product->exists() || $cart_item['quantity'] <= 0 ) {
                        continue;
                    }
                    ?>
                    <div class="hec-mini-cart-item">
                        <a href="<?php echo esc_url( $product->get_permalink( $cart_item ) ); ?>" class="hec-mini-cart-thumb">
                            <?php echo $product->get_image( 'thumbnail' ); ?>
                        </a>

                        <div class="hec-mini-cart-item-info">
                            <a href="<?php echo esc_url( $product->get_permalink( $cart_item ) ); ?>" class="hec-mini-cart-item-name">
                                <?php echo esc_html( wp_trim_words( $product->get_name(), 6 ) ); ?>
                            </a>
                            <span class="hec-mini-cart-item-qty">Qty: <?php echo (int) $cart_item['quantity']; ?></span>
                        </div>

                        <div class="hec-mini-cart-item-price">
                            <?php echo wp_kses_post( WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ) ); ?>
                        </div>

                        <a href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>"
                           class="hec-mini-cart-remove remove remove_from_cart_button"
                           data-cart_item_key="<?php echo esc_attr( $cart_item_key ); ?>"
                           data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
                           data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
                           aria-label="Remove item">&times;</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="hec-mini-cart-total">
                Total: <?php echo wp_kses_post( hec_mini_cart_total_html() ); ?>
            </div>

            <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="hec-mini-cart-checkout">
                Checkout
            </a>

        <?php endif; ?>

    </div>
    <?php
}

/**
 * Cart total for the mini cart. Shows 0 for an empty cart even if the
 * session still holds stale totals (e.g. a product in the cart was
 * deleted or went out of stock, which empties the cart without
 * recalculating the stored totals).
 */
function hec_mini_cart_total_html() {
    if ( WC()->cart->is_empty() ) {
        return wc_price( 0 );
    }
    return WC()->cart->get_cart_total();
}

add_shortcode( 'hec_mini_cart', function () {

    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return '';
    }

    $count = WC()->cart->get_cart_contents_count();

    ob_start();
    ?>
    <div class="hec-mini-cart">

        <button type="button" class="hec-mini-cart-trigger" aria-label="Cart">
            <span class="hec-mini-cart-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M6 6h15l-1.5 9h-12L6 6zm0 0L5 3H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1" fill="currentColor"/><circle cx="18" cy="20" r="1" fill="currentColor"/></svg>
                <span class="hec-mini-cart-count"><?php echo (int) $count; ?></span>
            </span>
            <span class="hec-mini-cart-price">
                <?php echo wp_kses_post( hec_mini_cart_total_html() ); ?>
            </span>
        </button>

        <?php hec_render_mini_cart_panel(); ?>

    </div>
    <?php
    return ob_get_clean();
});

/**
 * Keep the trigger (count + price) and the dropdown panel content in
 * sync with the cart automatically, via WooCommerce's own fragment
 * refresh system — this fires after both add-to-cart AND the built-in
 * remove_from_cart AJAX action, so no custom AJAX handler is needed
 * for item removal.
 *
 * Class selectors (not IDs) on purpose: the [hec_mini_cart] shortcode
 * is placed in both the desktop and mobile header rows, so more than
 * one instance of this markup exists in the DOM at once. jQuery's ID
 * selector fast-path resolves through getElementById, which only ever
 * matches the FIRST element with that id — with duplicate ids that
 * silently updated only one instance (desktop) and left the other
 * (mobile) stale. Class selectors update every instance on the page.
 */
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {

    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return $fragments;
    }

    ob_start();
    ?>
    <span class="hec-mini-cart-count"><?php echo (int) WC()->cart->get_cart_contents_count(); ?></span>
    <?php
    $fragments['.hec-mini-cart-count'] = ob_get_clean();

    ob_start();
    ?>
    <span class="hec-mini-cart-price">
        <?php echo wp_kses_post( hec_mini_cart_total_html() ); ?>
    </span>
    <?php
    $fragments['.hec-mini-cart-price'] = ob_get_clean();

    ob_start();
    hec_render_mini_cart_panel();
    $fragments['.hec-mini-cart-panel'] = ob_get_clean();

    return $fragments;
});
