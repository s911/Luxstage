<?php
/**
 * Seed Luxstage demo products for local/staging verification.
 */

$products = [
    [
        'title' => 'LX-MH350 Pro Moving Head Beam',
        'sku' => 'LX-MH350-PRO',
        'category' => 'Moving Head',
        'scene' => ['Concert', 'Event Rental'],
        'source' => 'Bulb',
        'certification' => ['CE', 'RoHS'],
        'model' => 'X-Series 350',
        'light_source_type' => 'Bulb',
        'wattage' => '350W',
        'color_temperature' => '7800K',
        'light_life' => '2000 hours',
        'luminous_flux' => 18000,
        'beam_angle' => '2.5°',
        'cri' => 85,
        'channels' => '16CH / 20CH',
        'control_protocols' => ['DMX512', 'RDM'],
        'wireless_control' => 1,
        'weight' => 22,
        'dimensions' => '430x320x620mm',
        'ip_rating' => 'IP20',
        'voltage' => 'AC 100-240V, 50/60Hz',
        'max_power' => 480,
        'effect_features' => ['Color Wheel', 'Gobo Wheel', 'Prism', 'Frost'],
        'prism' => '8-facet + 16-facet rotating prism',
        'dimming_curves' => ['Linear', 'S-Curve'],
        'refresh_rate' => 'N/A',
        'accessories' => 'Power cable, DMX cable, omega bracket, safety cable, user manual',
        'optional_accessories' => 'Flight case, clamp, wireless DMX receiver',
        'certification_standards' => ['CE', 'RoHS'],
    ],
    [
        'title' => 'LX-PAR1815 Outdoor LED Par',
        'sku' => 'LX-PAR1815-IP65',
        'category' => 'LED Par',
        'scene' => ['Outdoor Festival', 'Event Rental'],
        'source' => 'LED',
        'certification' => ['CE', 'RoHS', 'FCC'],
        'model' => 'PAR 18x15W RGBWA',
        'light_source_type' => 'LED',
        'wattage' => '270W',
        'color_temperature' => 'RGBWA color mixing',
        'light_life' => '50000 hours',
        'luminous_flux' => 9200,
        'beam_angle' => '25°',
        'cri' => 90,
        'channels' => '5CH / 9CH',
        'control_protocols' => ['DMX512'],
        'wireless_control' => 0,
        'weight' => 8.6,
        'dimensions' => '280x260x310mm',
        'ip_rating' => 'IP65',
        'voltage' => 'AC 100-240V, 50/60Hz',
        'max_power' => 300,
        'effect_features' => ['Zoom'],
        'prism' => '',
        'dimming_curves' => ['Linear'],
        'refresh_rate' => '4000Hz',
        'accessories' => 'PowerCON cable, DMX waterproof cable, bracket, manual',
        'optional_accessories' => 'Flight case, rain cover',
        'certification_standards' => ['CE', 'RoHS', 'FCC'],
    ],
    [
        'title' => 'LX-STROBE1000 LED Strobe Bar',
        'sku' => 'LX-STROBE1000',
        'category' => 'Strobe',
        'scene' => ['Concert', 'Disco / Club'],
        'source' => 'LED',
        'certification' => ['CE', 'RoHS'],
        'model' => 'S-Series 1000',
        'light_source_type' => 'LED',
        'wattage' => '1000W',
        'color_temperature' => '6500K white + RGB backlight',
        'light_life' => '50000 hours',
        'luminous_flux' => 30000,
        'beam_angle' => '120°',
        'cri' => 80,
        'channels' => '12CH / 24CH',
        'control_protocols' => ['DMX512', 'RDM', 'Art-Net'],
        'wireless_control' => 1,
        'weight' => 11.5,
        'dimensions' => '1000x120x210mm',
        'ip_rating' => 'IP20',
        'voltage' => 'AC 100-240V, 50/60Hz',
        'max_power' => 1100,
        'effect_features' => ['Color Wheel'],
        'prism' => '',
        'dimming_curves' => ['Linear', 'S-Curve'],
        'refresh_rate' => '25000Hz',
        'accessories' => 'Power cable, signal cable, hanging bracket, manual',
        'optional_accessories' => 'Flight case, clamp',
        'certification_standards' => ['CE', 'RoHS'],
    ],
    [
        'title' => 'LX-FX200 RGBW Effect Light',
        'sku' => 'LX-FX200-RGBW',
        'category' => 'Effect Light',
        'scene' => ['Disco / Club', 'Theatre'],
        'source' => 'LED',
        'certification' => ['CE'],
        'model' => 'FX-Series 200',
        'light_source_type' => 'LED',
        'wattage' => '200W',
        'color_temperature' => 'RGBW color mixing',
        'light_life' => '50000 hours',
        'luminous_flux' => 7600,
        'beam_angle' => '8°-50°',
        'cri' => 88,
        'channels' => '14CH',
        'control_protocols' => ['DMX512'],
        'wireless_control' => 0,
        'weight' => 9.2,
        'dimensions' => '360x240x410mm',
        'ip_rating' => 'IP20',
        'voltage' => 'AC 100-240V, 50/60Hz',
        'max_power' => 260,
        'effect_features' => ['Color Wheel', 'Gobo Wheel', 'Zoom'],
        'prism' => '6-facet prism',
        'dimming_curves' => ['Linear', 'L-Curve'],
        'refresh_rate' => '8000Hz',
        'accessories' => 'Power cable, DMX cable, bracket, user manual',
        'optional_accessories' => 'Clamp, safety rope',
        'certification_standards' => ['CE'],
    ],
    [
        'title' => 'LX-FOLLOW1200 Follow Spot',
        'sku' => 'LX-FOLLOW1200',
        'category' => 'Follow Spot',
        'scene' => ['Theatre', 'Concert'],
        'source' => 'Bulb',
        'certification' => ['CE', 'UL'],
        'model' => 'Follow 1200',
        'light_source_type' => 'Bulb',
        'wattage' => '1200W',
        'color_temperature' => '5600K',
        'light_life' => '1500 hours',
        'luminous_flux' => 52000,
        'beam_angle' => '5°-12°',
        'cri' => 92,
        'channels' => 'Manual / 6CH',
        'control_protocols' => ['DMX512'],
        'wireless_control' => 0,
        'weight' => 38,
        'dimensions' => '980x420x520mm',
        'ip_rating' => 'IP20',
        'voltage' => 'AC 220V, 50/60Hz',
        'max_power' => 1350,
        'effect_features' => ['Color Wheel', 'Frost'],
        'prism' => '',
        'dimming_curves' => ['Linear'],
        'refresh_rate' => 'N/A',
        'accessories' => 'Power cable, iris, color frame, tripod, manual',
        'optional_accessories' => 'Flight case, spare lamp',
        'certification_standards' => ['CE', 'UL'],
    ],
    [
        'title' => 'LX-LASER30 RGB Animation Laser',
        'sku' => 'LX-LASER30-RGB',
        'category' => 'Laser Light',
        'scene' => ['Concert', 'Disco / Club'],
        'source' => 'Laser',
        'certification' => ['CE', 'FDA'],
        'model' => 'Laser 30 RGB',
        'light_source_type' => 'Laser',
        'wattage' => '30W',
        'color_temperature' => 'RGB laser source',
        'light_life' => '10000 hours',
        'luminous_flux' => 0,
        'beam_angle' => 'High precision scanner',
        'cri' => 0,
        'channels' => '18CH',
        'control_protocols' => ['DMX512', 'Art-Net'],
        'wireless_control' => 0,
        'weight' => 19,
        'dimensions' => '520x360x260mm',
        'ip_rating' => 'IP20',
        'voltage' => 'AC 100-240V, 50/60Hz',
        'max_power' => 500,
        'effect_features' => ['Gobo Wheel'],
        'prism' => '',
        'dimming_curves' => ['Linear'],
        'refresh_rate' => '30K scanner',
        'accessories' => 'Power cable, interlock, safety key, signal cable, manual',
        'optional_accessories' => 'Flight case, emergency stop switch',
        'certification_standards' => ['CE'],
    ],
    [
        'title' => 'LX-BEAM380 Hybrid Beam Light',
        'sku' => 'LX-BEAM380-HYB',
        'category' => 'Beam Light',
        'scene' => ['Concert', 'Outdoor Festival'],
        'source' => 'Bulb',
        'certification' => ['CE', 'RoHS', 'ETL'],
        'model' => 'Beam 380 Hybrid',
        'light_source_type' => 'Hybrid',
        'wattage' => '380W',
        'color_temperature' => '8000K',
        'light_life' => '2000 hours',
        'luminous_flux' => 22000,
        'beam_angle' => '1.8°-20°',
        'cri' => 86,
        'channels' => '20CH / 24CH',
        'control_protocols' => ['DMX512', 'RDM'],
        'wireless_control' => 1,
        'weight' => 25,
        'dimensions' => '450x350x680mm',
        'ip_rating' => 'IP20',
        'voltage' => 'AC 100-240V, 50/60Hz',
        'max_power' => 560,
        'effect_features' => ['Color Wheel', 'Gobo Wheel', 'Prism', 'Frost', 'Zoom'],
        'prism' => '8-facet + linear prism',
        'dimming_curves' => ['Linear', 'S-Curve'],
        'refresh_rate' => 'N/A',
        'accessories' => 'Power cable, DMX cable, omega bracket, safety cable, manual',
        'optional_accessories' => 'Flight case, clamp',
        'certification_standards' => ['CE', 'RoHS', 'ETL'],
    ],
    [
        'title' => 'LX-WASH760 LED Moving Wash',
        'sku' => 'LX-WASH760',
        'category' => 'Moving Head',
        'scene' => ['TV Studio', 'Theatre'],
        'source' => 'LED',
        'certification' => ['CE', 'RoHS'],
        'model' => 'Wash 760',
        'light_source_type' => 'LED',
        'wattage' => '760W',
        'color_temperature' => '2700K-8000K',
        'light_life' => '50000 hours',
        'luminous_flux' => 26000,
        'beam_angle' => '6°-55°',
        'cri' => 95,
        'channels' => '24CH / 36CH',
        'control_protocols' => ['DMX512', 'RDM', 'sACN'],
        'wireless_control' => 1,
        'weight' => 24.5,
        'dimensions' => '480x330x610mm',
        'ip_rating' => 'IP20',
        'voltage' => 'AC 100-240V, 50/60Hz',
        'max_power' => 820,
        'effect_features' => ['Zoom', 'Frost'],
        'prism' => '',
        'dimming_curves' => ['Linear', 'S-Curve', 'L-Curve'],
        'refresh_rate' => '25000Hz',
        'accessories' => 'Power cable, signal cable, omega bracket, manual',
        'optional_accessories' => 'Flight case, wireless DMX receiver',
        'certification_standards' => ['CE', 'RoHS'],
    ],
    [
        'title' => 'LX-PAR1210 Compact LED Par',
        'sku' => 'LX-PAR1210',
        'category' => 'LED Par',
        'scene' => ['Theatre', 'Event Rental'],
        'source' => 'LED',
        'certification' => ['CE', 'RoHS'],
        'model' => 'PAR 12x10W RGBW',
        'light_source_type' => 'LED',
        'wattage' => '120W',
        'color_temperature' => 'RGBW color mixing',
        'light_life' => '50000 hours',
        'luminous_flux' => 4800,
        'beam_angle' => '30°',
        'cri' => 89,
        'channels' => '4CH / 8CH',
        'control_protocols' => ['DMX512'],
        'wireless_control' => 0,
        'weight' => 3.8,
        'dimensions' => '220x180x260mm',
        'ip_rating' => 'IP20',
        'voltage' => 'AC 100-240V, 50/60Hz',
        'max_power' => 150,
        'effect_features' => [],
        'prism' => '',
        'dimming_curves' => ['Linear'],
        'refresh_rate' => '4000Hz',
        'accessories' => 'Power cable, bracket, manual',
        'optional_accessories' => 'Clamp, carry bag',
        'certification_standards' => ['CE', 'RoHS'],
    ],
    [
        'title' => 'LX-IP67 Beam Wash Outdoor',
        'sku' => 'LX-IP67-BW',
        'category' => 'Beam Light',
        'scene' => ['Outdoor Festival', 'Event Rental'],
        'source' => 'LED',
        'certification' => ['CE', 'RoHS', 'FCC'],
        'model' => 'Outdoor Beam Wash IP67',
        'light_source_type' => 'LED',
        'wattage' => '480W',
        'color_temperature' => 'RGBL color mixing',
        'light_life' => '50000 hours',
        'luminous_flux' => 18500,
        'beam_angle' => '4°-42° motorized zoom',
        'cri' => 90,
        'channels' => '18CH / 26CH',
        'control_protocols' => ['DMX512', 'RDM', 'Art-Net'],
        'wireless_control' => 1,
        'weight' => 28,
        'dimensions' => '510x360x650mm',
        'ip_rating' => 'IP67',
        'voltage' => 'AC 100-240V, 50/60Hz',
        'max_power' => 540,
        'effect_features' => ['Zoom', 'Frost'],
        'prism' => '',
        'dimming_curves' => ['Linear', 'S-Curve'],
        'refresh_rate' => '25000Hz',
        'accessories' => 'Waterproof power cable, waterproof DMX cable, bracket, manual',
        'optional_accessories' => 'Flight case, outdoor clamp',
        'certification_standards' => ['CE', 'RoHS', 'FCC'],
    ],
];

