<?php
/**
 * Site-wide tweaks: admin cleanup, performance, misc hooks that
 * don't belong to a specific WooCommerce area.
 */

defined( 'ABSPATH' ) || exit;

// Example: disable emoji scripts for performance
// add_action( 'init', function () {
//     remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
//     remove_action( 'wp_print_styles', 'print_emoji_styles' );
// });

// Example: remove "Powered by WordPress" style admin bar clutter, etc.
