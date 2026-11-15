<?php
/**
 * Wemax Gadgets — functions.php
 *
 * Standalone parent theme. Everything store-specific lives in /snippets/
 * (auto-loaded) and per-site values in config.php. A child theme can
 * override any template file and add its own snippets.
 */

defined( 'ABSPATH' ) || exit;

// Version comes from style.css, so a release only has to bump one place.
define( 'HEC_THEME_VERSION', wp_get_theme( get_template() )->get( 'Version' ) );
define( 'HEC_THEME_DIR', get_template_directory() );
define( 'HEC_THEME_URI', get_template_directory_uri() );

// Back-compat aliases for snippets written against the old child theme.
defined( 'HEC_CHILD_DIR' )     || define( 'HEC_CHILD_DIR', HEC_THEME_DIR );
defined( 'HEC_CHILD_URI' )     || define( 'HEC_CHILD_URI', HEC_THEME_URI );
defined( 'HEC_CHILD_VERSION' ) || define( 'HEC_CHILD_VERSION', HEC_THEME_VERSION );

/*
 * Per-site settings. Theme updates replace this whole folder, so store
 * details must NOT live in theme files. Priority:
 *   1. wp-content/ecomx-config.php   (survives updates; optional)
 *   2. a child theme's config.php    (optional)
 *   3. Appearance → Customize → Store Details  (inc/store-settings.php)
 * Whatever defines a constant first wins.
 */
if ( file_exists( WP_CONTENT_DIR . '/ecomx-config.php' ) ) {
    require_once WP_CONTENT_DIR . '/ecomx-config.php';
}
if ( is_child_theme() && file_exists( get_stylesheet_directory() . '/config.php' ) ) {
    require_once get_stylesheet_directory() . '/config.php';
}
require_once HEC_THEME_DIR . '/inc/store-settings.php';
require_once HEC_THEME_DIR . '/inc/updater.php';

if ( ! isset( $content_width ) ) {
    $content_width = 1200;
}

/**
 * Theme supports, menus, WooCommerce/Elementor integration.
 */
add_action( 'after_setup_theme', function () {
    load_theme_textdomain( 'ecomx-bookshop', HEC_THEME_DIR . '/languages' );

    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'customize-selective-refresh-widgets' );
    add_theme_support( 'html5', [
        'search-form', 'comment-form', 'comment-list', 'gallery',
        'caption', 'script', 'style', 'navigation-widgets',
    ] );
    add_theme_support( 'custom-logo', [
        'height'      => 80,
        'width'       => 240,
        'flex-height' => true,
        'flex-width'  => true,
    ] );

    // WooCommerce.
    add_theme_support( 'woocommerce', [
        'thumbnail_image_width' => 400,
        'single_image_width'    => 800,
        'product_grid'          => [
            'default_columns' => 4,
            'min_columns'     => 2,
            'max_columns'     => 6,
        ],
    ] );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );

    register_nav_menus( [
        'primary' => __( 'Primary Menu', 'ecomx-bookshop' ),
        'footer'  => __( 'Footer Menu', 'ecomx-bookshop' ),
    ] );
} );

/**
 * Elementor Pro Theme Builder: let header/footer/single/archive
 * templates replace the theme's own markup.
 */
add_action( 'elementor/theme/register_locations', function ( $manager ) {
    $manager->register_all_core_location();
} );

/**
 * True when an Elementor Pro Theme Builder template owns this location.
 */
function hec_elementor_location( $location ) {
    return function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( $location );
}

/**
 * Widget areas.
 */
add_action( 'widgets_init', function () {
    register_sidebar( [
        'name'          => __( 'Shop Sidebar', 'ecomx-bookshop' ),
        'id'            => 'shop-sidebar',
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ] );
    register_sidebar( [
        'name'          => __( 'Footer', 'ecomx-bookshop' ),
        'id'            => 'footer-widgets',
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ] );
} );

/**
 * Assets. `ecomx-style` is the base handle every snippet depends on.
 */
