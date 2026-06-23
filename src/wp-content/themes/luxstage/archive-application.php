<?php
/**
 * Archive template for application cases.
 */

get_header();
?>
<main class="fw-b2b-container">
  <header>
    <h1><?php post_type_archive_title(); ?></h1>
    <p><?php esc_html_e('Explore Luxstage lighting applications for concerts, theatres, clubs, rentals, and outdoor events.', 'luxstage'); ?></p>
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
          <?php the_excerpt(); ?>
        </article>
      <?php endwhile; ?>
    </section>
    <?php the_posts_pagination(); ?>
  <?php else : ?>
    <p><?php esc_html_e('No application cases published yet.', 'luxstage'); ?></p>
  <?php endif; ?>
</main>
<?php
get_footer();
