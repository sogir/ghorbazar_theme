<?php
/**
 * Additional single-product sections:
 * [hec_product_description] — tabbed description area
 * [hec_product_video]       — YouTube video box (reads a custom field)
 * [hec_related_products]    — related products, reuses product-loop card styling
 */

defined( 'ABSPATH' ) || exit;

/**
 * Description tab section.
 * Renders the product's normal WooCommerce content (description),
 * wrapped in the tab-style container from the reference design.
 */
add_shortcode( 'hec_product_description', function () {

    global $product;
    if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
        $product = wc_get_product( get_the_ID() );
    }
    if ( ! $product ) {
        return '';
    }

    ob_start();
    ?>
    <div class="hec-pd-description-wrap">
        <div class="hec-pd-desc-nav">
            <span class="hec-pd-desc-nav-item is-active">Description</span>
        </div>
        <div class="hec-pd-desc-content">
            <?php echo wpautop( wp_kses_post( $product->get_description() ) ); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * Video section. Reads a YouTube URL from a custom field named
 * "hec_video_url" on the product (Custom Fields panel) — add that
 * field per-product to show a video, otherwise this renders nothing.
 */
add_shortcode( 'hec_product_video', function () {

    $product_id = get_the_ID();
    $video_url  = get_post_meta( $product_id, 'hec_video_url', true );

    if ( ! $video_url ) {
        return '';
    }

    // Convert a normal YouTube URL into an embeddable one.
    $embed_url = $video_url;
    if ( preg_match( '/(?:youtu\.be\/|youtube\.com\/watch\?v=)([a-zA-Z0-9_-]+)/', $video_url, $matches ) ) {
        $embed_url = 'https://www.youtube.com/embed/' . $matches[1];
    }

    ob_start();
    ?>
    <div class="hec-pd-video-box">
        <h2 class="hec-pd-video-title">Video</h2>
        <div class="hec-pd-video-frame">
            <iframe src="<?php echo esc_url( $embed_url ); ?>" title="Product video" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen></iframe>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * Related products — same category as current product, excludes
 * itself, reuses the exact card markup/classes from [hec_product_loop]
 * so styling (buttons, badge, hover states) stays consistent.
 *
 * Usage: [hec_related_products limit="12" columns="6"]
 */
add_shortcode( 'hec_related_products', function ( $atts ) {

    if ( ! class_exists( 'WooCommerce' ) ) {
        return '';
    }

    $atts = shortcode_atts( [
        'limit'   => 12,
        'columns' => 6,
    ], $atts, 'hec_related_products' );

    $product_id = get_the_ID();
    $product    = wc_get_product( $product_id );
    if ( ! $product ) {
        return '';
    }

    $related_ids = wc_get_related_products( $product_id, (int) $atts['limit'] );

    if ( empty( $related_ids ) ) {
        return '';
    }

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'post__in'       => $related_ids,
        'posts_per_page' => (int) $atts['limit'],
        'orderby'        => 'post__in',
    ];

    $products = new WP_Query( $args );

    if ( ! $products->have_posts() ) {
        return '';
    }

    ob_start();
    ?>
    <div class="hec-related-section">
        <h5 class="hec-related-title">Related Product</h5>
        <div class="hec-product-grid" style="--hec-grid-columns: <?php echo (int) $atts['columns']; ?>;">
            <?php
            while ( $products->have_posts() ) :
                $products->the_post();
                hec_render_product_card();
            endwhile;
            ?>
        </div>
    </div>
    <?php
    wp_reset_postdata();

    return ob_get_clean();
});