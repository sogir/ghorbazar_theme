<?php
/**
 * Default header: logo · search · cart, then the primary menu.
 * Uses the theme's own shortcodes so it matches the rest of the store.
 */
defined( 'ABSPATH' ) || exit;

$hec_has_wc = class_exists( 'WooCommerce' );
?>
<header id="site-header" class="site-header">
    <div class="site-header__bar container">
        <button class="site-header__toggle" type="button" aria-controls="site-navigation" aria-expanded="false">
            <span class="screen-reader-text"><?php esc_html_e( 'Menu', 'ecomx-bookshop' ); ?></span>
            <span class="site-header__toggle-icon" aria-hidden="true"></span>
        </button>

        <div class="site-branding">
            <?php if ( has_custom_logo() ) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
            <?php endif; ?>
        </div>

        <div class="site-header__search">
            <?php
            if ( $hec_has_wc && shortcode_exists( 'hec_search_box' ) ) {
                echo do_shortcode( '[hec_search_box]' );
            } else {
                get_search_form();
            }
            ?>
        </div>

        <div class="site-header__actions">
            <?php if ( $hec_has_wc ) : ?>
                <a class="site-header__account" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="<?php esc_attr_e( 'My account', 'ecomx-bookshop' ); ?>">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                </a>
                <?php if ( shortcode_exists( 'hec_mini_cart' ) ) echo do_shortcode( '[hec_mini_cart]' ); ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( has_nav_menu( 'primary' ) ) : ?>
        <nav id="site-navigation" class="site-nav" aria-label="<?php esc_attr_e( 'Primary', 'ecomx-bookshop' ); ?>">
            <div class="container">
                <?php
                wp_nav_menu( [
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'site-nav__menu',
                    'depth'          => 2,
                ] );
                ?>
            </div>
        </nav>
    <?php endif; ?>
</header>
