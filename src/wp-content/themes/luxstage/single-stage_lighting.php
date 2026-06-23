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
    </article>
  <?php endwhile; ?>
</main>
<?php
get_footer();
