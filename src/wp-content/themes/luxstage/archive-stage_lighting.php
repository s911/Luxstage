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

  <?php
  $product_categories = get_terms([
      'taxonomy' => 'product_category',
      'hide_empty' => false,
  ]);
  ?>
  <?php if (!is_wp_error($product_categories) && $product_categories) : ?>
    <nav aria-label="<?php esc_attr_e('Product categories', 'luxstage'); ?>">
      <?php foreach ($product_categories as $category) : ?>
        <a href="<?php echo esc_url(get_term_link($category)); ?>"><?php echo esc_html($category->name); ?></a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>

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
          <p><?php echo esc_html((string) luxstage_field('wattage')); ?></p>
          <p><?php echo esc_html((string) luxstage_field('light_source_type')); ?></p>
          <p><?php echo esc_html((string) luxstage_field('channels')); ?></p>
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