add_action( 'wp_enqueue_scripts', function () {
    $v   = HEC_THEME_VERSION;
    $uri = HEC_THEME_URI;

    // UI font (Roboto). Filter to false to self-host or skip.
    if ( apply_filters( 'ecomx_load_google_font', true ) ) {
        wp_enqueue_style( 'ecomx-font', 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap', [], null );
    }
    wp_enqueue_style( 'ecomx-style', $uri . '/assets/css/theme.css', [], $v );
    wp_enqueue_style( 'ecomx-style-header', $uri . '/style.css', [ 'ecomx-style' ], $v );
    wp_enqueue_style( 'hec-custom-css', $uri . '/assets/css/custom.css', [ 'ecomx-style' ], $v );
    wp_enqueue_script( 'hec-custom-js', $uri . '/assets/js/custom.js', [], $v, true );
    wp_enqueue_script( 'ecomx-theme-js', $uri . '/assets/js/theme.js', [], $v, true );

    wp_enqueue_style( 'hec-product-loop', $uri . '/assets/css/product-loop.css', [ 'ecomx-style' ], $v );
    wp_enqueue_script( 'hec-product-loop-js', $uri . '/assets/js/product-loop.js', [ 'jquery' ], $v, true );
    wp_localize_script( 'hec-product-loop-js', 'hecCheckoutData', [
        'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
    ] );

    wp_enqueue_style( 'hec-category-dropdown', $uri . '/assets/css/category-dropdown.css', [ 'ecomx-style' ], $v );
    wp_enqueue_script( 'hec-category-dropdown-js', $uri . '/assets/js/category-dropdown.js', [], $v, true );

    wp_enqueue_style( 'hec-product-details', $uri . '/assets/css/product-details.css', [ 'ecomx-style' ], $v );
    wp_enqueue_script( 'hec-product-details-js', $uri . '/assets/js/product-details.js', [ 'jquery' ], $v, true );

    wp_enqueue_style( 'hec-product-extras', $uri . '/assets/css/product-extras.css', [ 'ecomx-style', 'hec-product-loop' ], $v );

    wp_enqueue_style( 'hec-shop-filters', $uri . '/assets/css/shop-filters.css', [ 'ecomx-style', 'hec-product-loop' ], $v );
    wp_enqueue_script( 'hec-shop-filters-js', $uri . '/assets/js/shop-filters.js', [], $v, true );

    if ( class_exists( 'WooCommerce' ) ) {
        wp_enqueue_style( 'hec-mini-cart', $uri . '/assets/css/mini-cart.css', [ 'ecomx-style' ], $v );
        wp_enqueue_script( 'hec-mini-cart-js', $uri . '/assets/js/mini-cart.js', [ 'wc-cart-fragments' ], $v, true );
    }

    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
} );

/**
 * Brand layer: runs after the snippet files enqueue their own styles,
 * so it can restyle cards/buttons without editing each component.
 */
add_action( 'wp_enqueue_scripts', function () {
    $deps = array_values( array_filter(
        [ 'ecomx-style', 'hec-product-loop', 'hec-product-details', 'hec-shop-filters', 'hec-product-extras' ],
        'wp_style_is'
    ) );
    wp_enqueue_style( 'ecomx-brand', HEC_THEME_URI . '/assets/css/brand.css', $deps, HEC_THEME_VERSION );

    // Child theme stylesheet last, so it can override everything above.
    if ( is_child_theme() ) {
        wp_enqueue_style( 'ecomx-child-style', get_stylesheet_uri(), [ 'ecomx-brand' ], wp_get_theme()->get( 'Version' ) );
    }
}, 100 );

/**
 * WooCommerce wrappers — use the theme's container instead of the
 * default `#primary/#main` markup.
 */
add_action( 'after_setup_theme', function () {
    remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
    remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
    remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
}, 20 );

/**
 * Skip link + body classes.
 */
add_action( 'wp_body_open', function () {
    echo '<a class="skip-link screen-reader-text" href="#content">' . esc_html__( 'Skip to content', 'ecomx-bookshop' ) . '</a>';
}, 5 );

add_filter( 'body_class', function ( $classes ) {
    $classes[] = 'ecomx-theme';
    if ( is_page_template( 'templates/full-width.php' ) || is_page_template( 'templates/canvas.php' ) ) {
        $classes[] = 'ecomx-full-width';
    }
    return $classes;
} );

/**
 * Snippets: parent first, then child (child can add or replace by name).
 */
$hec_snippets = [];
foreach ( glob( HEC_THEME_DIR . '/snippets/*.php' ) as $hec_file ) {
    $hec_snippets[ basename( $hec_file ) ] = $hec_file;
}
if ( is_child_theme() && is_dir( get_stylesheet_directory() . '/snippets' ) ) {
    foreach ( glob( get_stylesheet_directory() . '/snippets/*.php' ) as $hec_file ) {
        $hec_snippets[ basename( $hec_file ) ] = $hec_file;
    }
}
foreach ( $hec_snippets as $hec_file ) {
    require_once $hec_file;
}
unset( $hec_snippets, $hec_file );
