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

  <?php
  $certifications = get_terms([
      'taxonomy' => 'certification',
      'hide_empty' => false,
  ]);
  $current_certification = sanitize_title((string) ($_GET['certification'] ?? ''));
  $query_args = [
      'post_type' => 'catalog',
      'post_status' => 'publish',
      'posts_per_page' => (int) get_option('posts_per_page'),
      'paged' => max(1, (int) get_query_var('paged')),
  ];

  if ($current_certification !== '') {
      $query_args['tax_query'] = [[
          'taxonomy' => 'certification',
          'field' => 'slug',
          'terms' => [$current_certification],
      ]];
  }

  $catalog_query = new WP_Query($query_args);
  ?>

  <section class="lux-filter-panel">
    <form method="get" action="<?php echo esc_url(get_post_type_archive_link('catalog')); ?>">
      <label for="catalog-certification"><?php esc_html_e('Certification', 'luxstage'); ?></label>
      <select id="catalog-certification" name="certification">
        <option value=""><?php esc_html_e('All Certifications', 'luxstage'); ?></option>
        <?php if (!is_wp_error($certifications) && $certifications) : ?>
          <?php foreach ($certifications as $term) : ?>
            <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($current_certification, $term->slug); ?>>
              <?php echo esc_html($term->name); ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
      <button type="submit"><?php esc_html_e('Apply', 'luxstage'); ?></button>
      <a href="<?php echo esc_url(get_post_type_archive_link('catalog')); ?>"><?php esc_html_e('Reset', 'luxstage'); ?></a>
    </form>
  </section>

  <?php if ($catalog_query->have_posts()) : ?>
    <section class="fw-b2b-grid">
      <?php while ($catalog_query->have_posts()) : $catalog_query->the_post(); ?>
        <?php
        $pdf_url = function_exists('luxstage_get_catalog_pdf_url') ? luxstage_get_catalog_pdf_url(get_the_ID()) : '';
        $download_url = function_exists('luxstage_catalog_secure_download_url')
            ? luxstage_catalog_secure_download_url(get_the_ID())
            : $pdf_url;
        $cert_slugs = wp_get_post_terms(get_the_ID(), 'certification', ['fields' => 'slugs']);
        if (is_wp_error($cert_slugs)) {
            $cert_slugs = [];
        }
        ?>
        <article
          <?php post_class('fw-b2b-card'); ?>
          data-catalog-id="<?php echo esc_attr((string) get_the_ID()); ?>"
          data-catalog-certification-slugs="<?php echo esc_attr(implode(',', $cert_slugs)); ?>"
        >
          <h2><?php the_title(); ?></h2>
          <?php the_excerpt(); ?>
          <?php if ($download_url) : ?>
            <a href="<?php echo esc_url($download_url); ?>" data-download-link="1">
              <?php esc_html_e('Download PDF', 'luxstage'); ?>
            </a>
          <?php endif; ?>
        </article>
      <?php endwhile; ?>
    </section>
    <?php
    the_posts_pagination([
        'total' => $catalog_query->max_num_pages,
        'current' => max(1, (int) get_query_var('paged')),
    ]);
    wp_reset_postdata();
    ?>
  <?php else : ?>
    <p><?php esc_html_e('No catalogs published yet.', 'luxstage'); ?></p>
  <?php endif; ?>
</main>
<?php
get_footer();
