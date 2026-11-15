<?php
/**
 * Site footer: feature strip · brand / shop / customer care / contact · bottom bar.
 * Settings: Appearance → Customize → Footer. Contact numbers: config.php.
 * "Customer Care" uses the Footer Menu when one is assigned.
 * If the "Footer" widget area has widgets, they replace the four columns.
 */
defined( 'ABSPATH' ) || exit;

$hec_has_wc   = function_exists( 'wc_get_page_permalink' );
$hec_phone    = defined( 'HEC_PHONE_NUMBER' ) ? HEC_PHONE_NUMBER : '';
$hec_whatsapp = defined( 'HEC_WHATSAPP_NUMBER' ) ? HEC_WHATSAPP_NUMBER : '';
$hec_mess     = defined( 'HEC_MESSENGER_USERNAME' ) ? HEC_MESSENGER_USERNAME : '';
$hec_email    = get_theme_mod( 'hec_footer_email', '' );
$hec_address  = get_theme_mod( 'hec_footer_address', 'Dhaka, Bangladesh' );
$hec_hours    = get_theme_mod( 'hec_footer_hours', '' );
$hec_about    = get_theme_mod( 'hec_footer_about', 'Original gadgets and electronics with cash on delivery all over Bangladesh.' );
$hec_copy     = get_theme_mod( 'hec_footer_copyright', get_bloginfo( 'name' ) . '. All rights reserved.' );

$hec_features = hec_store_features();

