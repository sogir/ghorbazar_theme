<?php
/**
 * Content pages: building blocks for About / Contact / policy pages.
 *
 * Contact details come from Customize → Store Details (and Footer), so
 * the same page content works on every store running this theme.
 *
 * [ecx_info key="phone|whatsapp|whatsapp_display|messenger|facebook|address|email|hours|name|inside_dhaka|outside_dhaka"]
 * [ecx_contact_cards]   — Call / WhatsApp / Messenger / Address cards
 * [ecx_features]        — the four store promises (same as the footer strip)
 * [ecx_cta title="" text="" button=""]  — closing "start shopping" band
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
    add_post_type_support( 'page', 'excerpt' ); // page excerpt = subtitle in the page header
} );

/**
 * Store info used by the shortcodes and templates.
 */
function hec_store_info( $key ) {
    $wa = defined( 'HEC_WHATSAPP_NUMBER' ) ? HEC_WHATSAPP_NUMBER : '';
    switch ( $key ) {
        case 'name':             return get_bloginfo( 'name' );
        case 'phone':            return defined( 'HEC_PHONE_NUMBER' ) ? HEC_PHONE_NUMBER : '';
        case 'whatsapp':         return $wa;
        case 'whatsapp_display': return $wa ? '+' . preg_replace( '/^(\d{3})(\d{4})(\d+)$/', '$1 $2-$3', $wa ) : '';
        case 'messenger':        return defined( 'HEC_MESSENGER_USERNAME' ) ? HEC_MESSENGER_USERNAME : '';
        case 'facebook':         return get_theme_mod( 'hec_social_facebook', '' );
        case 'address':          return get_theme_mod( 'hec_footer_address', 'Dhaka, Bangladesh' );
        case 'email':            return get_theme_mod( 'hec_footer_email', '' );
        case 'hours':            return get_theme_mod( 'hec_footer_hours', '' );
        case 'inside_dhaka':     return defined( 'HEC_DELIVERY_INSIDE_DHAKA' ) ? HEC_DELIVERY_INSIDE_DHAKA : '';
        case 'outside_dhaka':    return defined( 'HEC_DELIVERY_OUTSIDE_DHAKA' ) ? HEC_DELIVERY_OUTSIDE_DHAKA : '';
    }
    return '';
}

/**
 * The four store promises (footer strip + About page).
 */
function hec_store_features() {
    $in  = hec_store_info( 'inside_dhaka' );
    $out = hec_store_info( 'outside_dhaka' );
    $ph  = hec_store_info( 'phone' );
    return [
        [ 'cash', 'Cash on Delivery', 'Pay when you receive your order' ],
        [ 'truck', 'Delivery All Over BD', $in && $out ? "Inside Dhaka {$in} · Outside {$out}" : 'Fast delivery across Bangladesh' ],
        [ 'shield', '100% Original', 'Genuine, brand-new products' ],
        [ 'headset', 'Easy Ordering', $ph ? "Call or WhatsApp {$ph}" : 'Order online, by phone or WhatsApp' ],
    ];
}

add_shortcode( 'ecx_info', function ( $atts ) {
    $atts = shortcode_atts( [ 'key' => 'phone' ], $atts, 'ecx_info' );
    return esc_html( hec_store_info( sanitize_key( $atts['key'] ) ) );
} );

