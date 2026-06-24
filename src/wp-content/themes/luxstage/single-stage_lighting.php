<?php
/**
 * Single template for Stage Lighting CPT.
 */

get_header();
?>
<main class="fw-b2b-container">
  <?php while (have_posts()) : the_post(); ?>
    <article <?php post_class(); ?>>
      <nav class="lux-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'luxstage'); ?>">
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'luxstage'); ?></a>
        <span>&gt;</span>
        <a href="<?php echo esc_url(home_url('/products/')); ?>"><?php esc_html_e('Products', 'luxstage'); ?></a>
        <?php $categories = get_the_terms(get_the_ID(), 'product_category'); ?>
        <?php if (!is_wp_error($categories) && $categories) : ?>
          <span>&gt;</span>
          <a href="<?php echo esc_url(get_term_link($categories[0])); ?>"><?php echo esc_html($categories[0]->name); ?></a>
        <?php endif; ?>
        <span>&gt;</span>
        <span><?php the_title(); ?></span>
      </nav>

      <h1><?php the_title(); ?></h1>
      <?php if (has_post_thumbnail()) : ?>
        <div><?php the_post_thumbnail('large', ['loading' => 'eager', 'alt' => get_the_title()]); ?></div>
      <?php endif; ?>

      <section class="lux-media-gallery">
        <h2><?php esc_html_e('Media Gallery', 'luxstage'); ?></h2>
        <?php
        $video_url = (string) luxstage_field('video_url');
        $gallery_images = luxstage_field('gallery_images');
        $has_gallery_images = is_array($gallery_images) && !empty($gallery_images);
        ?>
        <?php if ($video_url !== '') : ?>
          <p><a href="<?php echo esc_url($video_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Watch Product Video', 'luxstage'); ?></a></p>
        <?php endif; ?>
        <?php if ($has_gallery_images) : ?>
          <div class="fw-b2b-grid">
            <?php foreach ($gallery_images as $image) : ?>
              <?php
              $img_url = is_array($image) && isset($image['url']) ? (string) $image['url'] : '';
              $img_alt = is_array($image) && isset($image['alt']) ? (string) $image['alt'] : get_the_title();
              if ($img_url === '') {
                  continue;
              }
              ?>
              <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>" loading="lazy" />
            <?php endforeach; ?>
          </div>
        <?php elseif (!has_post_thumbnail()) : ?>
          <p><?php esc_html_e('Product media is available on request.', 'luxstage'); ?></p>
        <?php endif; ?>
      </section>

      <section>
        <h2><?php esc_html_e('Specifications', 'luxstage'); ?></h2>
        <?php
        $spec_sections = [
            __('Basic Information', 'luxstage') => [
                'sku' => __('SKU', 'luxstage'),
                'model' => __('Model', 'luxstage'),
                'light_source_type' => __('Light Source Type', 'luxstage'),
                'wattage' => __('Wattage', 'luxstage'),
                'color_temperature' => __('Color Temperature', 'luxstage'),
                'light_life' => __('Light Source Lifetime', 'luxstage'),
            ],
            __('Optical Parameters', 'luxstage') => [
                'luminous_flux' => __('Luminous Flux', 'luxstage'),
                'beam_angle' => __('Beam Angle', 'luxstage'),
                'cri' => __('CRI', 'luxstage'),
            ],
            __('Control Protocols', 'luxstage') => [
                'channels' => __('DMX Channels', 'luxstage'),
                'control_protocols' => __('Control Protocols', 'luxstage'),
                'wireless_control' => __('Wireless Control', 'luxstage'),
            ],
            __('Physical and Electrical', 'luxstage') => [
                'weight' => __('Weight', 'luxstage'),
                'dimensions' => __('Dimensions', 'luxstage'),
                'ip_rating' => __('IP Rating', 'luxstage'),
                'voltage' => __('Voltage', 'luxstage'),
                'max_power' => __('Max Power Consumption', 'luxstage'),
            ],
            __('Effects and Accessories', 'luxstage') => [
                'effect_features' => __('Effect Features', 'luxstage'),
                'prism' => __('Prism', 'luxstage'),
                'dimming_curves' => __('Dimming Curves', 'luxstage'),
                'refresh_rate' => __('Refresh Rate', 'luxstage'),
                'accessories' => __('Standard Accessories', 'luxstage'),
                'optional_accessories' => __('Optional Accessories', 'luxstage'),
                'certification_standards' => __('Certification Standards', 'luxstage'),
            ],
        ];
        ?>
        <?php foreach ($spec_sections as $section_title => $fields) : ?>
          <h3><?php echo esc_html($section_title); ?></h3>
          <dl>
            <?php foreach ($fields as $field_name => $field_label) : ?>
              <?php
              $value = luxstage_field($field_name);
              if (is_array($value)) {
                  $value = implode(', ', array_filter(array_map('strval', $value)));
              }
              if ($value === '' || $value === null) {
                  continue;
              }
              ?>
              <dt><?php echo esc_html($field_label); ?></dt>
              <dd><?php echo esc_html((string) $value); ?></dd>
            <?php endforeach; ?>
          </dl>
        <?php endforeach; ?>
      </section>

      <section>
        <?php the_content(); ?>
      </section>

      <section class="lux-product-actions">
        <?php $sku = (string) luxstage_field('sku'); ?>
        <a class="lux-button lux-button--primary" href="<?php echo esc_url(add_query_arg(['product_sku' => $sku], home_url('/contact/'))); ?>">
          <?php esc_html_e('Send Inquiry', 'luxstage'); ?>
        </a>
        <?php
        $catalog_pdf = luxstage_field('catalog_pdf');
        $catalog_url = '';
        if (is_array($catalog_pdf) && !empty($catalog_pdf['url'])) {
            $catalog_url = (string) $catalog_pdf['url'];
        } elseif (is_string($catalog_pdf) && $catalog_pdf !== '') {
            $catalog_url = $catalog_pdf;
        } elseif (is_numeric($catalog_pdf)) {
            $catalog_url = (string) wp_get_attachment_url((int) $catalog_pdf);
        }
        ?>
        <?php if ($catalog_url !== '') : ?>
          <a class="lux-button lux-button--secondary" href="<?php echo esc_url($catalog_url); ?>">
            <?php esc_html_e('Download PDF', 'luxstage'); ?>
          </a>
        <?php endif; ?>
      </section>

      <?php
      $related = new WP_Query([
          'post_type' => 'stage_lighting',
          'post__not_in' => [get_the_ID()],
          'posts_per_page' => 3,
          'tax_query' => (!empty($categories) && !is_wp_error($categories)) ? [
              [
                  'taxonomy' => 'product_category',
                  'field' => 'term_id',
                  'terms' => [(int) $categories[0]->term_id],
              ],
          ] : [],
      ]);
      ?>
      <?php if ($related->have_posts()) : ?>
        <section>
          <h2><?php esc_html_e('Related Products', 'luxstage'); ?></h2>
          <div class="fw-b2b-grid">
            <?php while ($related->have_posts()) : $related->the_post(); ?>
              <article class="lux-card">
                <a href="<?php the_permalink(); ?>">
                  <h3><?php the_title(); ?></h3>
                  <p><?php echo esc_html((string) luxstage_field('wattage')); ?></p>
                </a>
              </article>
            <?php endwhile; ?>
          </div>
        </section>
        <?php wp_reset_postdata(); ?>
      <?php endif; ?>
    </article>
  <?php endwhile; ?>
</main>
<?php
get_footer();
