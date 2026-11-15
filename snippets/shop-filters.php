<?php
/**
 * Shop / archive filters — works automatically on the Shop page,
 * any product Category/Tag archive, AND product search results.
 *
 * Categories are plain navigation links to each category's real
 * archive page (get_term_link()), not an in-place checkbox filter —
 * this sidesteps any slug/encoding edge cases entirely and always
 * matches WordPress's own category routing exactly. Sort and
 * "On Sale Only" still work as live filters layered onto the main
 * query via pre_get_posts, since those don't involve term slugs.
 *
 * [hec_shop_filters] — sort dropdown, on-sale checkbox, category
 *   navigation list, mobile toggle tab.
 *
 * [hec_shop_grid columns="4"] — renders the real main loop (already
 *   filtered/searched/paginated by WordPress + the hook below).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Layer sort/on-sale constraints onto the main query, only on the
 * pages this system is meant to affect.
 */
add_action( 'pre_get_posts', function ( $query ) {

    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    $is_shop_context =
        is_shop() ||
        is_product_category() ||
        is_product_tag() ||
        ( is_search() && 'product' === $query->get( 'post_type' ) );

    if ( ! $is_shop_context ) {
        return;
    }

    $query->set( 'post_type', 'product' );
    $query->set( 'post_status', 'publish' );

    // ---- On sale only ----
    if ( ! empty( $_GET['on_sale'] ) ) {
        $sale_ids = wc_get_product_ids_on_sale();
        $query->set( 'post__in', ! empty( $sale_ids ) ? $sale_ids : [ 0 ] );
    }

    // ---- Sort ----
    if ( ! empty( $_GET['orderby'] ) ) {
        switch ( sanitize_text_field( $_GET['orderby'] ) ) {
            case 'price_asc':
                $query->set( 'meta_key', '_price' );
                $query->set( 'orderby', 'meta_value_num' );
                $query->set( 'order', 'ASC' );
                break;
            case 'price_desc':
                $query->set( 'meta_key', '_price' );
                $query->set( 'orderby', 'meta_value_num' );
                $query->set( 'order', 'DESC' );
                break;
            case 'newest':
                $query->set( 'orderby', 'date' );
                $query->set( 'order', 'DESC' );
                break;
            case 'popularity':
                $query->set( 'meta_key', 'total_sales' );
                $query->set( 'orderby', 'meta_value_num' );
                $query->set( 'order', 'DESC' );
                break;
        }
    }

    // ---- Products per page ----
    if ( ! empty( $_GET['per_page'] ) ) {
        $query->set( 'posts_per_page', max( 1, (int) $_GET['per_page'] ) );
    }
});

/**
 * Filter sidebar / bar shortcode.
 */
add_shortcode( 'hec_shop_filters', function () {

    if ( ! class_exists( 'WooCommerce' ) ) {
        return '';
    }

    $categories = get_terms( [
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0,
        'exclude'    => [ get_option( 'default_product_cat' ) ],
    ] );

    $current_cat_id = is_product_category() ? get_queried_object_id() : 0;

    $on_sale = ! empty( $_GET['on_sale'] );
    $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : '';

    ob_start();
    ?>
    <!-- Mobile-only toggle — fixed icon tab on the left edge, hidden on desktop -->
    <button type="button" class="hec-filter-toggle" id="hec-filter-toggle" aria-label="Open filters">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>

    <div class="hec-filter-backdrop" id="hec-filter-backdrop"></div>

    <form method="get" class="hec-shop-filters" id="hec-shop-filters">

        <div class="hec-filter-drawer-header">
            <span>Filters</span>
            <button type="button" class="hec-filter-close" id="hec-filter-close" aria-label="Close filters">&times;</button>
        </div>

        <?php // Preserve the current search term so filtering never loses it.
        if ( is_search() ) : ?>
            <input type="hidden" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
            <input type="hidden" name="post_type" value="product">
        <?php endif; ?>

        <div class="hec-filter-group">
            <label class="hec-filter-label" for="hec-sort">Sort by</label>
            <select name="orderby" id="hec-sort" class="hec-filter-select">
                <option value="" <?php selected( $orderby, '' ); ?>>Default</option>
                <option value="newest" <?php selected( $orderby, 'newest' ); ?>>Newest</option>
                <option value="price_asc" <?php selected( $orderby, 'price_asc' ); ?>>Price: Low to High</option>
                <option value="price_desc" <?php selected( $orderby, 'price_desc' ); ?>>Price: High to Low</option>
                <option value="popularity" <?php selected( $orderby, 'popularity' ); ?>>Popularity</option>
            </select>
        </div>

        <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
            <div class="hec-filter-group">
                <span class="hec-filter-label">Categories</span>
                <div class="hec-filter-catlist">
                    <?php foreach ( $categories as $cat ) : ?>
                        <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
                           class="hec-filter-cat-link <?php echo $current_cat_id === $cat->term_id ? 'is-active' : ''; ?>">
                            <span><?php echo esc_html( $cat->name ); ?></span>
                            <span class="hec-filter-count">(<?php echo (int) $cat->count; ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="hec-filter-group">
            <label class="hec-filter-check">
                <input type="checkbox" name="on_sale" value="1" <?php checked( $on_sale ); ?>>
                <span>On Sale Only</span>
            </label>
        </div>

        <div class="hec-filter-actions">
            <button type="submit" class="hec-filter-apply">Apply Filters</button>
            <a href="<?php echo esc_url( strtok( $_SERVER['REQUEST_URI'], '?' ) ); ?><?php echo is_search() ? '?s=' . esc_attr( get_search_query() ) . '&post_type=product' : ''; ?>" class="hec-filter-reset">Reset</a>
        </div>

    </form>
    <?php
    return ob_get_clean();
});

/**
 * Main results grid — uses WordPress's real main loop (already
 * filtered/searched/paginated by WordPress + the pre_get_posts hook
 * above), so it's always in sync with whatever page it's on.
 */
add_shortcode( 'hec_shop_grid', function ( $atts ) {

    if ( ! class_exists( 'WooCommerce' ) ) {
        return '<p>WooCommerce is not active.</p>';
    }

    $atts = shortcode_atts( [
        'columns' => 4,
    ], $atts, 'hec_shop_grid' );

    global $wp_query;

    if ( ! have_posts() ) {
        ob_start();
        ?>
        <div class="hec-shop-empty">
            <p>No products found.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    ob_start();
    ?>
    <div class="hec-product-grid hec-shop-grid" style="--hec-grid-columns: <?php echo (int) $atts['columns']; ?>;">
        <?php
        while ( have_posts() ) :
            the_post();
            global $product;
            $product = wc_get_product( get_the_ID() );
            hec_render_product_card();
        endwhile;
        ?>
    </div>

    <?php if ( $wp_query->max_num_pages > 1 ) : ?>
        <div class="hec-shop-pagination">
            <?php
            echo paginate_links( [
                'total'     => $wp_query->max_num_pages,
                'current'   => max( 1, get_query_var( 'paged' ) ?: get_query_var( 'page' ) ),
                'prev_text' => '‹',
                'next_text' => '›',
                'type'      => 'plain',
            ] );
            ?>
        </div>
    <?php endif; ?>

    <?php
    return ob_get_clean();
});
