<?php
/**
 * Branded header for regular pages: breadcrumb, title, optional subtitle
 * (the page's Excerpt field).
 */
defined( 'ABSPATH' ) || exit;
?>
<header class="ecx-page-hero">
    <nav class="ecx-page-hero__crumbs" aria-label="Breadcrumb">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
        <span aria-hidden="true">/</span>
        <span><?php the_title(); ?></span>
    </nav>
    <?php the_title( '<h1 class="ecx-page-hero__title">', '</h1>' ); ?>
    <?php if ( has_excerpt() ) : ?>
        <p class="ecx-page-hero__subtitle"><?php echo esc_html( get_the_excerpt() ); ?></p>
    <?php endif; ?>
</header>
