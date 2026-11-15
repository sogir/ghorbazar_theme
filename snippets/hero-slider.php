<?php
/**
 * Homepage hero: category sidebar + slider.
 *
 * [hec_home_hero]        — sidebar (desktop) + slider + category chips (mobile)
 * [hec_hero_slider]      — slider only
 * [hec_category_sidebar] — "All Categories" card (wraps [hec_category_dropdown])
 * [hec_category_chips]   — horizontal, scrollable category icons
 *
 * Slides are edited in Appearance → Customize → Homepage Slider
 * (image, title, text, button label, button link, background). Empty
 * slides are skipped. Until a slide has its own image, it falls back
 * to a product photo so the slider never looks empty.
 */

defined( 'ABSPATH' ) || exit;

const HEC_SLIDER_COUNT = 6;

/**
 * Built-in defaults (used until the slide is edited in the Customizer).
 */
function hec_slider_defaults() {
    $cat = function ( $slug ) {
        $t = get_term_by( 'slug', $slug, 'product_cat' );
        return $t ? get_term_link( $t ) : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) );
    };
    return [
        1 => [
            'kicker'  => 'New arrival',
            'title'   => 'iPhone 17 Series',
            'text'    => 'The latest iPhones, 100% original. Cash on delivery all over Bangladesh.',
            'button'  => 'Shop iPhone',
            'link'    => $cat( 'apple-iphone' ),
            'product' => 'iphone-17-pro',
            'bg'      => 'green',
        ],
        2 => [
            'kicker'  => 'Premium sound',
            'title'   => 'Headphones & Earbuds',
            'text'    => 'Sony, QCY, SoundPeats and AirPods — wireless audio for every budget.',
            'button'  => 'Shop Audio',
            'link'    => $cat( 'audio' ),
            'product' => 'sony-wh-100',
            'bg'      => 'orange',
        ],
        3 => [
            'kicker'  => 'Work & create',
            'title'   => 'iPad Pro & Mac mini',
            'text'    => 'Powerful Apple devices for work, study and play.',
            'button'  => 'Shop Now',
            'link'    => $cat( 'tablets' ),
            'product' => 'ipad-pro-m5-2025',
            'bg'      => 'light',
        ],
        4 => [],
        5 => [],
        6 => [],
    ];
}

function hec_slider_backgrounds() {
    return [
        'green'  => 'Dark green',
        'orange' => 'Orange',
        'light'  => 'Light green',
        'white'  => 'White',
        'black'  => 'Black',
    ];
}

/**
 * Customizer controls.
 */
add_action( 'customize_register', function ( WP_Customize_Manager $wpc ) {
    $wpc->add_section( 'hec_slider', [
        'title'       => 'Homepage Slider',
        'priority'    => 30,
        'description' => 'Up to ' . HEC_SLIDER_COUNT . ' slides. A slide shows when it has a title or an image. Recommended image: product photo on a transparent or plain background, about 800×800. Banner slide: add only an image (no title/text/button) and it fills the whole slide; the button link makes the banner clickable.',
    ] );

    $defaults = hec_slider_defaults();

    for ( $i = 1; $i <= HEC_SLIDER_COUNT; $i++ ) {
        $d = $defaults[ $i ] ?? [];
        $fields = [
            'kicker' => [ 'Small label above title', 'text' ],
            'title'  => [ 'Title', 'text' ],
            'text'   => [ 'Text', 'textarea' ],
            'button' => [ 'Button label', 'text' ],
            'link'   => [ 'Button link', 'url' ],
        ];
        $wpc->add_setting( "hec_slide_{$i}_image", [ 'default' => 0, 'sanitize_callback' => 'absint' ] );
        $wpc->add_control( new WP_Customize_Media_Control( $wpc, "hec_slide_{$i}_image", [
            'label'     => "Slide {$i} — image",
            'section'   => 'hec_slider',
            'mime_type' => 'image',
        ] ) );
        foreach ( $fields as $key => [ $label, $type ] ) {
            $wpc->add_setting( "hec_slide_{$i}_{$key}", [
                'default'           => $d[ $key ] ?? '',
                'sanitize_callback' => 'url' === $type ? 'esc_url_raw' : ( 'textarea' === $type ? 'sanitize_textarea_field' : 'sanitize_text_field' ),
            ] );
            $wpc->add_control( "hec_slide_{$i}_{$key}", [
                'label'   => "Slide {$i} — {$label}",
                'section' => 'hec_slider',
                'type'    => $type,
            ] );
        }
        $wpc->add_setting( "hec_slide_{$i}_bg", [ 'default' => $d['bg'] ?? 'green', 'sanitize_callback' => 'sanitize_key' ] );
        $wpc->add_control( "hec_slide_{$i}_bg", [
            'label'   => "Slide {$i} — background",
            'section' => 'hec_slider',
            'type'    => 'select',
            'choices' => hec_slider_backgrounds(),
        ] );
    }

    $wpc->add_setting( 'hec_slider_autoplay', [ 'default' => 5, 'sanitize_callback' => 'absint' ] );
    $wpc->add_control( 'hec_slider_autoplay', [
        'label'       => 'Autoplay (seconds, 0 = off)',
        'section'     => 'hec_slider',
        'type'        => 'number',
        'input_attrs' => [ 'min' => 0, 'max' => 30 ],
    ] );
} );

