<?php
/**
 * Archive template for catalog downloads.
 */

get_header();
?>
<main class="fw-b2b-container">
  <header>
    <h1><?php post_type_archive_title(); ?></h1>
    <p><?php esc_html_e('Download Luxstage product catalogs and technical documents.', 'luxstage'); ?></p>
  </header>

  <?php if (have_posts()) : ?>
    <section class="fw-b2b-grid">
      <?php while (have_posts()) : the_post(); ?>
        <?php
        $pdf_file = luxstage_field('pdf_file');
        $pdf_url = is_array($pdf_file) && isset($pdf_file['url']) ? $pdf_file['url'] : '';
        ?>
        <article <?php post_class('fw-b2b-card'); ?>>
          <h2><?php the_title(); ?></h2>
          <?php the_excerpt(); ?>
          <?php if ($pdf_url) : ?>
            <a href="<?php echo esc_url($pdf_url); ?>" download>
              <?php esc_html_e('Download PDF', 'luxstage'); ?>
            </a>
          <?php endif; ?>
        </article>
      <?php endwhile; ?>
    </section>
    <?php the_posts_pagination(); ?>
  <?php else : ?>
    <p><?php esc_html_e('No catalogs published yet.', 'luxstage'); ?></p>
  <?php endif; ?>
</main>
<?php
get_footer();
