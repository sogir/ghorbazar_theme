<?php
/**
 * Custom checkout page — replaces WooCommerce's default checkout with
 * a minimal COD-style layout: Name / Phone / Address only, delivery
 * area shown as radio tiles (driven by your real WooCommerce shipping
 * rates), and a right-hand order summary with coupon, per-item
 * quantity +/- and remove, and totals.
 *
 * Order submission reuses WooCommerce's own real checkout flow (the
 * actual #place_order button rendered from checkout/payment.php) —
 * our big styled order button just clicks that hidden button via JS,
 * so payment/order-creation logic is 100% native WooCommerce.
 *
 * Quantity/remove use small custom AJAX endpoints. Shipping-method
 * change and coupon apply go through WooCommerce's own native
 * 'update_checkout' flow, which now includes our summary table as a
 * registered fragment (see woocommerce_update_order_review_fragments
 * below) — that's what makes the delivery-area total update instantly.
 *
 * IMPORTANT SETUP NOTE: the delivery-area radio options come from your
 * actual configured WooCommerce shipping rates (WooCommerce → Settings
 * → Shipping) — set up flat rate methods labeled to match
 * HEC_DELIVERY_OUTSIDE_DHAKA / HEC_DELIVERY_INSIDE_DHAKA from
 * config.php, each in its own shipping zone, for both the amounts and
 * the "always showing free" symptom to resolve — that isn't a code
 * bug, it means no costed rate is currently matching.
 */

defined( 'ABSPATH' ) || exit;

/**
 * CartFlows renders its own checkout step pages as `is_checkout() === true`
 * (needed so native WooCommerce checkout scripts work there), which means
 * every `is_checkout()` guard below would otherwise also fire on CartFlows
 * checkout steps and hijack them. Detect those pages (post type
 * `cartflows_step`) so this whole file leaves CartFlows completely alone.
 */
function hec_is_cartflows_checkout() {
    return is_singular( 'cartflows_step' );
}

/**
 * ---- Field filtering: only Name / Phone / Address ----
 */
add_filter( 'woocommerce_checkout_fields', function ( $fields ) {

    if ( hec_is_cartflows_checkout() ) {
        return $fields;
    }

    $keep_billing = [ 'billing_first_name', 'billing_phone', 'billing_address_1' ];
    foreach ( $fields['billing'] as $key => $field ) {
        if ( ! in_array( $key, $keep_billing, true ) ) {
            unset( $fields['billing'][ $key ] );
        }
    }

    if ( isset( $fields['billing']['billing_first_name'] ) ) {
        $fields['billing']['billing_first_name']['label']       = 'Your name';
        $fields['billing']['billing_first_name']['placeholder'] = 'Full name';
        $fields['billing']['billing_first_name']['class']       = [ 'hec-co-field-full' ];
        $fields['billing']['billing_first_name']['priority']    = 10;
    }
    if ( isset( $fields['billing']['billing_phone'] ) ) {
        $fields['billing']['billing_phone']['label']       = 'Your phone number';
        $fields['billing']['billing_phone']['placeholder'] = '01XXXXXXXXX';
        $fields['billing']['billing_phone']['class']       = [ 'hec-co-field-full' ];
        $fields['billing']['billing_phone']['priority']    = 20;
    }
    if ( isset( $fields['billing']['billing_address_1'] ) ) {
        $fields['billing']['billing_address_1']['type']        = 'textarea';
        $fields['billing']['billing_address_1']['label']       = 'Full address';
        $fields['billing']['billing_address_1']['placeholder'] = 'House, road, area, district';
        $fields['billing']['billing_address_1']['class']       = [ 'hec-co-field-full' ];
        $fields['billing']['billing_address_1']['priority']    = 30;
    }

    $fields['shipping'] = [];
    unset( $fields['order']['order_comments'] );

    return $fields;
}, 20 );

/**
 * Auto-fill fields WooCommerce still wants internally even though we
 * don't display them, so order processing doesn't break. Deliberately
 * does NOT fabricate billing_last_name, billing_email, or billing_city
 * — those are simply left blank/empty when the customer didn't
 * provide them (there's no city field on this checkout at all; the
 * actual area/district is already captured as free text inside
 * billing_address_1). A hardcoded "Dhaka" city here would silently
 * apply to every order regardless of the customer's real delivery
 * area, since this field is always empty coming from this checkout.
 */