/**
 * Resolved slide list.
 */
function hec_get_slides() {
    $defaults = hec_slider_defaults();
    $slides   = [];
    for ( $i = 1; $i <= HEC_SLIDER_COUNT; $i++ ) {
        $d = $defaults[ $i ] ?? [];
        $s = [];
        foreach ( [ 'kicker', 'title', 'text', 'button', 'link', 'bg' ] as $k ) {
            $s[ $k ] = get_theme_mod( "hec_slide_{$i}_{$k}", $d[ $k ] ?? '' );
        }
        $img = (int) get_theme_mod( "hec_slide_{$i}_image", 0 );
        if ( ! $img && ! empty( $d['product'] ) ) {
            $p = get_page_by_path( $d['product'], OBJECT, 'product' );
            $img = $p ? (int) get_post_thumbnail_id( $p ) : 0;
        }
        $s['image'] = $img;
        if ( '' === trim( $s['title'] ) && ! $img ) {
            continue;
        }
        if ( ! array_key_exists( $s['bg'], hec_slider_backgrounds() ) ) {
            $s['bg'] = 'green';
        }
        $slides[] = $s;
    }
    return $slides;
}

add_shortcode( 'hec_hero_slider', function () {
    $slides = hec_get_slides();
    if ( ! $slides ) {
        return '';
    }
    $autoplay = (int) get_theme_mod( 'hec_slider_autoplay', 5 );
    wp_enqueue_style( 'hec-hero-slider' );
    wp_enqueue_script( 'hec-hero-slider-js' );

    ob_start();
    ?>
    <div class="hec-slider" data-autoplay="<?php echo esc_attr( $autoplay ); ?>" aria-roledescription="carousel" aria-label="Featured">
        <div class="hec-slider-track">
            <?php foreach ( $slides as $n => $s ) :
                $is_banner = $s['image'] && '' === trim( $s['title'] . $s['text'] . $s['button'] . $s['kicker'] );
                ?>
                <?php if ( $is_banner ) : ?>
                <div class="hec-slide hec-slide--banner hec-slide--<?php echo esc_attr( $s['bg'] ); ?><?php echo 0 === $n ? ' is-active' : ''; ?>"
                     role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $n + 1 ) . ' / ' . count( $slides ) ); ?>"
                     <?php echo 0 === $n ? '' : 'aria-hidden="true"'; ?>>
                    <?php
                    $img = wp_get_attachment_image( $s['image'], 'full', false, [
                        'loading'       => 0 === $n ? 'eager' : 'lazy',
                        'fetchpriority' => 0 === $n ? 'high' : 'auto',
                        'sizes'         => '(max-width: 767px) 100vw, 960px',
                    ] );
                    if ( $s['link'] ) {
                        echo '<a class="hec-slide-banner-link" href="' . esc_url( $s['link'] ) . '"' . ( 0 === $n ? '' : ' tabindex="-1"' ) . '>' . $img . '</a>';
                    } else {
                        echo $img;
                    }
                    ?>
                </div>
                <?php continue; endif; ?>
                <div class="hec-slide hec-slide--<?php echo esc_attr( $s['bg'] ); ?><?php echo 0 === $n ? ' is-active' : ''; ?>"
                     role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $n + 1 ) . ' / ' . count( $slides ) ); ?>"
                     <?php echo 0 === $n ? '' : 'aria-hidden="true"'; ?>>
                    <div class="hec-slide-copy">
                        <?php if ( $s['kicker'] ) : ?><span class="hec-slide-kicker"><?php echo esc_html( $s['kicker'] ); ?></span><?php endif; ?>
                        <?php if ( $s['title'] ) : ?><h2 class="hec-slide-title"><?php echo esc_html( $s['title'] ); ?></h2><?php endif; ?>
                        <?php if ( $s['text'] ) : ?><p class="hec-slide-text"><?php echo esc_html( $s['text'] ); ?></p><?php endif; ?>
                        <?php if ( $s['button'] && $s['link'] ) : ?>
                            <a class="hec-slide-btn" href="<?php echo esc_url( $s['link'] ); ?>"<?php echo 0 === $n ? '' : ' tabindex="-1"'; ?>><?php echo esc_html( $s['button'] ); ?></a>
                        <?php endif; ?>
                    </div>
                    <?php if ( $s['image'] ) : ?>
                        <div class="hec-slide-media">
                            <?php echo wp_get_attachment_image( $s['image'], 'large', false, [
                                'loading'       => 0 === $n ? 'eager' : 'lazy',
                                'fetchpriority' => 0 === $n ? 'high' : 'auto',
                                'alt'           => $s['title'],
                            ] ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ( count( $slides ) > 1 ) : ?>
            <button type="button" class="hec-slider-arrow hec-slider-prev" aria-label="Previous slide">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <button type="button" class="hec-slider-arrow hec-slider-next" aria-label="Next slide">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="hec-slider-dots">
                <?php foreach ( $slides as $n => $s ) : ?>
                    <button type="button" class="hec-slider-dot<?php echo 0 === $n ? ' is-active' : ''; ?>" aria-label="Go to slide <?php echo (int) $n + 1; ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
} );

