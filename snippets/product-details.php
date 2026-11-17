<?php
/**
 * Single product details shortcode — [hec_product_details]
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'hec_product_details', function () {

    if ( ! class_exists( 'WooCommerce' ) ) {
        return '<p>WooCommerce is not active.</p>';
    }

    global $product;

    if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
        $product = wc_get_product( get_the_ID() );
    }

    if ( ! $product ) {
        return '<p>Product not found.</p>';
    }

    // ---- Gallery images ----
    $attachment_ids = $product->get_gallery_image_ids();
    $main_image_id  = $product->get_image_id();
    if ( $main_image_id ) {
        array_unshift( $attachment_ids, $main_image_id );
    }
    $attachment_ids = array_unique( $attachment_ids );
    if ( empty( $attachment_ids ) ) {
        $gallery_images = [ wc_placeholder_img_src( 'woocommerce_single' ) ];
    } else {
        $gallery_images = array_map( function ( $id ) {
            return wp_get_attachment_image_url( $id, 'woocommerce_single' );
        }, $attachment_ids );
    }

    // ---- Variable product (choice options like Color / Size)? ----
    $is_variable = $product->is_type( 'variable' ) && $product->get_children();

    // ---- Discount pill (shown next to price, not on the image) ----
    // Variable products: "Up to X% OFF" until an option is chosen, then the
    // picker script swaps in that option's own discount.
    $percent       = hec_product_discount_percent( $product );
    $discount_html = '';
    if ( $percent > 0 || $is_variable ) {
        $discount_html = '<span class="hec-pd-discount-pill"' . ( $percent > 0 ? '' : ' hidden' ) . '>'
            . ( $is_variable && $percent > 0 ? 'Up to ' : '' ) . esc_html( $percent ) . '% OFF</span>';
    }

    // ---- Free delivery badge (shared with the product loop) ----
    $free_delivery_html = hec_render_free_delivery_badge( $product );

    // ---- Breadcrumb: Category- [name] ----
    $breadcrumb_cat = '';
    $terms = get_the_terms( $product->get_id(), 'product_cat' );
    if ( $terms && ! is_wp_error( $terms ) ) {
        $cat = reset( $terms );
        $breadcrumb_cat = '<a href="' . esc_url( get_term_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a>';
    }

    // ---- Pre-filled order message ----
    $product_name  = $product->get_name();
    $product_price = $product->get_price();
    $product_url   = get_permalink( $product->get_id() );

    // Variable products: the picker script adds the chosen option + its price.
    $order_message = $is_variable
        ? "🛒 {$product_name}\n{$product_url}\n\nI would like to order this."
        : "🛒 {$product_name} - {$product_price} TK\n{$product_url}\n\nI would like to order this.";

    $whatsapp_url = defined( 'HEC_WHATSAPP_NUMBER' )
        ? 'https://api.whatsapp.com/send?phone=' . esc_attr( HEC_WHATSAPP_NUMBER ) . '&text=' . rawurlencode( $order_message )
        : '';

    $messenger_url = defined( 'HEC_MESSENGER_USERNAME' )
        ? 'https://m.me/' . esc_attr( HEC_MESSENGER_USERNAME ) . '?text=' . rawurlencode( $order_message )
        : '';

    ob_start();
    ?>
    <div class="hec-product-details<?php echo $is_variable ? ' hec-pd-is-variable' : ''; ?>"
         data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
         data-product-name="<?php echo esc_attr( $product_name ); ?>"
         data-product-url="<?php echo esc_url( $product_url ); ?>">

                        <div class="hec-pd-gallery-card">
            <div class="hec-pd-gallery">

                <?php if ( count( $gallery_images ) > 1 ) : ?>
                    <button type="button" class="hec-pd-arrow hec-pd-arrow-prev" id="hec-pd-prev" aria-label="Previous image">
                        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button type="button" class="hec-pd-arrow hec-pd-arrow-next" id="hec-pd-next" aria-label="Next image">
                        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                <?php endif; ?>

                <div class="hec-pd-slider" id="hec-pd-slider">
                    <?php foreach ( $gallery_images as $img ) : ?>
                        <div class="hec-pd-slide">
                            <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $product_name ); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ( count( $gallery_images ) > 1 ) : ?>
                <div class="hec-pd-thumbs">
                    <?php foreach ( $gallery_images as $i => $img ) : ?>
                        <div class="hec-pd-thumb <?php echo 0 === $i ? 'is-active' : ''; ?>" data-index="<?php echo (int) $i; ?>">
                            <img src="<?php echo esc_url( $img ); ?>" alt="">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="hec-pd-info-card">

            <?php if ( $breadcrumb_cat ) : ?>
                <div class="hec-pd-breadcrumb">Category- <?php echo $breadcrumb_cat; ?></div>
            <?php endif; ?>

            <h1 class="hec-pd-title"><?php echo esc_html( $product_name ); ?></h1>

            <div class="hec-pd-price-row">
                <div class="hec-pd-price">
                    <?php echo $product->get_price_html(); ?>
                </div>
                <?php echo $discount_html; ?>
                <?php echo $free_delivery_html; ?>
            </div>

            <?php if ( $product->get_sku() || $is_variable ) : ?>
                <div class="hec-pd-sku" data-default-sku="<?php echo esc_attr( $product->get_sku() ); ?>"<?php echo $product->get_sku() ? '' : ' hidden'; ?>>Product code: <span class="hec-pd-sku-value"><?php echo esc_html( $product->get_sku() ); ?></span></div>
            <?php endif; ?>

            <?php if ( $is_variable ) hec_render_variation_picker( $product ); ?>

                        <div class="hec-pd-qty-row">
                <span class="hec-pd-qty-label">Quantity :</span>
                <div class="hec-pd-qty-control">
                    <button type="button" class="hec-pd-qty-minus" aria-label="Decrease">−</button>
                    <span class="hec-pd-qty-value" id="hec-pd-qty">1</span>
                    <button type="button" class="hec-pd-qty-plus" aria-label="Increase">+</button>
                </div>
            </div>

            <?php
            // Simple products: WooCommerce's own AJAX add-to-cart.
            // Variable products: product-details.js posts the chosen
            // variation to wc-ajax=hec_add_to_cart (see variable-products.php).
            $btn_href  = $is_variable ? $product_url : $product->add_to_cart_url();
            $btn_class = $is_variable ? 'hec-pd-btn add_to_cart_button is-disabled' : 'hec-pd-btn ajax_add_to_cart add_to_cart_button';
            $btn_extra = $is_variable ? ' data-hec-variable="1" aria-disabled="true"' : '';
            ?>
            <div class="hec-pd-actions">
                <a href="<?php echo esc_url( $btn_href ); ?>"
                   data-quantity="1"
                   data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
                   data-hec-role="order"<?php echo $btn_extra; ?>
                   class="<?php echo esc_attr( $btn_class ); ?> hec-pd-order-btn"
                   rel="nofollow">
                    <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M6 6h15l-1.5 9h-12L6 6zm0 0L5 3H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1" fill="currentColor"/><circle cx="18" cy="20" r="1" fill="currentColor"/></svg>
                    Order now
                </a>

                <a href="<?php echo esc_url( $btn_href ); ?>"
                   data-quantity="1"
                   data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
                   data-hec-role="cart"<?php echo $btn_extra; ?>
                   class="<?php echo esc_attr( $btn_class ); ?> hec-pd-cart-btn"
                   rel="nofollow">
                    <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M6 6h15l-1.5 9h-12L6 6zm0 0L5 3H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1" fill="currentColor"/><circle cx="18" cy="20" r="1" fill="currentColor"/></svg>
                    Add to cart
                </a>
            </div>

            <?php if ( defined( 'HEC_PHONE_NUMBER' ) ) : ?>
                <a href="tel:<?php echo esc_attr( HEC_PHONE_NUMBER ); ?>" class="hec-pd-contact-btn hec-pd-phone">
                    <svg viewBox="0 0 24 24" fill="none" width="17" height="17"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?php echo esc_html( HEC_PHONE_NUMBER ); ?>
                </a>
            <?php endif; ?>

            <?php if ( $whatsapp_url ) : ?>
                <a href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener" class="hec-pd-contact-btn hec-pd-whatsapp">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="17" height="17"><path d="M17.6 6.32A7.85 7.85 0 0012.05 4a7.94 7.94 0 00-6.9 11.9L4 20l4.2-1.1a7.9 7.9 0 003.85 1h0a7.94 7.94 0 005.55-13.58zm-5.55 12.2h0a6.57 6.57 0 01-3.36-.92l-.24-.14-2.5.65.67-2.44-.16-.25a6.6 6.6 0 1112.28-3.5 6.55 6.55 0 01-6.69 6.6zm3.6-4.93c-.2-.1-1.17-.58-1.35-.64s-.32-.1-.45.1-.5.64-.62.77-.23.15-.43.05a5.4 5.4 0 01-2.69-2.35c-.2-.35.2-.32.58-1.07a.36.36 0 000-.35c0-.1-.45-1.08-.62-1.48s-.33-.33-.45-.34h-.39a.75.75 0 00-.54.25 2.28 2.28 0 00-.71 1.7 3.96 3.96 0 00.83 2.1 9.06 9.06 0 003.46 3.06c.48.21.86.33 1.15.42a2.77 2.77 0 001.28.08 2.1 2.1 0 001.37-.97 1.7 1.7 0 00.12-.97c-.05-.09-.18-.14-.38-.24z"/></svg>
                    Order on WhatsApp
                </a>
            <?php endif; ?>

            <?php if ( $messenger_url ) : ?>
                <a href="<?php echo esc_url( $messenger_url ); ?>" target="_blank" rel="noopener" class="hec-pd-contact-btn hec-pd-messenger">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="17" height="17"><path d="M12 2C6.5 2 2 6.14 2 11.25c0 2.9 1.44 5.49 3.7 7.19V22l3.38-1.86c.9.25 1.87.38 2.92.38 5.5 0 10-4.14 10-9.25S17.5 2 12 2zm1.02 12.45L10.5 11.8l-4.9 2.68 5.4-5.73 2.6 2.65 4.82-2.65-5.4 5.7z"/></svg>
                    Order on Messenger
                </a>
            <?php endif; ?>

            <div class="hec-pd-delivery-box">
                <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M3 17V7a1 1 0 011-1h9a1 1 0 011 1v10M3 17h11m0 0h4.05a1 1 0 00.95-.68l1.3-3.9a1 1 0 00-.05-.74l-2.05-4.1a1 1 0 00-.9-.58H14M3 17a2 2 0 104 0 2 2 0 00-4 0zm11 0a2 2 0 104 0 2 2 0 00-4 0z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <div>
                    <span><b>Delivery charge</b></span><br>
                    <?php if ( defined( 'HEC_DELIVERY_OUTSIDE_DHAKA' ) ) : ?>
                        <span>Outside Dhaka: <?php echo esc_html( HEC_DELIVERY_OUTSIDE_DHAKA ); ?></span><br>
                    <?php endif; ?>
                    <?php if ( defined( 'HEC_DELIVERY_INSIDE_DHAKA' ) ) : ?>
                        <span>Inside Dhaka: <?php echo esc_html( HEC_DELIVERY_INSIDE_DHAKA ); ?></span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    <?php

    return ob_get_clean();
});