<?php
/**
 * Variable products (products with choice options such as Color / Size).
 *
 *  - hec_product_discount_percent()  sale % for simple AND variable products
 *  - hec_product_has_inline_buy()    can a card buy it in one click (simple)
 *                                    or must the shopper pick options first
 *  - hec_render_variation_picker()   option "chips" on the product page
 *  - wc-ajax=hec_add_to_cart         AJAX add-to-cart for a chosen variation;
 *                                    replies with the same fragments as
 *                                    WooCommerce's own add_to_cart endpoint,
 *                                    so mini cart / toast / Order now redirect
 *                                    all keep working unchanged
 *  - hec_cart_item_options_text() / hec_order_item_options_text()
 *                                    "Color: Grey" line under item names in
 *                                    mini cart, checkout, cart, thank-you page
 */

defined( 'ABSPATH' ) || exit;

/**
 * Biggest discount (in %) a product currently has. For variable products
 * this is the best discount across its variations ("up to" value), or the
 * selected variation's value when a variation object is passed.
 */
function hec_product_discount_percent( $product ) {
    if ( ! $product || ! $product->is_on_sale() ) {
        return 0;
    }

    if ( $product->is_type( 'variable' ) ) {
        $prices = $product->get_variation_prices( true );
        $best   = 0;
        foreach ( $prices['regular_price'] as $id => $regular ) {
            $regular = (float) $regular;
            $price   = isset( $prices['price'][ $id ] ) ? (float) $prices['price'][ $id ] : $regular;
            if ( $regular > 0 && $price < $regular ) {
                $best = max( $best, (int) round( ( ( $regular - $price ) / $regular ) * 100 ) );
            }
        }
        return $best;
    }

    $regular = (float) $product->get_regular_price();
    $sale    = (float) $product->get_sale_price();
    if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
        return 0;
    }
    return (int) round( ( ( $regular - $sale ) / $regular ) * 100 );
}

/**
 * True when the product can be added to the cart straight from a card
 * (simple products). Variable / grouped / external products need the
 * product page, where the shopper chooses options first.
 */
function hec_product_has_inline_buy( $product ) {
    return $product && $product->supports( 'ajax_add_to_cart' );
}

/**
 * Everything the product-page picker needs: the attributes with their
 * options (in admin order, with readable labels) and every available
 * variation with its price, image, stock and SKU.
 */
function hec_get_variation_picker_data( $product ) {
    $attributes = [];

    foreach ( $product->get_variation_attributes() as $attribute_name => $options ) {
        $key   = 'attribute_' . sanitize_title( $attribute_name );
        $items = [];

        if ( taxonomy_exists( $attribute_name ) ) {
            // Global attribute (Products → Attributes): keep the term order.
            $terms = wc_get_product_terms( $product->get_id(), $attribute_name, [ 'fields' => 'all' ] );
            foreach ( $terms as $term ) {
                if ( in_array( $term->slug, $options, true ) ) {
                    $items[] = [ 'value' => $term->slug, 'label' => $term->name ];
                }
            }
        } else {
            // Custom (per-product) attribute: the stored value is the label.
            foreach ( $options as $option ) {
                $items[] = [ 'value' => $option, 'label' => $option ];
            }
        }

        if ( empty( $items ) ) {
            continue;
        }

        $attributes[] = [
            'key'     => $key,
            'label'   => wc_attribute_label( $attribute_name, $product ),
            'default' => (string) $product->get_variation_default_attribute( $attribute_name ),
            'options' => $items,
        ];
    }

    $variations = [];
    foreach ( $product->get_available_variations() as $row ) {
        $variation = wc_get_product( $row['variation_id'] );
        if ( ! $variation ) {
            continue;
        }

        $regular = (float) wc_get_price_to_display( $variation, [ 'price' => $variation->get_regular_price() ] );
        $price   = (float) wc_get_price_to_display( $variation );

        $variations[] = [
            'id'         => (int) $row['variation_id'],
            'attributes' => $row['attributes'],
            'price_html' => $variation->get_price_html(),
            'price'      => $price,
            'discount'   => hec_product_discount_percent( $variation ),
            'in_stock'   => (bool) $row['is_in_stock'],
            'buyable'    => (bool) $row['is_purchasable'] && (bool) $row['is_in_stock'],
            'stock_text' => trim( wp_strip_all_tags( $row['availability_html'] ) ),
            'sku'        => (string) $row['sku'],
            'image'      => ! empty( $row['image']['src'] ) && $variation->get_image_id() ? $row['image']['src'] : '',
            'regular'    => $regular,
        ];
    }

    return [
        'attributes' => $attributes,
        'variations' => $variations,
    ];
}

