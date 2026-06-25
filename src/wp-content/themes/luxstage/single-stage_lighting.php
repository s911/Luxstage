<?php
/**
 * Single template for Stage Lighting CPT.
 */

get_header();
?>
<main class="fw-b2b-container lux-product-detail">
  <?php while (have_posts()) : the_post(); ?>
    <?php
    $post_id = get_the_ID();
    $categories = get_the_terms($post_id, 'product_category');
    $sku = (string) luxstage_field('sku');
    $light_source = (string) luxstage_field('light_source_type');
    $wattage = (string) luxstage_field('wattage');
    $ip_rating = (string) luxstage_field('ip_rating');
    $channels = (string) luxstage_field('channels');
    $video_url = (string) luxstage_field('video_url');
    $gallery_images = function_exists('luxstage_gallery_images')
        ? luxstage_gallery_images($post_id)
        : luxstage_field('gallery_images');

    $media_items = [];
    if (has_post_thumbnail()) {
        $thumbnail_id = (int) get_post_thumbnail_id($post_id);
        $media_items[] = [
            'full' => (string) wp_get_attachment_image_url($thumbnail_id, 'large'),
            'thumb' => (string) wp_get_attachment_image_url($thumbnail_id, 'thumbnail'),
            'alt' => get_the_title(),
        ];
    }

    if (is_array($gallery_images)) {
        foreach ($gallery_images as $image) {
            $full = '';
            $thumb = '';
            $alt = get_the_title();

            if (is_array($image)) {
                $full = (string) ($image['sizes']['large'] ?? $image['url'] ?? '');
                $thumb = (string) ($image['sizes']['thumbnail'] ?? $image['url'] ?? '');
                $alt = (string) ($image['alt'] ?? get_the_title());
            } elseif (is_numeric($image)) {
                $full = (string) wp_get_attachment_image_url((int) $image, 'large');
                $thumb = (string) wp_get_attachment_image_url((int) $image, 'thumbnail');
                $alt = (string) get_post_meta((int) $image, '_wp_attachment_image_alt', true) ?: get_the_title();
            }

            if ($full !== '') {
                $media_items[] = [
                    'full' => $full,
                    'thumb' => $thumb !== '' ? $thumb : $full,
                    'alt' => $alt,
                ];
            }
        }
    }

    $media_items = array_values(array_unique($media_items, SORT_REGULAR));
    $main_media = $media_items[0] ?? null;

    $overview_tags = array_filter([
        $sku !== '' ? ['label' => __('SKU', 'luxstage'), 'value' => $sku] : null,
        $light_source !== '' ? ['label' => __('Light Source', 'luxstage'), 'value' => $light_source] : null,
        $wattage !== '' ? ['label' => __('Power', 'luxstage'), 'value' => $wattage] : null,
        $ip_rating !== '' ? ['label' => __('IP Rating', 'luxstage'), 'value' => $ip_rating] : null,
    ]);

    $format_spec_value = static function (mixed $value): string {
        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map('strval', $value)));
        } elseif (is_bool($value)) {
            $value = $value ? __('Yes', 'luxstage') : __('No', 'luxstage');
        }

        return trim((string) $value);
    };

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
    <article <?php post_class('lux-product-detail__article'); ?>>
      <nav class="lux-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'luxstage'); ?>">
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'luxstage'); ?></a>
        <span>&gt;</span>
        <a href="<?php echo esc_url(home_url('/products/')); ?>"><?php esc_html_e('Products', 'luxstage'); ?></a>
        <?php if (!is_wp_error($categories) && $categories) : ?>
          <span>&gt;</span>
          <a href="<?php echo esc_url(add_query_arg(['product_category' => $categories[0]->slug], home_url('/products/'))); ?>"><?php echo esc_html($categories[0]->name); ?></a>
        <?php endif; ?>
        <span>&gt;</span>
        <span><?php the_title(); ?></span>
      </nav>

      <section class="lux-product-hero">
        <div class="lux-product-media" data-lux-product-gallery>
          <div class="lux-product-media__main">
            <?php if ($main_media) : ?>
              <img
                data-lux-gallery-main
                src="<?php echo esc_url($main_media['full']); ?>"
                alt="<?php echo esc_attr($main_media['alt']); ?>"
                loading="eager"
              />
            <?php else : ?>
              <div class="lux-product-media__placeholder"><?php esc_html_e('Luxstage', 'luxstage'); ?></div>
            <?php endif; ?>
          </div>
          <?php if (count($media_items) > 1 || $video_url !== '') : ?>
            <div class="lux-product-thumbs" aria-label="<?php esc_attr_e('Product media gallery', 'luxstage'); ?>">
              <?php foreach ($media_items as $index => $media) : ?>
                <button
                  class="lux-product-thumb<?php echo $index === 0 ? ' is-active' : ''; ?>"
                  type="button"
                  data-lux-gallery-thumb
                  data-full="<?php echo esc_url($media['full']); ?>"
                  data-alt="<?php echo esc_attr($media['alt']); ?>"
                  aria-label="<?php echo esc_attr(sprintf(__('View media %d', 'luxstage'), $index + 1)); ?>"
                >
                  <img src="<?php echo esc_url($media['thumb']); ?>" alt="<?php echo esc_attr($media['alt']); ?>" loading="lazy" />
                </button>
              <?php endforeach; ?>
              <?php if ($video_url !== '') : ?>
                <a class="lux-product-thumb lux-product-thumb--video" href="<?php echo esc_url($video_url); ?>" target="_blank" rel="noopener">
                  <?php esc_html_e('Video', 'luxstage'); ?>
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="lux-product-summary">
          <?php if (!is_wp_error($categories) && $categories) : ?>
            <p class="lux-eyebrow"><?php echo esc_html($categories[0]->name); ?></p>
          <?php else : ?>
            <p class="lux-eyebrow"><?php esc_html_e('Stage Lighting', 'luxstage'); ?></p>
          <?php endif; ?>
          <h1><?php the_title(); ?></h1>
          <?php if (has_excerpt()) : ?>
            <p class="lux-product-summary__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
          <?php endif; ?>
          <?php if ($overview_tags) : ?>
            <div class="lux-product-tags">
              <?php foreach ($overview_tags as $tag) : ?>
                <span class="lux-product-tag">
                  <small><?php echo esc_html($tag['label']); ?></small>
                  <strong><?php echo esc_html($tag['value']); ?></strong>
                </span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="lux-product-summary__actions">
            <a class="lux-button lux-button--primary lux-button--large" href="<?php echo esc_url(add_query_arg(['product_sku' => $sku], home_url('/contact/'))); ?>">
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
              <a class="lux-button lux-button--secondary lux-button--large" href="<?php echo esc_url($catalog_url); ?>">
                <?php esc_html_e('Download PDF', 'luxstage'); ?>
              </a>
            <?php endif; ?>
          </div>
          <div class="lux-product-summary__meta">
            <span><?php esc_html_e('OEM / ODM support', 'luxstage'); ?></span>
            <span><?php esc_html_e('Export-ready documents', 'luxstage'); ?></span>
            <span><?php esc_html_e('Factory direct response', 'luxstage'); ?></span>
          </div>
        </div>
      </section>

      <section class="lux-product-specs">
        <div class="lux-section__header">
          <div>
            <p class="lux-eyebrow"><?php esc_html_e('Technical Data', 'luxstage'); ?></p>
            <h2><?php esc_html_e('Specifications', 'luxstage'); ?></h2>
          </div>
        </div>
        <div class="lux-spec-table-wrap">
          <table class="lux-spec-table">
            <tbody>
              <?php foreach ($spec_sections as $section_title => $fields) : ?>
                <?php
                $section_rows = [];
                foreach ($fields as $field_name => $field_label) {
                    $value = $format_spec_value(luxstage_field($field_name));
                    if ($value !== '') {
                        $section_rows[] = [
                            'label' => $field_label,
                            'value' => $value,
                        ];
                    }
                }
                ?>
                <?php if ($section_rows) : ?>
                  <tr class="lux-spec-table__section">
                    <th colspan="2"><?php echo esc_html($section_title); ?></th>
                  </tr>
                  <?php foreach ($section_rows as $row) : ?>
                    <tr>
                      <th scope="row"><?php echo esc_html($row['label']); ?></th>
                      <td><?php echo esc_html($row['value']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <?php if (trim(get_the_content()) !== '') : ?>
        <section class="lux-product-content">
          <?php the_content(); ?>
        </section>
      <?php endif; ?>

      <?php
      $related = new WP_Query([
          'post_type' => 'stage_lighting',
          'post__not_in' => [$post_id],
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
        <section class="lux-related-products">
          <div class="lux-section__header">
            <div>
              <p class="lux-eyebrow"><?php esc_html_e('Recommended', 'luxstage'); ?></p>
              <h2><?php esc_html_e('Related Products', 'luxstage'); ?></h2>
            </div>
            <a href="<?php echo esc_url(home_url('/products/')); ?>"><?php esc_html_e('All products', 'luxstage'); ?></a>
          </div>
          <div class="fw-b2b-grid">
            <?php while ($related->have_posts()) : $related->the_post(); ?>
              <article <?php post_class('lux-card lux-related-card'); ?>>
                <a href="<?php the_permalink(); ?>">
                  <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('medium_large'); ?>
                  <?php else : ?>
                    <div class="lux-card__placeholder"><?php esc_html_e('Luxstage', 'luxstage'); ?></div>
                  <?php endif; ?>
                  <h3><?php the_title(); ?></h3>
                </a>
                <dl class="lux-spec-strip">
                  <div><dt><?php esc_html_e('Power', 'luxstage'); ?></dt><dd><?php echo esc_html((string) luxstage_field('wattage')); ?></dd></div>
                  <div><dt><?php esc_html_e('Source', 'luxstage'); ?></dt><dd><?php echo esc_html((string) luxstage_field('light_source_type')); ?></dd></div>
                  <div><dt><?php esc_html_e('DMX', 'luxstage'); ?></dt><dd><?php echo esc_html((string) luxstage_field('channels')); ?></dd></div>
                </dl>
                <a class="lux-text-link" href="<?php the_permalink(); ?>"><?php esc_html_e('View details', 'luxstage'); ?></a>
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
