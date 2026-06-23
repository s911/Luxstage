<?php
/**
 * Single template for Stage Lighting CPT.
 */

get_header();
?>
<main class="fw-b2b-container">
  <?php while (have_posts()) : the_post(); ?>
    <article <?php post_class(); ?>>
      <h1><?php the_title(); ?></h1>
      <?php if (has_post_thumbnail()) : ?>
        <div><?php the_post_thumbnail('large'); ?></div>
      <?php endif; ?>

      <section>
        <h2><?php esc_html_e('Specifications', 'luxstage'); ?></h2>
        <ul>
          <li><strong><?php esc_html_e('Wattage', 'luxstage'); ?>:</strong> <?php echo esc_html((string) get_field('wattage')); ?></li>
          <li><strong><?php esc_html_e('Channels', 'luxstage'); ?>:</strong> <?php echo esc_html((string) get_field('channels')); ?></li>
          <li><strong><?php esc_html_e('Prism', 'luxstage'); ?>:</strong> <?php echo esc_html((string) get_field('prism')); ?></li>
          <li><strong><?php esc_html_e('IP Rating', 'luxstage'); ?>:</strong> <?php echo esc_html((string) get_field('ip_rating')); ?></li>
        </ul>
      </section>

      <section>
        <?php the_content(); ?>
      </section>
    </article>
  <?php endwhile; ?>
</main>
<?php
get_footer();
