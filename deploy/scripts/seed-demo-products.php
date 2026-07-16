<?php
/**
 * Seed Luxstage demo data for local/staging verification.
 * Creates 3 records for each content feature: products, catalogs, applications, inquiries.
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
];

$catalogs = [
    [
        'title' => 'Luxstage Catalog 01',
        'slug' => 'luxstage-catalog-01',
        'version' => '2026.Q1',
        'certification' => 'CE',
        'excerpt' => 'Full-line stage lighting catalog for B2B buyers.',
    ],
    [
        'title' => 'Luxstage Catalog 02',
        'slug' => 'luxstage-catalog-02',
        'version' => '2026.Q1-IP',
        'certification' => 'RoHS',
        'excerpt' => 'Outdoor IP65/IP67 fixture catalog for rental and festival projects.',
    ],
    [
        'title' => 'Luxstage Catalog 03',
        'slug' => 'luxstage-catalog-03',
        'version' => '2026.Laser',
        'certification' => 'UL',
        'excerpt' => 'Laser and effect lighting catalog with certification matrix.',
    ],
];

$applications = [
    [
        'title' => 'Luxstage Application Case 01',
        'slug' => 'luxstage-application-01',
        'scene' => 'Concert',
        'content' => 'Arena concert install using LX-MH350-PRO moving heads for aerial beam looks and mid-air prism effects.',
    ],
    [
        'title' => 'Luxstage Application Case 02',
        'slug' => 'luxstage-application-02',
        'scene' => 'Outdoor Festival',
        'content' => 'Outdoor festival stage wash with LX-PAR1815-IP65 fixtures covering front truss and side towers in IP65 conditions.',
    ],
    [
        'title' => 'Luxstage Application Case 03',
        'slug' => 'luxstage-application-03',
        'scene' => 'Disco / Club',
        'content' => 'Nightclub ceiling grid featuring LX-LASER30-RGB animation laser synchronized with DMX/Art-Net show control.',
    ],
];

$inquiries = [
    [
        'title' => 'Demo Inquiry Fixture 01',
        'slug' => 'demo-inquiry-fixture-01',
        'content' => "Name: Alex Chen\nEmail: alex.chen@example.com\nCompany: Starlight Events\nSKU: LX-MH350-PRO\nMessage: Need 12 units quote with flight cases for Q3 tour.",
        'mail_status' => 'sent',
        'mail_to' => 'sales@luxstage.local',
    ],
    [
        'title' => 'Demo Inquiry Fixture 02',
        'slug' => 'demo-inquiry-fixture-02',
        'content' => "Name: Maria Lopez\nEmail: maria.lopez@example.com\nCompany: Pacific Rental Co.\nSKU: LX-PAR1815-IP65\nMessage: Request outdoor LED Par pricing and lead time for festival season.",
        'mail_status' => 'sent',
        'mail_to' => 'sales@luxstage.local',
    ],
    [
        'title' => 'Demo Inquiry Fixture 03',
        'slug' => 'demo-inquiry-fixture-03',
        'content' => "Name: Wei Zhang\nEmail: wei.zhang@example.com\nCompany: Neon Club Group\nSKU: LX-LASER30-RGB\nMessage: Looking for laser package with safety interlock accessories.",
        'mail_status' => 'failed',
        'mail_to' => 'sales@luxstage.local',
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

function luxstage_demo_upsert_by_slug(string $post_type, string $slug, array $post_data): int
{
    $existing = get_posts([
        'post_type' => $post_type,
        'name' => $slug,
        'posts_per_page' => 1,
        'post_status' => 'any',
    ]);

    if ($existing) {
        $post_id = (int) $existing[0]->ID;
        $post_data['ID'] = $post_id;
        $result = wp_update_post($post_data, true);
    } else {
        $post_data['post_type'] = $post_type;
        $post_data['post_name'] = $slug;
        $result = wp_insert_post($post_data, true);
        $post_id = (int) $result;
    }

    if (is_wp_error($result)) {
        WP_CLI::error($result->get_error_message());
    }

    return $post_id;
}

$created = [
    'products' => 0,
    'catalogs' => 0,
    'applications' => 0,
    'inquiries' => 0,
];
$updated = [
    'products' => 0,
    'catalogs' => 0,
    'applications' => 0,
    'inquiries' => 0,
];

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
        $updated['products']++;
    } else {
        $post_id = wp_insert_post($post_data, true);
        $created['products']++;
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

foreach ($catalogs as $catalog) {
    $existing = get_posts([
        'post_type' => 'catalog',
        'name' => $catalog['slug'],
        'posts_per_page' => 1,
        'post_status' => 'any',
    ]);

    $post_id = luxstage_demo_upsert_by_slug('catalog', $catalog['slug'], [
        'post_status' => 'publish',
        'post_title' => $catalog['title'],
        'post_excerpt' => $catalog['excerpt'],
        'post_content' => $catalog['excerpt'] . ' Download the PDF after submitting a catalog request form.',
    ]);

    if ($existing) {
        $updated['catalogs']++;
    } else {
        $created['catalogs']++;
    }

    $upload = wp_upload_dir();
    $file = trailingslashit((string) $upload['basedir']) . $catalog['slug'] . '.pdf';
    if (!is_dir(dirname($file))) {
        wp_mkdir_p(dirname($file));
    }
    if (!file_exists($file)) {
        file_put_contents($file, 'Luxstage catalog file for ' . $catalog['title']);
    }

    $url = trailingslashit((string) $upload['baseurl']) . $catalog['slug'] . '.pdf';
    update_post_meta($post_id, 'pdf_url', $url);
    luxstage_demo_set_field($post_id, 'pdf_file', $url);
    luxstage_demo_set_field($post_id, 'catalog_version', $catalog['version']);
    wp_set_object_terms($post_id, [luxstage_demo_ensure_term('certification', $catalog['certification'])], 'certification');
}

foreach ($applications as $application) {
    $existing = get_posts([
        'post_type' => 'application',
        'name' => $application['slug'],
        'posts_per_page' => 1,
        'post_status' => 'any',
    ]);

    $post_id = luxstage_demo_upsert_by_slug('application', $application['slug'], [
        'post_status' => 'publish',
        'post_title' => $application['title'],
        'post_content' => $application['content'],
        'post_excerpt' => wp_trim_words($application['content'], 24),
    ]);

    if ($existing) {
        $updated['applications']++;
    } else {
        $created['applications']++;
    }

    wp_set_object_terms($post_id, [luxstage_demo_ensure_term('application_scene', $application['scene'])], 'application_scene');
}

foreach ($inquiries as $inquiry) {
    $existing = get_posts([
        'post_type' => 'inquiry_record',
        'name' => $inquiry['slug'],
        'posts_per_page' => 1,
        'post_status' => 'any',
    ]);

    $post_id = luxstage_demo_upsert_by_slug('inquiry_record', $inquiry['slug'], [
        'post_status' => 'publish',
        'post_title' => $inquiry['title'],
        'post_content' => $inquiry['content'],
    ]);

    if ($existing) {
        $updated['inquiries']++;
    } else {
        $created['inquiries']++;
    }

    update_post_meta($post_id, 'mail_status', $inquiry['mail_status']);
    update_post_meta($post_id, 'mail_to', $inquiry['mail_to']);
}

WP_CLI::success(sprintf(
    'Seeded Luxstage demo data (3 per feature). Products C/U: %d/%d, Catalogs C/U: %d/%d, Applications C/U: %d/%d, Inquiries C/U: %d/%d.',
    $created['products'],
    $updated['products'],
    $created['catalogs'],
    $updated['catalogs'],
    $created['applications'],
    $updated['applications'],
    $created['inquiries'],
    $updated['inquiries']
));