add_shortcode( 'ecx_contact_cards', function () {
    $phone = hec_store_info( 'phone' );
    $wa    = hec_store_info( 'whatsapp' );
    $mess  = hec_store_info( 'messenger' );
    $fb    = hec_store_info( 'facebook' );
    $addr  = hec_store_info( 'address' );
    $hours = hec_store_info( 'hours' );
    $email = hec_store_info( 'email' );

    $cards = [];
    if ( $phone ) {
        $cards[] = [ 'phone', 'Call us', $phone, 'Call now', 'tel:' . $phone, 'ecx-ccard--phone' ];
    }
    if ( $wa ) {
        $cards[] = [ 'whatsapp', 'WhatsApp', hec_store_info( 'whatsapp_display' ), 'Chat on WhatsApp', 'https://wa.me/' . $wa, 'ecx-ccard--whatsapp' ];
    }
    if ( $mess || $fb ) {
        $cards[] = [ 'messenger', 'Facebook Messenger', 'Message our page', 'Open Messenger', $mess ? 'https://m.me/' . $mess : $fb, 'ecx-ccard--messenger' ];
    }
    if ( $email ) {
        $cards[] = [ 'mail', 'Email', antispambot( $email ), 'Send email', 'mailto:' . antispambot( $email ), 'ecx-ccard--mail' ];
    }

    ob_start();
    ?>
    <div class="ecx-contact-cards">
        <?php foreach ( $cards as [ $icon, $label, $value, $btn, $url, $mod ] ) : ?>
            <a class="ecx-ccard <?php echo esc_attr( $mod ); ?>" href="<?php echo esc_url( $url ); ?>"<?php echo 0 === strpos( $url, 'http' ) ? ' target="_blank" rel="noopener"' : ''; ?>>
                <span class="ecx-ccard__icon"><?php echo hec_footer_icon( $icon ); // phpcs:ignore -- static SVG ?></span>
                <span class="ecx-ccard__label"><?php echo esc_html( $label ); ?></span>
                <span class="ecx-ccard__value"><?php echo esc_html( $value ); ?></span>
                <span class="ecx-ccard__btn"><?php echo esc_html( $btn ); ?> →</span>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if ( $addr || $hours ) : ?>
        <div class="ecx-contact-meta">
            <?php if ( $addr ) : ?>
                <div><?php echo hec_footer_icon( 'pin' ); // phpcs:ignore ?><span><strong>Address</strong><?php echo nl2br( esc_html( $addr ) ); ?></span></div>
            <?php endif; ?>
            <?php if ( $hours ) : ?>
                <div><?php echo hec_footer_icon( 'clock' ); // phpcs:ignore ?><span><strong>Opening hours</strong><?php echo esc_html( $hours ); ?></span></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php
    return ob_get_clean();
} );

add_shortcode( 'ecx_features', function () {
    ob_start();
    ?>
    <div class="ecx-features">
        <?php foreach ( hec_store_features() as [ $icon, $title, $text ] ) : ?>
            <div class="ecx-feature-card">
                <span class="ecx-feature-card__icon"><?php echo hec_footer_icon( $icon ); // phpcs:ignore ?></span>
                <strong><?php echo esc_html( $title ); ?></strong>
                <span><?php echo esc_html( $text ); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
} );

add_shortcode( 'ecx_cta', function ( $atts ) {
    $atts = shortcode_atts( [
        'title'  => 'Ready to shop?',
        'text'   => 'Original gadgets with cash on delivery all over Bangladesh.',
        'button' => 'Shop now',
    ], $atts, 'ecx_cta' );
    $wa   = hec_store_info( 'whatsapp' );
    $shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
    ob_start();
    ?>
    <div class="ecx-cta">
        <div>
            <h2 class="ecx-cta__title"><?php echo esc_html( $atts['title'] ); ?></h2>
            <p class="ecx-cta__text"><?php echo esc_html( $atts['text'] ); ?></p>
        </div>
        <div class="ecx-cta__actions">
            <a class="ecx-btn ecx-btn--primary" href="<?php echo esc_url( $shop ); ?>"><?php echo esc_html( $atts['button'] ); ?></a>
            <?php if ( $wa ) : ?>
                <a class="ecx-btn ecx-btn--whatsapp" href="https://wa.me/<?php echo esc_attr( $wa ); ?>" target="_blank" rel="noopener"><?php echo hec_footer_icon( 'whatsapp' ); // phpcs:ignore ?> WhatsApp</a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
} );

add_action( 'wp_enqueue_scripts', function () {
    if ( is_page() && ! is_front_page() ) {
        wp_enqueue_style( 'ecomx-pages', HEC_THEME_URI . '/assets/css/pages.css', [ 'ecomx-style' ], HEC_THEME_VERSION );
    }
    if ( function_exists( 'is_account_page' ) && is_account_page() ) {
        wp_enqueue_style( 'ecomx-account', HEC_THEME_URI . '/assets/css/account.css', [ 'ecomx-style' ], HEC_THEME_VERSION );
    }
}, 20 );
