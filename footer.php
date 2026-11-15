<?php
/**
 * Site footer. Replaced entirely by an Elementor Pro "Footer" template
 * when one is assigned.
 */
defined( 'ABSPATH' ) || exit;
?>
</div><!-- #content -->
<?php
if ( ! is_page_template( 'templates/canvas.php' ) && ! hec_elementor_location( 'footer' ) ) {
    get_template_part( 'template-parts/footer' );
}
?>
</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>
