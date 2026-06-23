<?php
/**
 * Core bootstrap for Luxstage theme.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LUXSTAGE_THEME_VERSION', '1.0.0');

function luxstage_field(string $name, int $post_id = 0): mixed
{
    if (!function_exists('get_field')) {
        return '';
    }

    return get_field($name, $post_id ?: get_the_ID());
}

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
});

add_action('wp_enqueue_scripts', static function (): void {
    $theme_uri = get_stylesheet_directory_uri();

    wp_enqueue_style(
        'luxstage-style',
        $theme_uri . '/style.css',
        [],
        LUXSTAGE_THEME_VERSION
    );

    wp_enqueue_style(
        'luxstage-functions',
        $theme_uri . '/functions.css',
        ['luxstage-style'],
        LUXSTAGE_THEME_VERSION
    );

    wp_register_script(
        'luxstage-main',
        $theme_uri . '/assets/js/main.js',
        [],
        LUXSTAGE_THEME_VERSION,
        true
    );

    if (file_exists(get_stylesheet_directory() . '/assets/js/main.js')) {
        wp_enqueue_script('luxstage-main');
    }
});

add_filter('script_loader_tag', static function (string $tag, string $handle, string $src): string {
    $async_handles = [
        'luxstage-main',
    ];

    if (!in_array($handle, $async_handles, true)) {
        return $tag;
    }

    return sprintf(
        '<script src="%s" id="%s-js" async></script>' . "\n",
        esc_url($src),
        esc_attr($handle)
    );
}, 10, 3);

add_filter('upload_mimes', static function (array $mimes): array {
    $mimes['webp'] = 'image/webp';
    return $mimes;
});

add_filter('wp_check_filetype_and_ext', static function (array $file_data, string $file, string $filename, array $mimes): array {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if ($extension === 'webp') {
        $file_data['ext'] = 'webp';
        $file_data['type'] = 'image/webp';
        $file_data['proper_filename'] = $filename;
    }

    return $file_data;
}, 10, 4);

add_action('init', static function (): void {
    if (!class_exists('WooCommerce')) {
        return;
    }

    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
}, 20);

add_filter('woocommerce_is_purchasable', '__return_false', 99);
add_filter('woocommerce_variation_is_purchasable', '__return_false', 99);
add_filter('woocommerce_loop_add_to_cart_link', '__return_empty_string', 99);
add_filter('woocommerce_product_single_add_to_cart_text', static fn(): string => '');
add_filter('woocommerce_product_add_to_cart_text', static fn(): string => '');

add_action('template_redirect', static function (): void {
    if (!class_exists('WooCommerce')) {
        return;
    }

    if (is_cart() || is_checkout()) {
        wp_safe_redirect(home_url('/products/'));
        exit;
    }
});

add_action('wp_head', static function (): void {
    if (is_admin()) {
        return;
    }

    $description = get_bloginfo('description') ?: 'Luxstage professional stage lighting manufacturer for moving heads, LED pars, strobes, effect lights, follow spots, and laser systems.';

    if (is_singular('stage_lighting')) {
        $description = wp_strip_all_tags(get_the_excerpt() ?: get_the_title());
    }

    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
}, 1);

add_action('wp_head', static function (): void {
    if (is_admin()) {
        return;
    }

    if (is_front_page()) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Luxstage',
            'url' => home_url('/'),
            'description' => 'Professional stage lighting manufacturer for global B2B buyers.',
            'sameAs' => [
                'https://www.linkedin.com/',
                'https://www.youtube.com/',
                'https://www.facebook.com/',
            ],
        ];
    } elseif (is_singular('stage_lighting')) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title(),
            'sku' => (string) luxstage_field('sku'),
            'brand' => [
                '@type' => 'Brand',
                'name' => 'Luxstage',
            ],
            'description' => wp_strip_all_tags(get_the_excerpt() ?: get_the_content()),
        ];
    } else {
        return;
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}, 20);

add_filter('robots_txt', static function (string $output, bool $public): string {
    $lines = [
        'User-agent: *',
        'Disallow: /wp-admin/',
        'Allow: /wp-admin/admin-ajax.php',
        'Sitemap: ' . home_url('/sitemap_index.xml'),
    ];

    return implode("\n", $lines) . "\n";
}, 10, 2);

add_filter('post_type_link', static function (string $post_link, WP_Post $post): string {
    if ($post->post_type !== 'stage_lighting') {
        return $post_link;
    }

    $terms = get_the_terms($post, 'product_category');
    $category_slug = (!is_wp_error($terms) && $terms) ? $terms[0]->slug : 'uncategorized';

    return home_url('/products/' . $category_slug . '/' . $post->post_name . '/');
}, 10, 2);

function luxstage_register_cpt(string $post_type, string $singular, string $plural, string $slug, string $icon): void
{
    register_post_type($post_type, [
        'labels' => [
            'name'                  => __($plural, 'luxstage'),
            'singular_name'         => __($singular, 'luxstage'),
            'menu_name'             => __($plural, 'luxstage'),
            'name_admin_bar'        => __($singular, 'luxstage'),
            'add_new_item'          => sprintf(__('Add New %s', 'luxstage'), $singular),
            'edit_item'             => sprintf(__('Edit %s', 'luxstage'), $singular),
            'view_item'             => sprintf(__('View %s', 'luxstage'), $singular),
            'all_items'             => sprintf(__('All %s', 'luxstage'), $plural),
            'search_items'          => sprintf(__('Search %s', 'luxstage'), $plural),
            'not_found'             => sprintf(__('No %s found.', 'luxstage'), strtolower($plural)),
            'not_found_in_trash'    => sprintf(__('No %s found in Trash.', 'luxstage'), strtolower($plural)),
            'featured_image'        => __('Featured Image', 'luxstage'),
            'set_featured_image'    => __('Set featured image', 'luxstage'),
            'remove_featured_image' => __('Remove featured image', 'luxstage'),
            'use_featured_image'    => __('Use as featured image', 'luxstage'),
        ],
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'has_archive'        => $slug,
        'rewrite'            => [
            'slug'       => $slug,
            'with_front' => false,
        ],
        'menu_icon'          => $icon,
        'supports'           => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        'map_meta_cap'       => true,
    ]);
}

add_action('init', static function (): void {
    luxstage_register_cpt('stage_lighting', 'Stage Lighting', 'Stage Lighting', 'products', 'dashicons-lightbulb');
    luxstage_register_cpt('application', 'Application Case', 'Application Cases', 'applications', 'dashicons-format-gallery');
    luxstage_register_cpt('catalog', 'Catalog', 'Catalogs', 'downloads/catalogs', 'dashicons-media-document');

    add_rewrite_rule(
        '^products/([^/]+)/([^/]+)/?$',
        'index.php?post_type=stage_lighting&name=$matches[2]',
        'top'
    );
}, 9);

add_action('init', static function (): void {
    register_taxonomy('product_type', ['stage_lighting'], [
        'public'            => false,
        'show_ui'           => false,
        'show_admin_column' => false,
        'show_in_rest'      => false,
        'hierarchical'      => true,
    ]);

    register_taxonomy('product_category', ['stage_lighting'], [
        'labels'            => [
            'name'          => __('Product Category', 'luxstage'),
            'singular_name' => __('Product Category', 'luxstage'),
        ],
        'public'            => true,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => [
            'slug'       => 'products/category',
            'with_front' => false,
        ],
    ]);

    register_taxonomy('application_scene', ['stage_lighting', 'application'], [
        'labels'            => [
            'name'          => __('Application Scene', 'luxstage'),
            'singular_name' => __('Application Scene', 'luxstage'),
        ],
        'public'            => true,
        'hierarchical'      => false,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => [
            'slug'       => 'applications/scene',
            'with_front' => false,
        ],
    ]);

    register_taxonomy('light_source', ['stage_lighting'], [
        'labels'            => [
            'name'          => __('Light Source', 'luxstage'),
            'singular_name' => __('Light Source', 'luxstage'),
        ],
        'public'            => true,
        'hierarchical'      => false,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => [
            'slug'       => 'products/light-source',
            'with_front' => false,
        ],
    ]);

    register_taxonomy('certification', ['stage_lighting', 'catalog'], [
        'labels'            => [
            'name'          => __('Certification', 'luxstage'),
            'singular_name' => __('Certification', 'luxstage'),
        ],
        'public'            => true,
        'hierarchical'      => false,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => [
            'slug'       => 'products/certification',
            'with_front' => false,
        ],
    ]);
}, 10);

function luxstage_seed_terms(string $taxonomy, array $terms): void
{
    foreach ($terms as $name => $slug) {
        if (!term_exists($name, $taxonomy)) {
            wp_insert_term($name, $taxonomy, ['slug' => $slug]);
        }
    }
}

add_action('init', static function (): void {
    luxstage_seed_terms('product_category', [
        'Moving Head'  => 'moving-head',
        'LED Par'      => 'led-par',
        'Strobe'       => 'strobe',
        'Effect Light' => 'effect-light',
        'Follow Spot'  => 'follow-spot',
        'Laser Light'  => 'laser-light',
        'Beam Light'   => 'beam-light',
    ]);

    luxstage_seed_terms('application_scene', [
        'Concert'           => 'concert',
        'Theatre'           => 'theatre',
        'Disco / Club'      => 'disco-club',
        'Event Rental'      => 'event-rental',
        'TV Studio'         => 'tv-studio',
        'Outdoor Festival'  => 'outdoor-festival',
    ]);

    luxstage_seed_terms('light_source', [
        'LED'   => 'led',
        'Bulb'  => 'bulb',
        'Laser' => 'laser',
    ]);

    luxstage_seed_terms('certification', [
        'CE'   => 'ce',
        'RoHS' => 'rohs',
        'UL'   => 'ul',
        'ETL'  => 'etl',
        'FCC'  => 'fcc',
    ]);

    foreach (['Suiting/Shirting', 'Functional', 'Fleece', 'Denim', 'Outdoor', 'Workwear', 'Technical'] as $legacy_term) {
        $term = term_exists($legacy_term, 'product_type');
        if (is_array($term)) {
            wp_delete_term((int) $term['term_id'], 'product_type');
        }
    }
}, 20);

function luxstage_acf_text_field(string $key, string $label, string $name, string $placeholder = '', bool $required = false): array
{
    return [
        'key'         => $key,
        'label'       => $label,
        'name'        => $name,
        'type'        => 'text',
        'required'    => $required ? 1 : 0,
        'placeholder' => $placeholder,
    ];
}

add_action('acf/init', static function (): void {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_luxstage_stage_lighting_specs',
        'title' => 'Stage Lighting Specifications',
        'fields' => [
            luxstage_acf_text_field('field_luxstage_sku', 'SKU', 'sku', 'e.g. LX-MH350'),
            luxstage_acf_text_field('field_luxstage_model', 'Model', 'model', 'e.g. X-Series 350'),
            [
                'key' => 'field_luxstage_light_source_type',
                'label' => 'Light Source Type',
                'name' => 'light_source_type',
                'type' => 'select',
                'choices' => [
                    'LED' => 'LED',
                    'Bulb' => 'Bulb',
                    'Laser' => 'Laser',
                    'Hybrid' => 'Hybrid',
                ],
                'ui' => 1,
                'required' => 1,
            ],
            luxstage_acf_text_field('field_luxstage_wattage', 'Wattage', 'wattage', 'e.g. 200W / 350W / 1200W', true),
            luxstage_acf_text_field('field_luxstage_color_temperature', 'Color Temperature', 'color_temperature', 'e.g. 3200K-8000K adjustable'),
            luxstage_acf_text_field('field_luxstage_light_life', 'Light Source Lifetime', 'light_life', 'e.g. 50000 hours'),
            [
                'key' => 'field_luxstage_luminous_flux',
                'label' => 'Luminous Flux',
                'name' => 'luminous_flux',
                'type' => 'number',
                'append' => 'lm',
            ],
            luxstage_acf_text_field('field_luxstage_beam_angle', 'Beam Angle', 'beam_angle', 'e.g. 4°-42° motorized zoom'),
            [
                'key' => 'field_luxstage_cri',
                'label' => 'CRI',
                'name' => 'cri',
                'type' => 'number',
                'placeholder' => 'e.g. 90 / 95',
            ],
            luxstage_acf_text_field('field_luxstage_channels', 'DMX Channels', 'channels', 'e.g. 16CH / 24CH / 36CH', true),
            [
                'key' => 'field_luxstage_control_protocols',
                'label' => 'Control Protocols',
                'name' => 'control_protocols',
                'type' => 'checkbox',
                'choices' => [
                    'DMX512' => 'DMX512',
                    'RDM' => 'RDM',
                    'Art-Net' => 'Art-Net',
                    'sACN' => 'sACN',
                ],
                'layout' => 'horizontal',
            ],
            [
                'key' => 'field_luxstage_wireless_control',
                'label' => 'Wireless Control',
                'name' => 'wireless_control',
                'type' => 'true_false',
                'message' => 'Supports CRMX / W-DMX',
                'ui' => 1,
            ],
            [
                'key' => 'field_luxstage_weight',
                'label' => 'Weight',
                'name' => 'weight',
                'type' => 'number',
                'append' => 'kg',
            ],
            luxstage_acf_text_field('field_luxstage_dimensions', 'Dimensions', 'dimensions', 'e.g. 350x250x450mm'),
            [
                'key' => 'field_luxstage_ip_rating',
                'label' => 'IP Rating',
                'name' => 'ip_rating',
                'type' => 'select',
                'choices' => [
                    'IP20' => 'IP20',
                    'IP54' => 'IP54',
                    'IP65' => 'IP65',
                    'IP67' => 'IP67',
                ],
                'default_value' => 'IP20',
                'ui' => 1,
                'required' => 1,
            ],
            luxstage_acf_text_field('field_luxstage_voltage', 'Voltage', 'voltage', 'e.g. AC 100-240V, 50/60Hz'),
            [
                'key' => 'field_luxstage_max_power',
                'label' => 'Max Power Consumption',
                'name' => 'max_power',
                'type' => 'number',
                'append' => 'W',
            ],
            [
                'key' => 'field_luxstage_effect_features',
                'label' => 'Effect Features',
                'name' => 'effect_features',
                'type' => 'checkbox',
                'choices' => [
                    'Color Wheel' => 'Color Wheel',
                    'Gobo Wheel' => 'Gobo Wheel',
                    'Prism' => 'Prism',
                    'Frost' => 'Frost',
                    'Zoom' => 'Zoom',
                ],
                'layout' => 'horizontal',
            ],
            luxstage_acf_text_field('field_luxstage_prism', 'Prism', 'prism', 'e.g. 8-facet / 16-facet'),
            [
                'key' => 'field_luxstage_dimming_curves',
                'label' => 'Dimming Curves',
                'name' => 'dimming_curves',
                'type' => 'checkbox',
                'choices' => [
                    'Linear' => 'Linear',
                    'S-Curve' => 'S-Curve',
                    'L-Curve' => 'L-Curve',
                ],
                'layout' => 'horizontal',
            ],
            luxstage_acf_text_field('field_luxstage_refresh_rate', 'Refresh Rate', 'refresh_rate', 'e.g. 2000Hz-25000Hz adjustable'),
            [
                'key' => 'field_luxstage_accessories',
                'label' => 'Standard Accessories',
                'name' => 'accessories',
                'type' => 'textarea',
                'placeholder' => 'Power cable, signal cable, hanging bracket, manual...',
            ],
            [
                'key' => 'field_luxstage_optional_accessories',
                'label' => 'Optional Accessories',
                'name' => 'optional_accessories',
                'type' => 'textarea',
                'placeholder' => 'Flight case, clamp, safety cable...',
            ],
            [
                'key' => 'field_luxstage_certification_standards',
                'label' => 'Certification Standards',
                'name' => 'certification_standards',
                'type' => 'checkbox',
                'choices' => [
                    'CE' => 'CE',
                    'RoHS' => 'RoHS',
                    'UL' => 'UL',
                    'ETL' => 'ETL',
                    'FCC' => 'FCC',
                ],
                'layout' => 'horizontal',
            ],
            [
                'key' => 'field_luxstage_catalog_pdf',
                'label' => 'Catalog PDF',
                'name' => 'catalog_pdf',
                'type' => 'file',
                'return_format' => 'array',
                'mime_types' => 'pdf',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'stage_lighting',
                ],
            ],
        ],
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'show_in_rest' => 1,
    ]);

    acf_add_local_field_group([
        'key' => 'group_luxstage_catalog_file',
        'title' => 'Catalog Download',
        'fields' => [
            [
                'key' => 'field_luxstage_catalog_file',
                'label' => 'PDF File',
                'name' => 'pdf_file',
                'type' => 'file',
                'return_format' => 'array',
                'mime_types' => 'pdf',
                'required' => 1,
            ],
            luxstage_acf_text_field('field_luxstage_catalog_version', 'Version', 'catalog_version', 'e.g. 2026 EN'),
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'catalog',
                ],
            ],
        ],
        'active' => true,
        'show_in_rest' => 1,
    ]);
});
