<?php
/**
 * Archive template for Stage Lighting CPT.
 */

get_header();
?>
<main class="fw-b2b-container">
  <?php
  $product_categories = get_terms([
      'taxonomy' => 'product_category',
      'hide_empty' => false,
  ]);

  $light_sources = get_terms([
      'taxonomy' => 'light_source',
      'hide_empty' => false,
  ]);

  $certifications = get_terms([
      'taxonomy' => 'certification',
      'hide_empty' => false,
  ]);

  $current_category = sanitize_title((string) ($_GET['product_category'] ?? ''));
  $current_light_source = sanitize_title((string) ($_GET['light_source'] ?? ''));
  $current_certification = sanitize_title((string) ($_GET['certification'] ?? ''));
  $current_keyword = sanitize_text_field((string) ($_GET['keyword'] ?? ''));
  $current_sort = sanitize_key((string) ($_GET['sort'] ?? 'date_desc'));

  $archive_title = post_type_archive_title('', false);
  if ($current_category !== '') {
      $term = get_term_by('slug', $current_category, 'product_category');
      if ($term instanceof WP_Term) {
          $archive_title = $term->name;
      }
  } elseif ($current_light_source !== '') {
      $term = get_term_by('slug', $current_light_source, 'light_source');
      if ($term instanceof WP_Term) {
          $archive_title = $term->name;
      }
  } elseif ($current_certification !== '') {
      $term = get_term_by('slug', $current_certification, 'certification');
      if ($term instanceof WP_Term) {
          $archive_title = $term->name;
      }
  } elseif ($current_keyword !== '') {
      $archive_title = sprintf(__('Search: %s', 'luxstage'), $current_keyword);
  }

  $tax_query = [];
  if ($current_category !== '') {
      $tax_query[] = [
          'taxonomy' => 'product_category',
          'field' => 'slug',
          'terms' => [$current_category],
      ];
  }
  if ($current_light_source !== '') {
      $tax_query[] = [
          'taxonomy' => 'light_source',
          'field' => 'slug',
          'terms' => [$current_light_source],
      ];
  }
  if ($current_certification !== '') {
      $tax_query[] = [
          'taxonomy' => 'certification',
          'field' => 'slug',
          'terms' => [$current_certification],
      ];
  }

  if (count($tax_query) > 1) {
      $tax_query['relation'] = 'AND';
  }

  $query_args = [
      'post_type' => 'stage_lighting',
      'post_status' => 'publish',
      'posts_per_page' => (int) get_option('posts_per_page'),
      'paged' => max(1, (int) get_query_var('paged')),
      's' => $current_keyword,
      'ignore_sticky_posts' => true,
  ];

  if ($tax_query) {
      $query_args['tax_query'] = $tax_query;
  }

  switch ($current_sort) {
      case 'date_asc':
          $query_args['orderby'] = 'date';
          $query_args['order'] = 'ASC';
          break;
      case 'title_asc':
          $query_args['orderby'] = 'title';
          $query_args['order'] = 'ASC';
          break;
      case 'title_desc':
          $query_args['orderby'] = 'title';
          $query_args['order'] = 'DESC';
          break;
      case 'date_desc':
      default:
          $query_args['orderby'] = 'date';
          $query_args['order'] = 'DESC';
          break;
  }

  $products_query = new WP_Query($query_args);
  ?>
  <header>
    <h1><?php echo esc_html($archive_title); ?></h1>
  </header>

  <section class="lux-filter-panel">
    <form method="get" action="<?php echo esc_url(get_post_type_archive_link('stage_lighting')); ?>">
      <label for="filter-product-category"><?php esc_html_e('Product Category', 'luxstage'); ?></label>
      <select id="filter-product-category" name="product_category">
        <option value=""><?php esc_html_e('All Categories', 'luxstage'); ?></option>
        <?php if (!is_wp_error($product_categories) && $product_categories) : ?>
          <?php foreach ($product_categories as $category) : ?>
            <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($current_category, $category->slug); ?>>
              <?php echo esc_html($category->name); ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>

      <label for="filter-light-source"><?php esc_html_e('Light Source', 'luxstage'); ?></label>
      <select id="filter-light-source" name="light_source">
        <option value=""><?php esc_html_e('All Types', 'luxstage'); ?></option>
        <?php if (!is_wp_error($light_sources) && $light_sources) : ?>
          <?php foreach ($light_sources as $light_source) : ?>
            <option value="<?php echo esc_attr($light_source->slug); ?>" <?php selected($current_light_source, $light_source->slug); ?>>
              <?php echo esc_html($light_source->name); ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>

      <label for="filter-certification"><?php esc_html_e('Certification', 'luxstage'); ?></label>
      <select id="filter-certification" name="certification">
        <option value=""><?php esc_html_e('All Certifications', 'luxstage'); ?></option>
        <?php if (!is_wp_error($certifications) && $certifications) : ?>
          <?php foreach ($certifications as $certification) : ?>
            <option value="<?php echo esc_attr($certification->slug); ?>" <?php selected($current_certification, $certification->slug); ?>>
              <?php echo esc_html($certification->name); ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>

      <label for="filter-keyword"><?php esc_html_e('Keyword', 'luxstage'); ?></label>
      <input id="filter-keyword" type="text" name="keyword" value="<?php echo esc_attr($current_keyword); ?>" placeholder="<?php esc_attr_e('Search model or SKU', 'luxstage'); ?>" />

      <label for="filter-sort"><?php esc_html_e('Sort By', 'luxstage'); ?></label>
      <select id="filter-sort" name="sort">
        <option value="date_desc" <?php selected($current_sort, 'date_desc'); ?>><?php esc_html_e('Newest', 'luxstage'); ?></option>
        <option value="date_asc" <?php selected($current_sort, 'date_asc'); ?>><?php esc_html_e('Oldest', 'luxstage'); ?></option>
        <option value="title_asc" <?php selected($current_sort, 'title_asc'); ?>><?php esc_html_e('Name A-Z', 'luxstage'); ?></option>
        <option value="title_desc" <?php selected($current_sort, 'title_desc'); ?>><?php esc_html_e('Name Z-A', 'luxstage'); ?></option>
      </select>

      <button type="submit"><?php esc_html_e('Apply Filters', 'luxstage'); ?></button>
      <a href="<?php echo esc_url(get_post_type_archive_link('stage_lighting')); ?>"><?php esc_html_e('Reset', 'luxstage'); ?></a>
    </form>
  </section>

  <?php if ($products_query->have_posts()) : ?>
    <section class="fw-b2b-grid">
      <?php while ($products_query->have_posts()) : $products_query->the_post(); ?>
        <?php
        $category_slugs = wp_get_post_terms(get_the_ID(), 'product_category', ['fields' => 'slugs']);
        if (is_wp_error($category_slugs)) {
            $category_slugs = [];
        }
        ?>
        <article
          <?php post_class('fw-b2b-card'); ?>
          data-product-title="<?php echo esc_attr(get_the_title()); ?>"
          data-product-category-slugs="<?php echo esc_attr(implode(',', $category_slugs)); ?>"
        >
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
    <?php
    the_posts_pagination([
        'total' => $products_query->max_num_pages,
        'current' => max(1, (int) get_query_var('paged')),
    ]);
    wp_reset_postdata();
    ?>
  <?php else : ?>
    <p><?php esc_html_e('No stage lighting products published yet.', 'luxstage'); ?></p>
  <?php endif; ?>
</main>
<?php
get_footer();