add_filter( 'woocommerce_checkout_posted_data', function ( $data ) {
    if ( hec_is_cartflows_checkout() ) {
        return $data;
    }
    if ( empty( $data['billing_country'] ) ) {
        $data['billing_country'] = WC()->countries->get_base_country();
    }
    $data['shipping_country']    = $data['billing_country'];
    $data['shipping_first_name'] = $data['billing_first_name'] ?? '';
    $data['shipping_address_1']  = $data['billing_address_1'] ?? '';
    $data['shipping_city']       = $data['billing_city'] ?? '';

    return $data;
});

add_filter( 'woocommerce_order_button_text', function ( $text ) {
    return hec_is_cartflows_checkout() ? $text : 'Confirm order';
});

/**
 * ---- Override the [woocommerce_checkout] shortcode with our layout ----
 */
add_action( 'wp', function () {

    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || hec_is_cartflows_checkout() ) {
        return;
    }

    if ( is_wc_endpoint_url() && ! is_order_received_page() ) {
        return;
    }

    // "order-received" is a WooCommerce endpoint segment in the URL
    // path (/checkout/order-received/123/), not a $_GET param — it
    // must never be read via $_GET, that's always empty. WooCommerce
    // also empties the cart right before redirecting here, so without
    // the is_order_received_page() check below, an empty cart on the
    // real thank-you page would wrongly look identical to an empty
    // cart anywhere else on the checkout — and skip our override
    // entirely, falling back to WooCommerce's default unstyled
    // thank-you template.
    if ( WC()->cart && WC()->cart->is_empty() && ! is_order_received_page() ) {
        return;
    }

    add_shortcode( 'woocommerce_checkout', 'hec_render_custom_checkout' );
    remove_all_actions( 'woocommerce_checkout_order_review' );
    add_action( 'woocommerce_checkout_order_review', 'hec_render_order_review', 10 );
});

function hec_render_custom_checkout() {

    if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
        return hec_render_thankyou_page();
    }

    if ( WC()->cart->is_empty() ) {
        ob_start();
        wc_print_notice( 'Your cart is empty.', 'notice' );
        echo '<p><a class="button wc-backward" href="' . esc_url( home_url( '/' ) ) . '">Back to home</a></p>';
        return ob_get_clean();
    }

    WC()->cart->calculate_totals();

    do_action( 'woocommerce_before_checkout_form', WC()->checkout() );

    ob_start();
    ?>
    <div class="hec-checkout-wrap">
        <form name="checkout" method="post" class="checkout woocommerce-checkout hec-checkout-form"
              action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

            <div class="hec-checkout-col hec-checkout-col-left">

                <div class="hec-checkout-instructions">
                    Fill in your details and click <strong>"Confirm order"</strong> to place your order, or call
                    <?php if ( defined( 'HEC_PHONE_NUMBER' ) ) : ?>
                        <a href="tel:<?php echo esc_attr( HEC_PHONE_NUMBER ); ?>"><?php echo esc_html( HEC_PHONE_NUMBER ); ?></a>
                    <?php endif; ?>
                    to order by phone.
                </div>

                <?php hec_render_checkout_fields(); ?>

                <?php hec_render_delivery_area(); ?>

                <div class="hec-co-order-btn-wrap">
                    <button type="button" id="hec-co-place-order-btn" class="hec-co-order-btn hec-co-place-order-trigger">Confirm order</button>
                </div>

            </div>

            <div class="hec-checkout-col hec-checkout-col-right">
                <div class="hec-co-summary-card">
                    <h3 class="hec-co-summary-title">Order summary</h3>

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

                    <div id="order_review" class="woocommerce-checkout-review-order">
                        <?php do_action( 'woocommerce_checkout_order_review' ); ?>
                    </div>
                </div>
            </div>

        </form>
    </div>
    <?php
    $html = ob_get_clean();

    do_action( 'woocommerce_after_checkout_form', WC()->checkout() );

    return $html;
}

/**
 * Minimal, on-brand order-received (thank you) page. Same card style
 * as the checkout itself — order number, status, items ordered, and
 * the total. No account/tracking clutter, matching the "minimal info"
 * request.
 */
