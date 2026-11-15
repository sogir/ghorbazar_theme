<?php
/**
 * Store details (Appearance → Customize → Store Details).
 *
 * Stored in the database, so they survive theme updates. Each value is
 * exposed as the HEC_* constant the snippets already use, unless that
 * constant was already defined by wp-content/ecomx-config.php or a
 * child theme's config.php.
 */

defined( 'ABSPATH' ) || exit;

function hec_store_settings_fields() {
    return [
        'HEC_PHONE_NUMBER'           => [ 'Phone number', 'e.g. 01XXXXXXXXX' ],
        'HEC_WHATSAPP_NUMBER'        => [ 'WhatsApp number', 'Digits only with country code, e.g. 8801XXXXXXXXX' ],
        'HEC_MESSENGER_USERNAME'     => [ 'Facebook page username (Messenger)', 'The part after m.me/ — e.g. yourpage' ],
        'HEC_DELIVERY_INSIDE_DHAKA'  => [ 'Delivery charge text — inside Dhaka', 'Shown on product pages, e.g. 60 TK' ],
        'HEC_DELIVERY_OUTSIDE_DHAKA' => [ 'Delivery charge text — outside Dhaka', 'Shown on product pages, e.g. 100 TK' ],
    ];
}

foreach ( hec_store_settings_fields() as $hec_const => $hec_field ) {
    if ( ! defined( $hec_const ) ) {
        $hec_value = get_theme_mod( strtolower( $hec_const ), '' );
        if ( '' !== $hec_value ) {
            define( $hec_const, $hec_value );
        }
    }
}
unset( $hec_const, $hec_field, $hec_value );

add_action( 'customize_register', function ( WP_Customize_Manager $wpc ) {
    $wpc->add_section( 'hec_store', [
        'title'       => 'Store Details',
        'priority'    => 25,
        'description' => 'Contact numbers and delivery charges used across the site (product page buttons, mobile bottom bar, footer, checkout). Leave a field empty to hide what uses it.',
    ] );
    foreach ( hec_store_settings_fields() as $const => [ $label, $help ] ) {
        $id = strtolower( $const );
        $wpc->add_setting( $id, [ 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ] );
        $wpc->add_control( $id, [
            'label'       => $label,
            'description' => $help,
            'section'     => 'hec_store',
            'type'        => 'text',
        ] );
    }
} );
