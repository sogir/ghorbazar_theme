<?php
/**
 * Footer settings (Appearance → Customize → Footer) and helpers used by
 * template-parts/footer.php. Phone / WhatsApp / Messenger come from
 * config.php so they stay in sync with the product page buttons.
 */

defined( 'ABSPATH' ) || exit;

function hec_footer_social_networks() {
    return [
        'facebook'  => 'Facebook',
        'instagram' => 'Instagram',
        'youtube'   => 'YouTube',
        'tiktok'    => 'TikTok',
    ];
}

add_action( 'customize_register', function ( WP_Customize_Manager $wpc ) {
    $wpc->add_section( 'hec_footer', [
        'title'       => 'Footer',
        'priority'    => 35,
        'description' => 'Phone, WhatsApp and Messenger are set in the theme\'s config.php.',
    ] );

    $fields = [
        'hec_footer_about'     => [ 'About text', 'textarea', 'Original gadgets and electronics with cash on delivery all over Bangladesh.' ],
        'hec_footer_address'   => [ 'Address', 'textarea', 'Dhaka, Bangladesh' ],
        'hec_footer_email'     => [ 'Email (optional)', 'email', '' ],
        'hec_footer_hours'     => [ 'Opening hours (optional)', 'text', '' ],
        'hec_footer_copyright' => [ 'Copyright text (after © year)', 'text', get_bloginfo( 'name' ) . '. All rights reserved.' ],
    ];
    foreach ( hec_footer_social_networks() as $key => $label ) {
        $fields[ "hec_social_{$key}" ] = [ "{$label} URL", 'url', '' ];
    }
    $fields['hec_footer_show_features'] = [ 'Show the feature strip (Cash on delivery, etc.)', 'checkbox', false ];

    foreach ( $fields as $id => [ $label, $type, $default ] ) {
        $sanitize = [
            'textarea' => 'sanitize_textarea_field',
            'email'    => 'sanitize_email',
            'url'      => 'esc_url_raw',
            'checkbox' => 'rest_sanitize_boolean',
        ][ $type ] ?? 'sanitize_text_field';
        $wpc->add_setting( $id, [ 'default' => $default, 'sanitize_callback' => $sanitize ] );
        $wpc->add_control( $id, [ 'label' => $label, 'section' => 'hec_footer', 'type' => $type ] );
    }
} );

/**
 * Inline SVG icons for the footer (no icon font).
 */
function hec_footer_icon( $name ) {
    $icons = [
        'cash'      => '<path d="M3 7h18v10H3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.8"/><path d="M6.5 10v4M17.5 10v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'truck'     => '<path d="M2 6h11v9H2zM13 9h4l3 3v3h-7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="6" cy="17" r="1.8" stroke="currentColor" stroke-width="1.8"/><circle cx="17" cy="17" r="1.8" stroke="currentColor" stroke-width="1.8"/>',
        'shield'    => '<path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8.8 12l2.2 2.2 4.2-4.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'headset'   => '<path d="M4 14v-2a8 8 0 0116 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M4 14h3v5H5a1 1 0 01-1-1zM20 14h-3v5h2a1 1 0 001-1z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'phone'     => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a1 1 0 01-1 1A16 16 0 014 5a1 1 0 011-1z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'whatsapp'  => '<path d="M4 20l1.3-4A8 8 0 1112 20a8 8 0 01-4-1.1z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 9.5c.3 2 2.2 4 4.5 4.6l1-1.1 1.6.8c-.2 1-1 1.7-2 1.7C11.3 15.5 8.5 12.7 8.5 9.9c0-1 .7-1.8 1.7-2l.8 1.6z" fill="currentColor"/>',
        'messenger' => '<path d="M12 3C7 3 3 6.7 3 11.3c0 2.6 1.3 4.9 3.3 6.4V21l3-1.7c.9.3 1.8.4 2.7.4 5 0 9-3.7 9-8.4S17 3 12 3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7.5 13.5l3-3.2 2 2 3-3.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'mail'      => '<path d="M3 6h18v12H3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M3 7l9 6 9-6" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'pin'       => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0114 0C19 14.8 12 21 12 21z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="9.5" r="2.5" stroke="currentColor" stroke-width="1.8"/>',
        'clock'     => '<circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><path d="M12 7.5V12l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'facebook'  => '<path d="M14 8h2.5V4.5H14A4 4 0 0010 8.5V11H7.5v3.5H10V21h3.5v-6.5H16l.5-3.5h-3V8.8c0-.5.3-.8.5-.8z" fill="currentColor"/>',
        'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/><circle cx="17.2" cy="6.8" r="1.1" fill="currentColor"/>',
        'youtube'   => '<path d="M21 8.2c-.2-1.4-1.2-2.4-2.6-2.5C16.6 5.5 14.3 5.4 12 5.4s-4.6.1-6.4.3C4.2 5.8 3.2 6.8 3 8.2 2.8 9.5 2.8 10.7 2.8 12s0 2.5.2 3.8c.2 1.4 1.2 2.4 2.6 2.5 1.8.2 4.1.3 6.4.3s4.6-.1 6.4-.3c1.4-.1 2.4-1.1 2.6-2.5.2-1.3.2-2.5.2-3.8s0-2.5-.2-3.8z" fill="currentColor"/><path d="M10 9.2v5.6l4.8-2.8z" fill="var(--ecx-brand-dark, #083f0e)"/>',
        'tiktok'    => '<path d="M14.5 3h3c.2 1.9 1.6 3.4 3.5 3.6v3c-1.3 0-2.5-.4-3.5-1v6.2a5.6 5.6 0 11-5.6-5.6c.3 0 .6 0 .9.1v3.1a2.6 2.6 0 102 2.5z" fill="currentColor"/>',
    ];
    if ( empty( $icons[ $name ] ) ) {
        return '';
    }
    return '<svg class="ecx-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">' . $icons[ $name ] . '</svg>';
}

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'ecomx-footer', HEC_THEME_URI . '/assets/css/footer.css', [ 'ecomx-style' ], HEC_THEME_VERSION );
} );