function hec_render_thankyou_page() {

    $order_id = absint( get_query_var( 'order-received' ) );
    $order    = $order_id ? wc_get_order( $order_id ) : false;

    ob_start();
    ?>
    <div class="hec-checkout-wrap hec-thankyou-wrap">
        <div class="hec-thankyou-card">

            <?php if ( $order && hash_equals( $order->get_order_key(), wc_clean( wp_unslash( $_GET['key'] ?? '' ) ) ) ) : ?>

                <div class="hec-thankyou-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="48" height="48"><circle cx="12" cy="12" r="11" stroke="currentColor" stroke-width="1.5"/><path d="M7 12.5l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>

                <h1 class="hec-thankyou-title">Thank you! Your order has been placed</h1>
                <p class="hec-thankyou-sub">Order number <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong> — we will contact you shortly.</p>

                <div class="hec-thankyou-items">
                    <?php foreach ( $order->get_items() as $item ) :
                        $product = $item->get_product();
                        ?>
                        <div class="hec-thankyou-item">
                            <?php if ( $product ) : ?>
                                <?php echo $product->get_image( [ 44, 44 ] ); ?>
                            <?php endif; ?>
                            <span class="hec-thankyou-item-name"><?php echo esc_html( $item->get_name() ); ?> × <?php echo (int) $item->get_quantity(); ?></span>
                            <span class="hec-thankyou-item-price"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="hec-thankyou-total">
                    <span>Total</span>
                    <span><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
                </div>

            <?php else : ?>
                <h1 class="hec-thankyou-title">Thank you!</h1>
                <p class="hec-thankyou-sub">Your order has been received successfully.</p>
            <?php endif; ?>

            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hec-thankyou-btn">Continue shopping</a>

        </div>
    </div>
    <?php
    return ob_get_clean();
}

function hec_render_checkout_fields() {
    $checkout = WC()->checkout();
    $fields   = $checkout->get_checkout_fields( 'billing' );

    foreach ( $fields as $key => $field ) {
        echo '<div class="hec-co-field">';
        woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
        echo '</div>';
    }
}

function hec_render_delivery_area() {
    if ( ! WC()->cart->needs_shipping() ) {
        return;
    }

    $packages = WC()->shipping()->get_packages();
    if ( empty( $packages ) ) {
        return;
    }

    $has_rates = false;
    foreach ( $packages as $package ) {
        if ( ! empty( $package['rates'] ) ) {
            $has_rates = true;
            break;
        }
    }
    if ( ! $has_rates ) {
        return;
    }

    echo '<div class="hec-co-delivery-wrap">';
    echo '<p class="hec-co-delivery-label">Select delivery area <span>*</span></p>';

    foreach ( $packages as $i => $package ) {
        if ( empty( $package['rates'] ) ) {
            continue;
        }
        $chosen = WC()->session->get( 'chosen_shipping_methods' );
        $chosen = isset( $chosen[ $i ] ) ? $chosen[ $i ] : '';

        echo '<div class="hec-co-delivery-options">';
        foreach ( $package['rates'] as $rate ) {
            $checked = ( $chosen === $rate->id || ( ! $chosen && $rate === reset( $package['rates'] ) ) );
            printf(
                '<label class="hec-co-delivery-option">
                    <input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s>
                    <span class="hec-co-radio-dot"></span>
                    <span class="hec-co-delivery-text">%5$s %6$s</span>
                </label>',
                absint( $i ),
                esc_attr( sanitize_title( $rate->id ) ),
                esc_attr( $rate->id ),
                checked( $checked, true, false ),
                esc_html( $rate->label ),
                wp_kses_post( $rate->cost > 0 ? wc_price( $rate->cost ) : 'Free' )
            );
        }
        echo '</div>';
    }
    echo '</div>';
}

