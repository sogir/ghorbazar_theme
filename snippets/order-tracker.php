<?php
/**
 * Phone order tracker — [hec_order_tracker]
 *
 * Lets a customer look up their own orders by phone number, no
 * account/login needed — same convenience pattern as the WhatsApp/
 * phone contact buttons elsewhere on this site. Styled to match the
 * theme's own card/button language (same classes families as
 * checkout/cart/thank-you) rather than a standalone design, and uses
 * inline SVG icons instead of an icon-font CDN — consistent with how
 * every other component on this site does icons, and one less
 * external asset to load.
 *
 * Matches by $order->get_billing_phone() (bounded to the 500 most
 * recent orders) rather than a meta_query on _billing_phone — under
 * WooCommerce's High-Performance Order Storage, billing_phone is a
 * real column on WooCommerce's own orders table, not post meta, so a
 * meta_query on _billing_phone silently matches nothing there.
 * get_billing_phone() is the official API and works identically
 * whether HPOS is on or off.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'hec_order_tracker', 'hec_render_order_tracker' );

function hec_render_order_tracker() {

    if ( ! function_exists( 'wc_get_orders' ) ) {
        return '<p>WooCommerce is not active.</p>';
    }

    $results     = [];
    $phone_input = '';
    $searched    = false;
    $error       = '';

    if ( isset( $_POST['hec_ot_phone'] ) ) {

        if ( ! isset( $_POST['hec_ot_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['hec_ot_nonce'] ), 'hec_order_tracker' ) ) {
            $error = 'Sorry, please try again.';
        } else {

            $phone_input = sanitize_text_field( wp_unslash( $_POST['hec_ot_phone'] ) );

            // Same normalisation the checkout phone field expects
            // (see checkout-custom.php: placeholder "01XXXXXXXXX") —
            // strip everything but digits, drop a leading 880.
            $phone_clean = preg_replace( '/\D/', '', $phone_input );
            if ( strlen( $phone_clean ) > 10 && '880' === substr( $phone_clean, 0, 3 ) ) {
                $phone_clean = substr( $phone_clean, 3 );
            }

            if ( strlen( $phone_clean ) < 10 ) {
                $error = 'Please enter a valid mobile number.';
            } else {
                $searched = true;

                // Matched via $order->get_billing_phone() rather than a
                // meta_query on _billing_phone: under WooCommerce's
                // High-Performance Order Storage (HPOS — on by default
                // since WC 8.2), billing_phone is a real column on
                // WooCommerce's own orders table, not post meta, so a
                // meta_query against _billing_phone silently matches
                // nothing there. get_billing_phone() is the official API
                // and works identically either way. Bounded to the most
                // recent orders (rather than the whole order history) to
                // keep this from getting slower as the store grows.
                $recent_orders = wc_get_orders( [
                    'limit'   => 500,
                    'status'  => 'any',
                    'orderby' => 'date',
                    'order'   => 'DESC',
                ] );

                foreach ( $recent_orders as $order ) {
                    $order_phone = preg_replace( '/\D/', '', $order->get_billing_phone() );
                    if ( strlen( $order_phone ) > 10 && '880' === substr( $order_phone, 0, 3 ) ) {
                        $order_phone = substr( $order_phone, 3 );
                    }
                    if ( $order_phone === $phone_clean ) {
                        $results[] = $order;
                    }
                }
            }
        }
    }

    $status_map = [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'on-hold'    => 'On hold',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
        'refunded'   => 'Refunded',
        'failed'     => 'Failed',
        'shipped'    => 'Shipped',
    ];

    ob_start();
    ?>
    <div class="hec-ot-wrap">

        <div class="hec-ot-header">
            <h2 class="hec-ot-title">Track your order</h2>
            <p class="hec-ot-sub">Enter your mobile number to see the latest status of your orders</p>
        </div>

        <form method="post" class="hec-ot-form">
            <?php wp_nonce_field( 'hec_order_tracker', 'hec_ot_nonce' ); ?>

            <div class="hec-ot-field">
                <label class="hec-ot-label" for="hec-ot-phone">Mobile number</label>
                <input type="tel" id="hec-ot-phone" name="hec_ot_phone" class="hec-ot-input"
                       placeholder="01XXXXXXXXX" maxlength="14" inputmode="numeric"
                       value="<?php echo esc_attr( $phone_input ); ?>" required>
            </div>

            <?php if ( $error ) : ?>
                <p class="hec-ot-error"><?php echo esc_html( $error ); ?></p>
            <?php endif; ?>

            <button type="submit" class="hec-ot-submit">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                Find orders
            </button>
        </form>

        <p class="hec-ot-secure">
            <svg viewBox="0 0 24 24" fill="none" width="13" height="13"><rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 10V7a4 4 0 018 0v3" stroke="currentColor" stroke-width="1.8"/></svg>
            Your information is kept completely private
        </p>

        <?php if ( $searched ) : ?>

            <?php if ( ! empty( $results ) ) : ?>

                <div class="hec-ot-result-header">
                    <svg viewBox="0 0 24 24" fill="none" width="17" height="17"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.6"/><path d="M8 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="hec-ot-result-title">Your orders</span>
                    <span class="hec-ot-result-count"><?php echo (int) count( $results ); ?> <?php echo 1 === count( $results ) ? 'order' : 'orders'; ?></span>
                </div>

                <div class="hec-ot-orders">
                    <?php foreach ( $results as $order ) :
                        $status       = $order->get_status();
                        $status_label = $status_map[ $status ] ?? ucfirst( str_replace( '-', ' ', $status ) );

                        $items      = $order->get_items();
                        $item_names = array_map( fn( $item ) => $item->get_name(), $items );
                        $items_str  = implode( ', ', array_slice( $item_names, 0, 3 ) );
                        if ( count( $item_names ) > 3 ) {
                            $extra      = count( $item_names ) - 3;
                            $items_str .= ' + ' . $extra . ' more';
                        }

                        $date = $order->get_date_created() ? $order->get_date_created()->date_i18n( 'd M Y' ) : '—';
                        ?>
                        <div class="hec-ot-order">
                            <div class="hec-ot-order-top">
                                <span class="hec-ot-order-id">Order #<?php echo esc_html( $order->get_order_number() ); ?></span>
                                <span class="hec-ot-badge hec-ot-badge-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status_label ); ?></span>
                            </div>
                            <div class="hec-ot-meta">
                                <span class="hec-ot-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 9h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                    <?php echo esc_html( $date ); ?>
                                </span>
                                <span class="hec-ot-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M11 3H5a2 2 0 00-2 2v6l9.6 9.6a2 2 0 002.8 0l5.2-5.2a2 2 0 000-2.8L11 3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="8" cy="8" r="1.3" fill="currentColor"/></svg>
                                    <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
                                </span>
                            </div>
                            <?php if ( $items_str ) : ?>
                                <div class="hec-ot-products">
                                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M21 8l-9-5-9 5 9 5 9-5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3 8v8l9 5 9-5V8M12 13v8" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                    <?php echo esc_html( $items_str ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else : ?>

                <div class="hec-ot-empty">
                    <svg viewBox="0 0 24 24" fill="none" width="34" height="34"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <p>No orders found for this number.<br><strong>Please check the number and try again.</strong></p>
                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'hec-order-tracker',
        HEC_THEME_URI . '/assets/css/order-tracker.css',
        [ 'ecomx-style' ],
        HEC_THEME_VERSION
    );
});
