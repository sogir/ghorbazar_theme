<?php
/**
 * Post card used in archives, blog index and search results.
 */
defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry-card' ); ?>>
    <?php if ( has_post_thumbnail() ) : ?>
        <a class="entry-card__thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium_large' ); ?></a>
    <?php endif; ?>
    <div class="entry-card__body">
        <?php the_title( '<h2 class="entry-card__title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' ); ?>
        <?php if ( 'post' === get_post_type() ) : ?>
            <p class="entry-meta"><?php echo esc_html( get_the_date() ); ?></p>
        <?php endif; ?>
        <div class="entry-card__excerpt"><?php the_excerpt(); ?></div>
    </div>
</article>
