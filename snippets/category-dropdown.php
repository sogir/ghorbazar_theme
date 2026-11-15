<?php
/**
 * Category dropdown shortcode — [hec_category_dropdown]
 *
 * Renders a vertical list of WooCommerce product categories with
 * thumbnail icons, hides empty categories, and shows a flyout submenu
 * of child categories on hover (desktop) / tap (touch devices).
 *
 * Usage:
 *   [hec_category_dropdown limit="6"]
 *   [hec_category_dropdown limit="10" orderby="name"]
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'hec_category_dropdown', function ( $atts ) {

    if ( ! taxonomy_exists( 'product_cat' ) ) {
        return '';
    }

    $atts = shortcode_atts( [
        'limit'   => 8,
        'orderby' => 'name', // name, count, menu_order
        'order'   => 'ASC',
    ], $atts, 'hec_category_dropdown' );

    $categories = get_terms( [
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,           // hides categories with zero products
        'parent'     => 0,               // top-level only; children fetched per-item below
        'number'     => (int) $atts['limit'],
        'orderby'    => $atts['orderby'],
        'order'      => $atts['order'],
        'exclude'    => [ get_option( 'default_product_cat' ) ], // skip "Uncategorized"
    ] );

    if ( empty( $categories ) || is_wp_error( $categories ) ) {
        return '<p>No categories found.</p>';
    }

    ob_start();
    ?>
    <div class="hec-cat-dropdown">
        <ul class="hec-cat-list">
            <?php foreach ( $categories as $cat ) :

                $thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
                $image_url    = $thumbnail_id
                    ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' )
                    : wc_placeholder_img_src( 'thumbnail' );

                // Fetch child categories (also hiding empty ones)
                $children = get_terms( [
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => true,
                    'parent'     => $cat->term_id,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                ] );
                $has_children = ! empty( $children ) && ! is_wp_error( $children );
                ?>

                <li class="hec-cat-item <?php echo $has_children ? 'has-children' : ''; ?>">
                    <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="hec-cat-link">
                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $cat->name ); ?>" class="hec-cat-icon">
                        <span class="hec-cat-name"><?php echo esc_html( $cat->name ); ?></span>
                        <?php if ( $has_children ) : ?>
                            <svg class="hec-cat-arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        <?php endif; ?>
                    </a>

                    <?php if ( $has_children ) : ?>
                        <ul class="hec-cat-submenu">
                            <?php foreach ( $children as $child ) : ?>
                                <li>
                                    <a href="<?php echo esc_url( get_term_link( $child ) ); ?>">
                                        <?php echo esc_html( $child->name ); ?>
                                        <span class="hec-cat-count">(<?php echo (int) $child->count; ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>

            <?php endforeach; ?>
        </ul>
    </div>
    <?php

    return ob_get_clean();
});