function luxstage_demo_set_field(int $post_id, string $field_name, mixed $value): void
{
    if (function_exists('update_field')) {
        update_field($field_name, $value, $post_id);
        return;
    }

    update_post_meta($post_id, $field_name, $value);
}

function luxstage_demo_ensure_term(string $taxonomy, string $name): int
{
    $term = term_exists($name, $taxonomy);

    if (!$term) {
        $term = wp_insert_term($name, $taxonomy);
    }

    if (is_wp_error($term)) {
        WP_CLI::error($term->get_error_message());
    }

    return (int) (is_array($term) ? $term['term_id'] : $term);
}

$created = 0;
$updated = 0;

foreach ($products as $product) {
    $existing = get_posts([
        'post_type' => 'stage_lighting',
        'post_status' => 'any',
        'meta_key' => 'sku',
        'meta_value' => $product['sku'],
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);

    $post_data = [
        'post_type' => 'stage_lighting',
        'post_status' => 'publish',
        'post_title' => $product['title'],
        'post_excerpt' => sprintf(
            '%s stage lighting fixture with %s, %s, and %s control.',
            $product['category'],
            $product['wattage'],
            $product['ip_rating'],
            implode(' / ', $product['control_protocols'])
        ),
        'post_content' => sprintf(
            "%s is a Luxstage B2B demo product for validating product archives, technical parameters, taxonomy filters, RFQ flows, and SEO-ready product content.\n\nRecommended applications: %s.\n\nStandard accessories: %s.",
            $product['title'],
            implode(', ', $product['scene']),
            $product['accessories']
        ),
    ];

    if ($existing) {
        $post_id = (int) $existing[0];
        $post_data['ID'] = $post_id;
        wp_update_post($post_data, true);
        $updated++;
    } else {
        $post_id = wp_insert_post($post_data, true);
        $created++;
    }

    if (is_wp_error($post_id)) {
        WP_CLI::error($post_id->get_error_message());
    }

    luxstage_demo_set_field($post_id, 'sku', $product['sku']);
    luxstage_demo_set_field($post_id, 'model', $product['model']);
    luxstage_demo_set_field($post_id, 'light_source_type', $product['light_source_type']);
    luxstage_demo_set_field($post_id, 'wattage', $product['wattage']);
    luxstage_demo_set_field($post_id, 'color_temperature', $product['color_temperature']);
    luxstage_demo_set_field($post_id, 'light_life', $product['light_life']);
    luxstage_demo_set_field($post_id, 'luminous_flux', $product['luminous_flux']);
    luxstage_demo_set_field($post_id, 'beam_angle', $product['beam_angle']);
    luxstage_demo_set_field($post_id, 'cri', $product['cri']);
    luxstage_demo_set_field($post_id, 'channels', $product['channels']);
    luxstage_demo_set_field($post_id, 'control_protocols', $product['control_protocols']);
    luxstage_demo_set_field($post_id, 'wireless_control', $product['wireless_control']);
    luxstage_demo_set_field($post_id, 'weight', $product['weight']);
    luxstage_demo_set_field($post_id, 'dimensions', $product['dimensions']);
    luxstage_demo_set_field($post_id, 'ip_rating', $product['ip_rating']);
    luxstage_demo_set_field($post_id, 'voltage', $product['voltage']);
    luxstage_demo_set_field($post_id, 'max_power', $product['max_power']);
    luxstage_demo_set_field($post_id, 'effect_features', $product['effect_features']);
    luxstage_demo_set_field($post_id, 'prism', $product['prism']);
    luxstage_demo_set_field($post_id, 'dimming_curves', $product['dimming_curves']);
    luxstage_demo_set_field($post_id, 'refresh_rate', $product['refresh_rate']);
    luxstage_demo_set_field($post_id, 'accessories', $product['accessories']);
    luxstage_demo_set_field($post_id, 'optional_accessories', $product['optional_accessories']);
    luxstage_demo_set_field($post_id, 'certification_standards', $product['certification_standards']);

    wp_set_object_terms($post_id, [luxstage_demo_ensure_term('product_category', $product['category'])], 'product_category');
    wp_set_object_terms($post_id, array_map(static fn($term) => luxstage_demo_ensure_term('application_scene', $term), $product['scene']), 'application_scene');
    wp_set_object_terms($post_id, [luxstage_demo_ensure_term('light_source', $product['source'])], 'light_source');
    wp_set_object_terms($post_id, array_map(static fn($term) => luxstage_demo_ensure_term('certification', $term), $product['certification']), 'certification');
}

WP_CLI::success(sprintf('Seeded Luxstage demo products. Created: %d, Updated: %d.', $created, $updated));