add_shortcode( 'hec_category_sidebar', function ( $atts ) {
    $atts = shortcode_atts( [ 'title' => 'All Categories', 'limit' => 12 ], $atts, 'hec_category_sidebar' );
    wp_enqueue_style( 'hec-hero-slider' );
    ob_start();
    ?>
    <aside class="hec-cat-sidebar">
        <div class="hec-cat-sidebar-head">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
            <?php echo esc_html( $atts['title'] ); ?>
        </div>
        <?php echo do_shortcode( '[hec_category_dropdown limit="' . (int) $atts['limit'] . '" orderby="menu_order"]' ); ?>
        <?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
            <a class="hec-cat-sidebar-all" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">View all products →</a>
        <?php endif; ?>
    </aside>
    <?php
    return ob_get_clean();
} );

add_shortcode( 'hec_category_chips', function () {
    if ( ! taxonomy_exists( 'product_cat' ) ) {
        return '';
    }
    $cats = get_terms( [
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0,
        'orderby'    => 'menu_order',
        'exclude'    => [ get_option( 'default_product_cat' ) ],
    ] );
    if ( empty( $cats ) || is_wp_error( $cats ) ) {
        return '';
    }
    wp_enqueue_style( 'hec-hero-slider' );
    ob_start();
    ?>
    <nav class="hec-cat-chips" aria-label="Categories">
        <?php foreach ( $cats as $c ) :
            $thumb = get_term_meta( $c->term_id, 'thumbnail_id', true );
            ?>
            <a class="hec-cat-chip" href="<?php echo esc_url( get_term_link( $c ) ); ?>">
                <span class="hec-cat-chip-img"><?php echo $thumb ? wp_get_attachment_image( $thumb, 'thumbnail', false, [ 'alt' => '' ] ) : ''; ?></span>
                <span class="hec-cat-chip-name"><?php echo esc_html( $c->name ); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
    return ob_get_clean();
} );

add_shortcode( 'hec_home_hero', function () {
    return '<section class="hec-home-hero">'
        . do_shortcode( '[hec_category_sidebar]' )
        . '<div class="hec-home-hero-main">' . do_shortcode( '[hec_hero_slider]' ) . '</div>'
        . '</section>'
        . do_shortcode( '[hec_category_chips]' );
} );

add_action( 'wp_enqueue_scripts', function () {
    wp_register_style( 'hec-hero-slider', HEC_THEME_URI . '/assets/css/hero-slider.css', [ 'ecomx-style', 'hec-category-dropdown' ], HEC_THEME_VERSION );
    wp_register_script( 'hec-hero-slider-js', HEC_THEME_URI . '/assets/js/hero-slider.js', [], HEC_THEME_VERSION, true );

    // Load in <head> (no flash of unstyled content) when the page uses these shortcodes.
    $post = get_post();
    if ( is_singular() && $post && preg_match( '/\[hec_(home_hero|hero_slider|category_sidebar|category_chips)\b/', $post->post_content ) ) {
        wp_enqueue_style( 'hec-hero-slider' );
        wp_enqueue_script( 'hec-hero-slider-js' );
    }
} );
