<?php
/**
 * Mobile bottom navigation bar — Categories / Support / Checkout.
 *
 * "Support" opens a WhatsApp chat (wa.me/HEC_WHATSAPP_NUMBER, from
 * config.php) in a new tab, badge-styled as a filled green circle
 * with the WhatsApp glyph — same convention as the phone/WhatsApp/
 * Messenger contact buttons on the product details page.
 *
 * Rendered once, site-wide, via wp_footer rather than as a shortcode —
 * a bottom nav needs to be present on every front-end page regardless
 * of which Elementor template built it, not manually placed per page.
 * Hidden entirely above 767px (assets/css/mobile-bottom-nav.css);
 * fixed to the viewport bottom on mobile.
 *
 * "Categories" doesn't link anywhere — it opens a slide-in category
 * drawer, same interaction pattern (backdrop + slide-from-left panel,
 * header with a close button) as the shop page's [hec_shop_filters]
 * mobile drawer, just with a plain top-level category list instead of
 * sort/on-sale filter controls. Kept as separate markup/CSS/JS (own
 * ids) rather than reusing that shortcode's drawer, since this one is
 * global and always present, not scoped to the shop/archive context.
 *
 * Left alone on CartFlows funnel steps — those are meant to be
 * distraction-free, and injecting sitewide chrome there would fight
 * that purpose.
 */

defined( 'ABSPATH' ) || exit;

function hec_mobile_bottom_nav_should_render() {
    if ( is_admin() || ! class_exists( 'WooCommerce' ) ) {
        return false;
    }
    if ( is_singular( 'cartflows_step' ) ) {
        return false;
    }
    return true;
}

add_action( 'wp_footer', function () {

    if ( ! hec_mobile_bottom_nav_should_render() ) {
        return;
    }

    $categories = get_terms( [
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0,
        'exclude'    => [ get_option( 'default_product_cat' ) ],
    ] );

    $is_checkout_page = function_exists( 'is_checkout' ) && is_checkout();
    $cart_count       = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    $whatsapp_url     = defined( 'HEC_WHATSAPP_NUMBER' ) ? 'https://wa.me/' . esc_attr( HEC_WHATSAPP_NUMBER ) : '';
    ?>
    <div class="hec-catnav-backdrop" id="hec-catnav-backdrop"></div>

    <div class="hec-catnav-drawer" id="hec-catnav-drawer">
        <div class="hec-catnav-header">
            <span>Categories</span>
            <button type="button" class="hec-catnav-close" id="hec-catnav-close" aria-label="Close categories">&times;</button>
        </div>

        <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
            <div class="hec-catnav-list">
                <?php foreach ( $categories as $cat ) :
                    $thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
                    $image_url    = $thumbnail_id
                        ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' )
                        : wc_placeholder_img_src( 'thumbnail' );
                    ?>
                    <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="hec-catnav-link">
                        <span class="hec-catnav-link-main">
                            <img src="<?php echo esc_url( $image_url ); ?>" alt="" class="hec-catnav-icon">
                            <span><?php echo esc_html( $cat->name ); ?></span>
                        </span>
                        <span class="hec-catnav-count">(<?php echo (int) $cat->count; ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="hec-catnav-empty">No categories found.</p>
        <?php endif; ?>
    </div>

    <nav class="hec-mnav" id="hec-mnav" aria-label="Mobile navigation">

        <button type="button" class="hec-mnav-item" id="hec-catnav-toggle" aria-label="Open categories">
            <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/></svg>
            <span>Categories</span>
        </button>

        <a href="<?php echo esc_url( $whatsapp_url ?: '#' ); ?>"<?php echo $whatsapp_url ? ' target="_blank" rel="noopener"' : ''; ?> class="hec-mnav-item hec-mnav-support-item" aria-label="Support on WhatsApp">
            <span class="hec-mnav-support-icon-wrap">
                <svg viewBox="0 0 24 24" width="42" height="42" aria-hidden="true">
                    <circle cx="12" cy="12" r="12" fill="#25D366"/>
                    <g transform="translate(4.6 4.6) scale(0.62)">
                        <path d="M17.6 6.32A7.85 7.85 0 0012.05 4a7.94 7.94 0 00-6.9 11.9L4 20l4.2-1.1a7.9 7.9 0 003.85 1h0a7.94 7.94 0 005.55-13.58zm-5.55 12.2h0a6.57 6.57 0 01-3.36-.92l-.24-.14-2.5.65.67-2.44-.16-.25a6.6 6.6 0 1112.28-3.5 6.55 6.55 0 01-6.69 6.6zm3.6-4.93c-.2-.1-1.17-.58-1.35-.64s-.32-.1-.45.1-.5.64-.62.77-.23.15-.43.05a5.4 5.4 0 01-2.69-2.35c-.2-.35.2-.32.58-1.07a.36.36 0 000-.35c0-.1-.45-1.08-.62-1.48s-.33-.33-.45-.34h-.39a.75.75 0 00-.54.25 2.28 2.28 0 00-.71 1.7 3.96 3.96 0 00.83 2.1 9.06 9.06 0 003.46 3.06c.48.21.86.33 1.15.42a2.77 2.77 0 001.28.08 2.1 2.1 0 001.37-.97 1.7 1.7 0 00.12-.97c-.05-.09-.18-.14-.38-.24z" fill="#fff"/>
                    </g>
                </svg>
            </span>
            <span>WhatsApp</span>
        </a>

        <a href="<?php echo esc_url( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' ) ); ?>" class="hec-mnav-item<?php echo $is_checkout_page ? ' is-active' : ''; ?>">
            <span class="hec-mnav-cart-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M6 6h15l-1.5 9h-12L6 6zm0 0L5 3H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1" fill="currentColor"/><circle cx="18" cy="20" r="1" fill="currentColor"/></svg>
                <span class="hec-mini-cart-count"><?php echo (int) $cart_count; ?></span>
            </span>
            <span>Checkout</span>
        </a>

    </nav>
    <?php
});

add_action( 'wp_enqueue_scripts', function () {

    if ( ! hec_mobile_bottom_nav_should_render() ) {
        return;
    }

    wp_enqueue_style(
        'hec-mobile-bottom-nav',
        HEC_THEME_URI . '/assets/css/mobile-bottom-nav.css',
        [ 'ecomx-style' ],
        HEC_THEME_VERSION
    );

    wp_enqueue_script(
        'hec-mobile-bottom-nav-js',
        HEC_THEME_URI . '/assets/js/mobile-bottom-nav.js',
        [],
        HEC_THEME_VERSION,
        true
    );
});