/**
 * Option chips + the JSON the picker script reads. Echoes markup.
 */
function hec_render_variation_picker( $product ) {
    $data = hec_get_variation_picker_data( $product );

    if ( empty( $data['attributes'] ) ) {
        return;
    }
    ?>
    <div class="hec-pd-variations" data-hec-variations>
        <?php foreach ( $data['attributes'] as $attr ) : ?>
            <div class="hec-pd-var-row" data-attribute="<?php echo esc_attr( $attr['key'] ); ?>">
                <span class="hec-pd-var-label">
                    <?php echo esc_html( $attr['label'] ); ?>:
                    <span class="hec-pd-var-current"></span>
                </span>
                <div class="hec-pd-var-options" role="group" aria-label="<?php echo esc_attr( $attr['label'] ); ?>">
                    <?php foreach ( $attr['options'] as $opt ) : ?>
                        <button type="button" class="hec-pd-opt" aria-pressed="false"
                                data-value="<?php echo esc_attr( $opt['value'] ); ?>"
                                data-label="<?php echo esc_attr( $opt['label'] ); ?>">
                            <?php echo esc_html( $opt['label'] ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="hec-pd-var-msg" aria-live="polite"></div>

        <script type="application/json" class="hec-pd-var-data"><?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
    </div>
    <?php
}

/**
 * AJAX: add a chosen variation to the cart.
 * POST product_id, variation_id, quantity, attribute_* => value.
 */
add_action( 'wc_ajax_hec_add_to_cart', 'hec_ajax_add_variation_to_cart' );

function hec_ajax_add_variation_to_cart() {
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- same as WooCommerce's own add_to_cart endpoint.
    $product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
    $quantity     = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 1;
    $quantity     = max( 1, $quantity );

    $product   = wc_get_product( $product_id );
    $variation = $variation_id ? wc_get_product( $variation_id ) : null;

    if ( ! $product || ! $product->is_type( 'variable' ) || ! $variation || $variation->get_parent_id() !== $product->get_id() ) {
        wp_send_json( [
            'error'   => true,
            'message' => 'Please choose the product options first.',
        ] );
    }

    // Chosen options, with the variation's fixed values taking priority
    // (the shopper only really "chooses" values a variation leaves as Any).
    $attributes = [];
    foreach ( $product->get_variation_attributes() as $attribute_name => $options ) {
        $key   = 'attribute_' . sanitize_title( $attribute_name );
        $value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
        $value = taxonomy_exists( $attribute_name )
            ? sanitize_title( $value )
            : html_entity_decode( wc_clean( $value ), ENT_QUOTES, get_bloginfo( 'charset' ) );
        $attributes[ $key ] = $value;
    }
    foreach ( $variation->get_variation_attributes() as $key => $value ) {
        if ( '' !== $value ) {
            $attributes[ $key ] = $value;
        }
    }
    // phpcs:enable

    $passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $attributes );

    if ( $passed && 'publish' === get_post_status( $product_id ) && false !== WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes ) ) {
        do_action( 'woocommerce_ajax_added_to_cart', $product_id );
        WC_AJAX::get_refreshed_fragments(); // sends { fragments, cart_hash } and exits
    }

    $messages = [];
    foreach ( wc_get_notices( 'error' ) as $notice ) {
        $messages[] = wp_strip_all_tags( is_array( $notice ) ? $notice['notice'] : $notice );
    }
    wc_clear_notices();

    wp_send_json( [
        'error'   => true,
        'message' => $messages ? implode( ' ', $messages ) : 'Sorry, this option could not be added. Please try again.',
    ] );
}

/**
 * "Color: Grey, Size: L" for a cart item (attributes already in the item's
 * name are left out by WooCommerce, so nothing is repeated).
 */
function hec_cart_item_options_text( $cart_item ) {
    if ( ! function_exists( 'wc_get_formatted_cart_item_data' ) ) {
        return '';
    }
    $lines = array_filter( array_map( 'trim', explode( "\n", wp_strip_all_tags( wc_get_formatted_cart_item_data( $cart_item, true ) ) ) ) );
    return implode( ', ', $lines );
}

/**
 * Same as above for an order line item (thank-you page, order tracker).
 */
function hec_order_item_options_text( $item ) {
    if ( ! is_callable( [ $item, 'get_formatted_meta_data' ] ) ) {
        return '';
    }
    $parts = [];
    foreach ( $item->get_formatted_meta_data() as $meta ) {
        $parts[] = trim( wp_strip_all_tags( $meta->display_key ) ) . ': ' . trim( wp_strip_all_tags( $meta->display_value ) );
    }
    return implode( ', ', $parts );
}
