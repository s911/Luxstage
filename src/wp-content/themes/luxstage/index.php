<?php
/**
 * Minimal fallback template.
 */

get_header();
?>
<main class="fw-b2b-container">
  <?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
      <article <?php post_class(); ?>>
        <h1><?php the_title(); ?></h1>
        <?php the_content(); ?>
      </article>
    <?php endwhile; ?>
  <?php else : ?>
    <p><?php esc_html_e('No content available.', 'luxstage'); ?></p>
  <?php endif; ?>
</main>
<?php
get_footer();
