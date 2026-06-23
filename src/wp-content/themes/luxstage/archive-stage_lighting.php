<?php
/**
 * Archive template for Stage Lighting CPT.
 */

get_header();
?>
<main class="fw-b2b-container">
  <header>
    <h1><?php post_type_archive_title(); ?></h1>
    <p><?php esc_html_e('Browse all stage lighting products.', 'luxstage'); ?></p>
  </header>

  <?php if (have_posts()) : ?>
    <section class="fw-b2b-grid">
      <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class('fw-b2b-card'); ?>>
          <a href="<?php the_permalink(); ?>">
            <?php if (has_post_thumbnail()) : ?>
              <?php the_post_thumbnail('medium'); ?>
            <?php endif; ?>
            <h2><?php the_title(); ?></h2>
          </a>
          <p><?php echo esc_html(get_field('wattage') ?: ''); ?></p>
          <p><?php echo esc_html(get_field('channels') ?: ''); ?></p>
        </article>
      <?php endwhile; ?>
    </section>
    <?php the_posts_pagination(); ?>
  <?php else : ?>
    <p><?php esc_html_e('No stage lighting products published yet.', 'luxstage'); ?></p>
  <?php endif; ?>
</main>
<?php
get_footer();