function hec_render_order_review() {
    hec_render_order_summary_table();

    // Real WooCommerce payment method list + the actual working submit
    // button — fully hidden (zero space) since this is a COD-only
    // checkout, but the Terms & Conditions checkbox (if your store
    // requires one) is rendered separately and stays visible, so
    // required consent can never be silently hidden/unchecked.
    echo '<div class="hec-co-payment-wrap">';
    wc_get_template( 'checkout/payment.php', [
        'checkout'           => WC()->checkout(),
        'available_gateways' => WC()->payment_gateways()->get_available_payment_gateways(),
        'order_button_text'  => apply_filters( 'woocommerce_order_button_text', 'Confirm order' ),
    ] );
    echo '</div>';

    if ( function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) && wc_terms_and_conditions_checkbox_enabled() ) {
        echo '<div class="hec-co-terms-visible">';
        wc_get_template( 'checkout/terms.php', [ 'checkout' => WC()->checkout() ] );
        echo '</div>';
    }
}

function hec_render_order_summary_table( $show_disclaimer = true ) {
    $cart = WC()->cart;
    ?>
    <div id="hec-co-summary-table-wrap">

        <div class="hec-co-table">

            <div class="hec-co-thead">
                <div class="hec-co-th hec-co-col-del"></div>
                <div class="hec-co-th hec-co-col-product">Product</div>
                <div class="hec-co-th hec-co-col-qty">Qty</div>
                <div class="hec-co-th hec-co-col-price">Price</div>
            </div>

            <div class="hec-co-tbody">
                <?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) :
                    $product = $cart_item['data'];
                    if ( ! $product || ! $product->exists() || $cart_item['quantity'] <= 0 ) {
                        continue;
                    }
                    ?>
                    <div class="hec-co-row" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
                        <div class="hec-co-cell hec-co-col-del">
                            <button type="button" class="hec-co-remove-btn" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="Remove item">
                                <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2m2 0v14a1 1 0 01-1 1H7a1 1 0 01-1-1V6h12z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                        <div class="hec-co-cell hec-co-col-product">
                            <div class="hec-co-prod-inner">
                                <?php echo $product->get_image( [ 40, 40 ] ); ?>
                                <span class="hec-co-prod-name"><?php echo esc_html( wp_trim_words( $product->get_name(), 8 ) ); ?></span>
                            </div>
                        </div>
                        <div class="hec-co-cell hec-co-col-qty">
                            <div class="hec-co-qty-stepper">
                                <button type="button" class="hec-co-qty-minus" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">−</button>
                                <span class="hec-co-qty-val"><?php echo (int) $cart_item['quantity']; ?></span>
                                <button type="button" class="hec-co-qty-plus" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">+</button>
                            </div>
                        </div>
                        <div class="hec-co-cell hec-co-col-price">
                            <?php echo wp_kses_post( $cart->get_product_subtotal( $product, $cart_item['quantity'] ) ); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="hec-co-tfoot">
                <div class="hec-co-total-row">
                    <span>Subtotal</span>
                    <span class="hec-co-col-price"><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></span>
                </div>
                <?php foreach ( $cart->get_coupons() as $code => $coupon ) : ?>
                    <div class="hec-co-total-row hec-co-discount-row">
                        <span><?php echo esc_html( wc_cart_totals_coupon_label( $coupon ) ); ?></span>
                        <span class="hec-co-col-price">−<?php echo wp_kses_post( wc_price( $cart->get_coupon_discount_amount( $code, $cart->display_cart_ex_tax ) ) ); ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ( $cart->needs_shipping() && $cart->show_shipping() ) : ?>
                    <div class="hec-co-total-row">
                        <span>Delivery charge</span>
                        <span class="hec-co-col-price"><?php echo wp_kses_post( $cart->get_cart_shipping_total() ); ?></span>
                    </div>
                <?php endif; ?>
                <div class="hec-co-total-row hec-co-grand-total">
                    <span>Total</span>
                    <span class="hec-co-col-price"><?php echo wp_kses_post( $cart->get_total() ); ?></span>
                </div>
            </div>

        </div>

        <?php if ( $show_disclaimer ) : ?>
        <p class="hec-co-disclaimer">
            Note: Please order only when you are 100% sure. If the product matches its photos and description but you decide not to accept it, please pay the 120 TK courier charge to the delivery person when returning it.
            Want more products for the same delivery charge? <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Click here</a>
        </p>

        <!-- Mobile-only: a second order-confirm trigger here, so on a
             long scrolled-down phone screen the customer doesn't have
             to scroll back up to the left column's button after
             reviewing the order summary. Hidden on desktop via CSS;
             shares the same class as the main button so one JS
             handler covers both. -->
        <div class="hec-co-mobile-order-btn-wrap">
            <button type="button" class="hec-co-order-btn hec-co-place-order-trigger">Confirm order</button>
        </div>
        <?php endif; ?>

    </div>
    <?php
}

