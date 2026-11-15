<?php
/**
 * Product search box — [hec_search_box]
 *
 * A search field with a live AJAX suggestions dropdown (thumbnail /
 * name / price, top 6 matches) as the customer types, styled to match
 * the theme. Pressing Enter or the search button still submits a real
 * `<form>` to the normal WordPress search results (?s=...&post_type=
 * product) — the AJAX dropdown is a shortcut for the obvious match,
 * not a replacement for the results page.
 *
 * The actual results page needs no new code: [hec_shop_filters] and
 * [hec_shop_grid] (see shop-filters.php) already run on
 * is_search() && post_type=product automatically — just place
 * [hec_search_box], [hec_search_results_heading], [hec_shop_filters]
 * and [hec_shop_grid] on whatever page/Elementor template handles
 * WordPress's native search results.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'hec_search_box', function () {

    if ( ! class_exists( 'WooCommerce' ) ) {
        return '';
    }

    ob_start();
    ?>
    <form role="search" method="get" class="hec-search-box" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input type="search" name="s" class="hec-search-input" id="hec-search-input"
               placeholder="Search Product......" autocomplete="off"
               value="<?php echo esc_attr( get_search_query() ); ?>">
        <input type="hidden" name="post_type" value="product">

        <button type="submit" class="hec-search-submit" aria-label="Search">
            <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2.2"/><path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
        </button>

        <div class="hec-search-suggestions" id="hec-search-suggestions"></div>
    </form>
    <?php
    return ob_get_clean();
});

/**
 * Optional heading for the search results page itself — "Search
 * results for '<query>' (N found)", styled like the other section
 * titles on this site (.hec-related-title / .hec-co-summary-title).
 */
add_shortcode( 'hec_search_results_heading', function () {

    if ( ! is_search() ) {
        return '';
    }

    global $wp_query;
    $query = get_search_query();
    $count = $wp_query->found_posts;

    ob_start();
    ?>
    <div class="hec-search-heading">
        <?php if ( $query ) : ?>
            <h1 class="hec-search-heading-title">Search results for "<?php echo esc_html( $query ); ?>"</h1>
            <p class="hec-search-heading-count"><?php echo (int) $count; ?> <?php echo 1 === (int) $count ? 'product' : 'products'; ?> found</p>
        <?php else : ?>
            <h1 class="hec-search-heading-title">Search results</h1>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * ---- AJAX: live suggestions ----
 */
add_action( 'wp_ajax_hec_search_products', 'hec_ajax_search_products' );
add_action( 'wp_ajax_nopriv_hec_search_products', 'hec_ajax_search_products' );
function hec_ajax_search_products() {
    check_ajax_referer( 'hec_search_nonce', 'nonce' );

    $term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

    if ( mb_strlen( $term ) < 2 ) {
        wp_send_json_success( [ 'items' => [] ] );
    }

    $query = new WP_Query( [
        'post_type'           => 'product',
        'post_status'         => 'publish',
        's'                   => $term,
        'posts_per_page'      => 6,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ] );

    $items = [];

    while ( $query->have_posts() ) {
        $query->the_post();
        $product = wc_get_product( get_the_ID() );
        if ( ! $product ) {
            continue;
        }
        $items[] = [
            'name'  => $product->get_name(),
            'url'   => get_permalink( $product->get_id() ),
            'image' => $product->get_image( [ 44, 44 ] ),
            'price' => $product->get_price_html(),
        ];
    }
    wp_reset_postdata();

    wp_send_json_success( [
        'items'      => $items,
        'search_url' => add_query_arg( [ 's' => rawurlencode( $term ), 'post_type' => 'product' ], home_url( '/' ) ),
    ] );
}

add_action( 'wp_enqueue_scripts', function () {

    wp_enqueue_style(
        'hec-search-box',
        HEC_THEME_URI . '/assets/css/search-box.css',
        [ 'ecomx-style' ],
        HEC_THEME_VERSION
    );

    wp_enqueue_script(
        'hec-search-box-js',
        HEC_THEME_URI . '/assets/js/search-box.js',
        [],
        HEC_THEME_VERSION,
        true
    );

    wp_localize_script( 'hec-search-box-js', 'hecSearchAjax', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'hec_search_nonce' ),
    ] );
});