$hec_socials = [];
foreach ( hec_footer_social_networks() as $key => $label ) {
    $url = get_theme_mod( "hec_social_{$key}", '' );
    if ( $url ) {
        $hec_socials[ $key ] = [ $label, $url ];
    }
}
if ( $hec_whatsapp ) {
    $hec_socials['whatsapp'] = [ 'WhatsApp', 'https://wa.me/' . $hec_whatsapp ];
}
if ( $hec_mess ) {
    $hec_socials['messenger'] = [ 'Messenger', 'https://m.me/' . $hec_mess ];
}
?>
<footer id="site-footer" class="ecx-footer">

    <?php if ( get_theme_mod( 'hec_footer_show_features', false ) ) : ?>
        <div class="ecx-footer-features">
            <div class="container ecx-footer-features__grid">
                <?php foreach ( $hec_features as [ $icon, $title, $text ] ) : ?>
                    <div class="ecx-feature">
                        <span class="ecx-feature__icon"><?php echo hec_footer_icon( $icon ); // phpcs:ignore -- static SVG ?></span>
                        <div>
                            <strong class="ecx-feature__title"><?php echo esc_html( $title ); ?></strong>
                            <span class="ecx-feature__text"><?php echo esc_html( $text ); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="ecx-footer-main">
        <div class="container">
            <?php if ( is_active_sidebar( 'footer-widgets' ) ) : ?>
                <div class="ecx-footer-widgets"><?php dynamic_sidebar( 'footer-widgets' ); ?></div>
            <?php else : ?>
                <div class="ecx-footer-cols">

                    <div class="ecx-footer-col ecx-footer-brand">
                        <a class="ecx-footer-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                            <?php
                            $logo_id = get_theme_mod( 'custom_logo' );
                            echo $logo_id
                                ? wp_get_attachment_image( $logo_id, 'medium', false, [ 'alt' => get_bloginfo( 'name' ), 'loading' => 'lazy' ] )
                                : '<span class="ecx-footer-logo__text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
                            ?>
                        </a>
                        <?php if ( $hec_about ) : ?><p class="ecx-footer-about"><?php echo esc_html( $hec_about ); ?></p><?php endif; ?>
                        <?php if ( $hec_socials ) : ?>
                            <ul class="ecx-social" aria-label="Follow us">
                                <?php foreach ( $hec_socials as $key => [ $label, $url ] ) : ?>
                                    <li><a class="ecx-social__link ecx-social__link--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $label ); ?>"><?php echo hec_footer_icon( $key ); // phpcs:ignore ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <?php
                    $hec_cats = taxonomy_exists( 'product_cat' ) ? get_terms( [
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => true,
                        'parent'     => 0,
                        'number'     => 4,
                        'orderby'    => 'menu_order',
                        'exclude'    => [ get_option( 'default_product_cat' ) ],
                    ] ) : [];
                    if ( $hec_cats && ! is_wp_error( $hec_cats ) ) :
                        ?>
                        <nav class="ecx-footer-col" aria-label="Shop">
                            <h3 class="ecx-footer-title">Shop</h3>
                            <ul class="ecx-footer-links">
                                <?php foreach ( $hec_cats as $c ) : ?>
                                    <li><a href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?></a></li>
                                <?php endforeach; ?>
                                <?php if ( $hec_has_wc ) : ?>
                                    <li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">All Products</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                    <nav class="ecx-footer-col" aria-label="Customer care">
                        <h3 class="ecx-footer-title">Customer Care</h3>
                        <?php
                        if ( has_nav_menu( 'footer' ) ) {
                            wp_nav_menu( [
                                'theme_location' => 'footer',
                                'container'      => false,
                                'menu_class'     => 'ecx-footer-links',
                                'depth'          => 1,
                            ] );
                        } else {
                            $links = [];
                            foreach ( [ 'track-order' => 'Track Order', 'contact' => 'Contact Us', 'refund_returns' => 'Refund & Returns' ] as $slug => $label ) {
                                $p = get_page_by_path( $slug );
                                if ( $p ) $links[ get_permalink( $p ) ] = $label;
                            }
                            if ( $hec_has_wc ) $links[ wc_get_page_permalink( 'myaccount' ) ] = 'My Account';
                            echo '<ul class="ecx-footer-links">';
                            foreach ( $links as $url => $label ) {
                                echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
                            }
                            echo '</ul>';
                        }
                        ?>
                    </nav>

                    <div class="ecx-footer-col">
                        <h3 class="ecx-footer-title">Contact Us</h3>
                        <ul class="ecx-footer-contact">
                            <?php if ( $hec_phone ) : ?>
                                <li><?php echo hec_footer_icon( 'phone' ); // phpcs:ignore ?><a href="tel:<?php echo esc_attr( $hec_phone ); ?>"><?php echo esc_html( $hec_phone ); ?></a></li>
                            <?php endif; ?>
                            <?php if ( $hec_email ) : ?>
                                <li><?php echo hec_footer_icon( 'mail' ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( antispambot( $hec_email ) ); ?>"><?php echo esc_html( antispambot( $hec_email ) ); ?></a></li>
                            <?php endif; ?>
                            <?php if ( $hec_address ) : ?>
                                <li><?php echo hec_footer_icon( 'pin' ); // phpcs:ignore ?><span><?php echo nl2br( esc_html( $hec_address ) ); ?></span></li>
                            <?php endif; ?>
                            <?php if ( $hec_hours ) : ?>
                                <li><?php echo hec_footer_icon( 'clock' ); // phpcs:ignore ?><span><?php echo esc_html( $hec_hours ); ?></span></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ecx-footer-bottom">
        <div class="container ecx-footer-bottom__inner">
            <p class="ecx-footer-copy">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( $hec_copy ); ?></p>
            <nav class="ecx-footer-legal" aria-label="Legal">
                <?php
                foreach ( [ 'about' => 'About Us', 'privacy-policy' => 'Privacy Policy' ] as $slug => $label ) {
                    $p = get_page_by_path( $slug );
                    if ( $p ) echo '<a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( $label ) . '</a>';
                }
                ?>
            </nav>
            <div class="ecx-footer-pay">
                <span class="ecx-footer-pay__label">We accept</span>
                <span class="ecx-pay-badge"><?php echo hec_footer_icon( 'cash' ); // phpcs:ignore ?> Cash on Delivery</span>
            </div>
        </div>
    </div>
</footer>