/**
 * Registers our summary table as a fragment for WooCommerce's own
 * native 'update_checkout' AJAX response (fires on shipping-method
 * change and coupon apply). Without this, WooCommerce recalculates
 * everything correctly server-side, but never had a matching selector
 * to know it should refresh OUR custom table — this is what makes the
 * delivery-area total update instantly instead of only after a manual
 * page action.
 */
add_filter( 'woocommerce_update_order_review_fragments', function ( $fragments ) {
    if ( hec_is_cartflows_checkout() ) {
        return $fragments;
    }
    ob_start();
    hec_render_order_summary_table();
    $fragments['#hec-co-summary-table-wrap'] = ob_get_clean();
    return $fragments;
});

/**
 * ---- AJAX: remove item ----
 */
add_action( 'wp_ajax_hec_checkout_remove_item', 'hec_ajax_checkout_remove_item' );
add_action( 'wp_ajax_nopriv_hec_checkout_remove_item', 'hec_ajax_checkout_remove_item' );
function hec_ajax_checkout_remove_item() {
    check_ajax_referer( 'hec_checkout_nonce', 'nonce' );

    $cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
    $cart = WC()->cart;

    if ( ! $cart_item_key || ! $cart || ! isset( $cart->get_cart()[ $cart_item_key ] ) ) {
        wp_send_json_error( [ 'message' => 'Item not found.' ] );
    }

    $cart->remove_cart_item( $cart_item_key );
    $cart->calculate_totals();

    ob_start();
    hec_render_order_summary_table();
    $table_html = ob_get_clean();

    wp_send_json_success( [
        'table_html' => $table_html,
        'cart_count' => $cart->get_cart_contents_count(),
    ] );
}

/**
 * ---- AJAX: update quantity ----
 */
add_action( 'wp_ajax_hec_checkout_update_qty', 'hec_ajax_checkout_update_qty' );
add_action( 'wp_ajax_nopriv_hec_checkout_update_qty', 'hec_ajax_checkout_update_qty' );
function hec_ajax_checkout_update_qty() {
    check_ajax_referer( 'hec_checkout_nonce', 'nonce' );

    $cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
    $qty           = isset( $_POST['qty'] ) ? max( 1, absint( $_POST['qty'] ) ) : 1;
    $cart          = WC()->cart;

    if ( ! $cart_item_key || ! $cart || ! isset( $cart->get_cart()[ $cart_item_key ] ) ) {
        wp_send_json_error( [ 'message' => 'Item not found.' ] );
    }

    $cart->set_quantity( $cart_item_key, $qty, true );
    $cart->calculate_totals();

    ob_start();
    hec_render_order_summary_table();
    $table_html = ob_get_clean();

    wp_send_json_success( [
        'table_html' => $table_html,
        'cart_count' => $cart->get_cart_contents_count(),
    ] );
}

/**
 * ---- Enqueue assets, checkout page only ----
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || hec_is_cartflows_checkout() ) {
        return;
    }

    if ( is_wc_endpoint_url() && ! is_order_received_page() ) {
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
        [ 'jquery', 'wc-checkout' ],
        HEC_THEME_VERSION,
        true
    );

    wp_localize_script( 'hec-checkout-js', 'hecCheckoutAjax', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'hec_checkout_nonce' ),
        'homeUrl' => home_url( '/' ),
    ] );
});

/**
 * Checkout errors: WooCommerce prefixes field names with "Billing"
 * ("Billing Your name is a required field."). This checkout only has
 * one set of fields, so drop the prefix.
 */
add_filter( 'woocommerce_checkout_required_field_notice', function ( $notice ) {
    return str_replace( [ '>Billing ', 'Billing ' ], [ '>', '' ], $notice );
} );
add_filter( 'woocommerce_add_error', function ( $message ) {
    return is_string( $message ) ? preg_replace( '/(<strong>)?Billing (?=[A-Z])/', '$1', $message ) : $message;
} );
