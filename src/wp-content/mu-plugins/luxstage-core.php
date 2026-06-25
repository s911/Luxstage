<?php
/**
 * Plugin Name: Luxstage Core
 * Description: Core business logic for Luxstage B2B site (CPT, taxonomies, ACF, SEO, URL rules, B2B commerce behavior).
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('luxstage_field')) {
    function luxstage_field(string $name, int $post_id = 0): mixed
    {
        if (!function_exists('get_field')) {
            return '';
        }

        return get_field($name, $post_id ?: get_the_ID());
    }
}

if (!function_exists('luxstage_ini_bytes')) {
    function luxstage_ini_bytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $last = strtolower(substr($value, -1));
        $num = (float) $value;
        return match ($last) {
            'g' => (int) ($num * 1024 * 1024 * 1024),
            'm' => (int) ($num * 1024 * 1024),
            'k' => (int) ($num * 1024),
            default => (int) $num,
        };
    }
}

if (!function_exists('luxstage_ensure_image_memory_limit')) {
    function luxstage_ensure_image_memory_limit(): void
    {
        $target = '256M';
        $current = (string) ini_get('memory_limit');
        $current_bytes = luxstage_ini_bytes($current);
        $target_bytes = luxstage_ini_bytes($target);

        // -1 means unlimited. Otherwise raise limit for media processing tasks.
        if ($current === '-1' || $current_bytes >= $target_bytes) {
            return;
        }

        @ini_set('memory_limit', $target);
    }
}

add_action('init', 'luxstage_ensure_image_memory_limit', 1);
add_action('admin_init', 'luxstage_ensure_image_memory_limit', 1);

add_filter('wp_image_editors', static function (array $editors): array {
    // Force GD only. In some containerized environments Imagick fails on PNG processing
    // and causes "The server cannot process the image" during media upload.
    if (class_exists('WP_Image_Editor_GD')) {
        return ['WP_Image_Editor_GD'];
    }
    return $editors;
}, 20);

// Temporary hardening for environments where image editors fail during upload.
// This allows media files to be uploaded without generating intermediate sizes.
add_filter('big_image_size_threshold', '__return_false');
add_filter('intermediate_image_sizes_advanced', static function (array $sizes): array {
    return [];
}, 10, 1);

if (!function_exists('luxstage_normalize_phone_number')) {
    function luxstage_normalize_phone_number(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '';
        }

        // Keep leading + if present, remove spaces/dashes/parentheses and non-digit chars.
        $has_plus = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value);
        if (!is_string($digits) || $digits === '') {
            return '';
        }

        return $has_plus ? '+' . $digits : $digits;
    }
}

if (!function_exists('luxstage_whatsapp_number')) {
    function luxstage_whatsapp_number(): string
    {
        $configured = (string) get_option('luxstage_whatsapp_number', '');
        if ($configured === '') {
            $configured = (string) getenv('LUXSTAGE_WHATSAPP_NUMBER');
        }

        return luxstage_normalize_phone_number($configured);
    }
}

if (!function_exists('luxstage_whatsapp_url')) {
    function luxstage_whatsapp_url(string $prefill_text = ''): string
    {
        $number = luxstage_whatsapp_number();
        if ($number === '') {
            return '';
        }

        $number_for_url = ltrim($number, '+');
        $url = 'https://wa.me/' . rawurlencode($number_for_url);
        if ($prefill_text !== '') {
            $url = add_query_arg('text', rawurlencode($prefill_text), $url);
        }

        return $url;
    }
}

add_action('admin_init', static function (): void {
    register_setting('general', 'luxstage_whatsapp_number', [
        'type' => 'string',
        'sanitize_callback' => static fn($value): string => luxstage_normalize_phone_number((string) $value),
        'default' => '',
    ]);

    add_settings_field(
        'luxstage_whatsapp_number',
        __('Luxstage WhatsApp Number', 'luxstage'),
        static function (): void {
            $value = (string) get_option('luxstage_whatsapp_number', '');
            ?>
            <input
                type="text"
                id="luxstage_whatsapp_number"
                name="luxstage_whatsapp_number"
                value="<?php echo esc_attr($value); ?>"
                class="regular-text"
                placeholder="+8613800000000"
            />
            <p class="description">
                <?php esc_html_e('Used by homepage WhatsApp contact button. Example: +8613800000000', 'luxstage'); ?>
            </p>
            <?php
        },
        'general'
    );
});

add_filter('upload_mimes', static function (array $mimes): array {
    $mimes['webp'] = 'image/webp';
    return $mimes;
});

add_filter('wp_check_filetype_and_ext', static function (array $file_data, string $file, string $filename, ?array $mimes = null): array {
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

add_action('send_headers', static function (): void {
    if (headers_sent()) {
        return;
    }

    // Baseline cache policy for local/staging verification.
    // Static asset cache can still be overridden by web server rules.
    header('Cache-Control: public, max-age=300, stale-while-revalidate=60');
});

if (!function_exists('luxstage_login_protection_enabled')) {
    function luxstage_login_protection_enabled(): bool
    {
        return true;
    }
}

if (!function_exists('luxstage_login_client_ip')) {
    function luxstage_login_client_ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $value = trim((string) $_SERVER[$key]);
            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $parts = explode(',', $value);
                $value = trim((string) ($parts[0] ?? ''));
            }
            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }

        return '0.0.0.0';
    }
}

add_filter('authenticate', static function ($user, string $username, string $password) {
    if (is_wp_error($user) || $user instanceof WP_User) {
        return $user;
    }

    if (!luxstage_login_protection_enabled()) {
        return $user;
    }

    $ip = luxstage_login_client_ip();
    $key = 'luxstage_login_fail_' . md5($ip);
    $attempts = (int) get_transient($key);
    $limit = 5;

    if ($attempts >= $limit) {
        return new WP_Error(
            'luxstage_login_locked',
            __('Too many failed login attempts. Please wait 15 minutes and try again.', 'luxstage')
        );
    }

    return $user;
}, 25, 3);

add_action('wp_login_failed', static function (string $username): void {
    if (!luxstage_login_protection_enabled()) {
        return;
    }

    $ip = luxstage_login_client_ip();
    $key = 'luxstage_login_fail_' . md5($ip);
    $attempts = (int) get_transient($key);
    set_transient($key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
});

add_action('wp_login', static function (string $user_login, WP_User $user): void {
    if (!luxstage_login_protection_enabled()) {
        return;
    }

    $ip = luxstage_login_client_ip();
    $key = 'luxstage_login_fail_' . md5($ip);
    delete_transient($key);
}, 10, 2);

add_filter('post_type_link', static function (string $post_link, WP_Post $post): string {
    if ($post->post_type !== 'stage_lighting') {
        return $post_link;
    }

    $terms = get_the_terms($post, 'product_category');
    $category_slug = (!is_wp_error($terms) && $terms) ? $terms[0]->slug : 'uncategorized';

    return home_url('/products/' . $category_slug . '/' . $post->post_name . '/');
}, 10, 2);

if (!function_exists('luxstage_register_cpt')) {
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
    $rewrite_version = '2026-06-24-1';
    $option_key = 'luxstage_rewrite_rules_version';
    $current = (string) get_option($option_key, '');
    if ($current === $rewrite_version) {
        return;
    }

    flush_rewrite_rules(false);
    update_option($option_key, $rewrite_version, false);
}, 99);

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

if (!function_exists('luxstage_seed_terms')) {
    function luxstage_seed_terms(string $taxonomy, array $terms): void
    {
        foreach ($terms as $name => $slug) {
            if (!term_exists($name, $taxonomy)) {
                wp_insert_term($name, $taxonomy, ['slug' => $slug]);
            }
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
}, 20);

if (!function_exists('luxstage_acf_text_field')) {
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
                'choices' => ['LED' => 'LED', 'Bulb' => 'Bulb', 'Laser' => 'Laser', 'Hybrid' => 'Hybrid'],
                'ui' => 1,
                'required' => 1,
            ],
            luxstage_acf_text_field('field_luxstage_wattage', 'Wattage', 'wattage', 'e.g. 200W / 350W / 1200W', true),
            luxstage_acf_text_field('field_luxstage_color_temperature', 'Color Temperature', 'color_temperature', 'e.g. 3200K-8000K adjustable'),
            luxstage_acf_text_field('field_luxstage_light_life', 'Light Source Lifetime', 'light_life', 'e.g. 50000 hours'),
            ['key' => 'field_luxstage_luminous_flux', 'label' => 'Luminous Flux', 'name' => 'luminous_flux', 'type' => 'number', 'append' => 'lm'],
            luxstage_acf_text_field('field_luxstage_beam_angle', 'Beam Angle', 'beam_angle', 'e.g. 4°-42° motorized zoom'),
            ['key' => 'field_luxstage_cri', 'label' => 'CRI', 'name' => 'cri', 'type' => 'number', 'placeholder' => 'e.g. 90 / 95'],
            luxstage_acf_text_field('field_luxstage_channels', 'DMX Channels', 'channels', 'e.g. 16CH / 24CH / 36CH', true),
            [
                'key' => 'field_luxstage_control_protocols',
                'label' => 'Control Protocols',
                'name' => 'control_protocols',
                'type' => 'checkbox',
                'choices' => ['DMX512' => 'DMX512', 'RDM' => 'RDM', 'Art-Net' => 'Art-Net', 'sACN' => 'sACN'],
                'layout' => 'horizontal',
            ],
            ['key' => 'field_luxstage_wireless_control', 'label' => 'Wireless Control', 'name' => 'wireless_control', 'type' => 'true_false', 'message' => 'Supports CRMX / W-DMX', 'ui' => 1],
            ['key' => 'field_luxstage_weight', 'label' => 'Weight', 'name' => 'weight', 'type' => 'number', 'append' => 'kg'],
            luxstage_acf_text_field('field_luxstage_dimensions', 'Dimensions', 'dimensions', 'e.g. 350x250x450mm'),
            [
                'key' => 'field_luxstage_ip_rating',
                'label' => 'IP Rating',
                'name' => 'ip_rating',
                'type' => 'select',
                'choices' => ['IP20' => 'IP20', 'IP54' => 'IP54', 'IP65' => 'IP65', 'IP67' => 'IP67'],
                'default_value' => 'IP20',
                'ui' => 1,
                'required' => 1,
            ],
            luxstage_acf_text_field('field_luxstage_voltage', 'Voltage', 'voltage', 'e.g. AC 100-240V, 50/60Hz'),
            ['key' => 'field_luxstage_max_power', 'label' => 'Max Power Consumption', 'name' => 'max_power', 'type' => 'number', 'append' => 'W'],
            [
                'key' => 'field_luxstage_effect_features',
                'label' => 'Effect Features',
                'name' => 'effect_features',
                'type' => 'checkbox',
                'choices' => ['Color Wheel' => 'Color Wheel', 'Gobo Wheel' => 'Gobo Wheel', 'Prism' => 'Prism', 'Frost' => 'Frost', 'Zoom' => 'Zoom'],
                'layout' => 'horizontal',
            ],
            luxstage_acf_text_field('field_luxstage_prism', 'Prism', 'prism', 'e.g. 8-facet / 16-facet'),
            [
                'key' => 'field_luxstage_dimming_curves',
                'label' => 'Dimming Curves',
                'name' => 'dimming_curves',
                'type' => 'checkbox',
                'choices' => ['Linear' => 'Linear', 'S-Curve' => 'S-Curve', 'L-Curve' => 'L-Curve'],
                'layout' => 'horizontal',
            ],
            luxstage_acf_text_field('field_luxstage_refresh_rate', 'Refresh Rate', 'refresh_rate', 'e.g. 2000Hz-25000Hz adjustable'),
            ['key' => 'field_luxstage_accessories', 'label' => 'Standard Accessories', 'name' => 'accessories', 'type' => 'textarea'],
            ['key' => 'field_luxstage_optional_accessories', 'label' => 'Optional Accessories', 'name' => 'optional_accessories', 'type' => 'textarea'],
            [
                'key' => 'field_luxstage_certification_standards',
                'label' => 'Certification Standards',
                'name' => 'certification_standards',
                'type' => 'checkbox',
                'choices' => ['CE' => 'CE', 'RoHS' => 'RoHS', 'UL' => 'UL', 'ETL' => 'ETL', 'FCC' => 'FCC'],
                'layout' => 'horizontal',
            ],
            ['key' => 'field_luxstage_catalog_pdf', 'label' => 'Catalog PDF', 'name' => 'catalog_pdf', 'type' => 'file', 'return_format' => 'array', 'mime_types' => 'pdf'],
        ],
        'location' => [[[ 'param' => 'post_type', 'operator' => '==', 'value' => 'stage_lighting' ]]],
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'show_in_rest' => 1,
    ]);

    acf_add_local_field_group([
        'key' => 'group_luxstage_stage_lighting_media',
        'title' => 'Product Media',
        'fields' => [
            [
                'key' => 'field_luxstage_video_url',
                'label' => 'Video URL',
                'name' => 'video_url',
                'type' => 'url',
                'placeholder' => 'https://',
            ],
        ],
        'location' => [[[ 'param' => 'post_type', 'operator' => '==', 'value' => 'stage_lighting' ]]],
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'show_in_rest' => 1,
    ]);

    acf_add_local_field_group([
        'key' => 'group_luxstage_stage_lighting_gallery',
        'title' => 'Product Gallery',
        'fields' => [
            [
                'key' => 'field_luxstage_gallery_images',
                'label' => 'Gallery Images',
                'name' => 'gallery_images',
                'type' => 'gallery',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'insert' => 'append',
                'library' => 'all',
            ],
        ],
        'location' => [[[ 'param' => 'post_type', 'operator' => '==', 'value' => 'stage_lighting' ]]],
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
            ['key' => 'field_luxstage_catalog_file', 'label' => 'PDF File', 'name' => 'pdf_file', 'type' => 'file', 'return_format' => 'array', 'mime_types' => 'pdf', 'required' => 1],
            luxstage_acf_text_field('field_luxstage_catalog_version', 'Version', 'catalog_version', 'e.g. 2026 EN'),
        ],
        'location' => [[[ 'param' => 'post_type', 'operator' => '==', 'value' => 'catalog' ]]],
        'active' => true,
        'show_in_rest' => 1,
    ]);
});

if (!function_exists('luxstage_get_catalog_pdf_url')) {
    function luxstage_get_catalog_pdf_url(int $post_id): string
    {
        $acf_file = luxstage_field('pdf_file', $post_id);
        if (is_array($acf_file) && !empty($acf_file['url'])) {
            return (string) $acf_file['url'];
        }
        if (is_string($acf_file) && filter_var($acf_file, FILTER_VALIDATE_URL)) {
            return $acf_file;
        }

        $meta_url = (string) get_post_meta($post_id, 'pdf_url', true);
        if ($meta_url !== '' && filter_var($meta_url, FILTER_VALIDATE_URL)) {
            return $meta_url;
        }

        return '';
    }
}

if (!function_exists('luxstage_catalog_signature')) {
    function luxstage_catalog_signature(int $catalog_id, int $expires): string
    {
        return hash_hmac('sha256', $catalog_id . '|' . $expires, wp_salt('auth'));
    }
}

if (!function_exists('luxstage_catalog_secure_download_url')) {
    function luxstage_catalog_secure_download_url(int $catalog_id, int $ttl = DAY_IN_SECONDS): string
    {
        $expires = time() + max(300, $ttl);
        $signature = luxstage_catalog_signature($catalog_id, $expires);
        return add_query_arg(
            [
                'luxstage_catalog_download' => 1,
                'catalog_id' => $catalog_id,
                'expires' => $expires,
                'sig' => $signature,
            ],
            home_url('/')
        );
    }
}

if (!function_exists('luxstage_catalog_local_file_path')) {
    function luxstage_catalog_local_file_path(string $pdf_url): string
    {
        $uploads = wp_upload_dir();
        $baseurl = (string) ($uploads['baseurl'] ?? '');
        $basedir = (string) ($uploads['basedir'] ?? '');
        if ($baseurl === '' || $basedir === '') {
            return '';
        }

        if (!str_starts_with($pdf_url, $baseurl)) {
            return '';
        }

        $relative = ltrim((string) substr($pdf_url, strlen($baseurl)), '/');
        return $relative !== '' ? trailingslashit($basedir) . $relative : '';
    }
}

if (!function_exists('luxstage_write_catalog_placeholder')) {
    function luxstage_write_catalog_placeholder(string $path, int $catalog_id): bool
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !wp_mkdir_p($dir)) {
            return false;
        }

        $title = get_the_title($catalog_id) ?: 'Luxstage Catalog';
        $content = "Luxstage catalog placeholder\nCatalog ID: {$catalog_id}\nTitle: {$title}\n";
        return file_put_contents($path, $content) !== false;
    }
}

add_action('init', static function (): void {
    register_post_type('inquiry_record', [
        'labels' => [
            'name' => __('Inquiry Records', 'luxstage'),
            'singular_name' => __('Inquiry Record', 'luxstage'),
            'menu_name' => __('Inquiry Records', 'luxstage'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'custom-fields'],
        'menu_icon' => 'dashicons-email-alt',
    ]);

    register_post_type('mail_record', [
        'labels' => [
            'name' => __('Mail Records', 'luxstage'),
            'singular_name' => __('Mail Record', 'luxstage'),
            'menu_name' => __('Mail Records', 'luxstage'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'custom-fields'],
        'menu_icon' => 'dashicons-email',
    ]);

    add_rewrite_rule(
        '^catalog-download/?$',
        'index.php?luxstage_catalog_download=1',
        'top'
    );
}, 30);

add_filter('query_vars', static function (array $vars): array {
    $vars[] = 'luxstage_catalog_download';
    return $vars;
});

add_action('template_redirect', static function (): void {
    $download_flag = (string) get_query_var('luxstage_catalog_download');
    if ($download_flag !== '1' && empty($_GET['luxstage_catalog_download'])) {
        return;
    }

    $catalog_id = isset($_GET['catalog_id']) ? (int) $_GET['catalog_id'] : 0;
    $expires = isset($_GET['expires']) ? (int) $_GET['expires'] : 0;
    $signature = sanitize_text_field((string) ($_GET['sig'] ?? ''));

    if ($catalog_id <= 0 || $expires <= 0 || $signature === '') {
        status_header(400);
        wp_die(esc_html__('Invalid download request.', 'luxstage'));
    }

    if (time() > $expires) {
        status_header(410);
        wp_die(esc_html__('Download link expired. Please request a new catalog link.', 'luxstage'));
    }

    $expected = luxstage_catalog_signature($catalog_id, $expires);
    if (!hash_equals($expected, $signature)) {
        status_header(403);
        wp_die(esc_html__('Invalid download signature.', 'luxstage'));
    }

    $pdf_url = luxstage_get_catalog_pdf_url($catalog_id);
    if ($pdf_url === '') {
        status_header(404);
        wp_die(esc_html__('Catalog file not found.', 'luxstage'));
    }

    $local_path = luxstage_catalog_local_file_path($pdf_url);
    if ($local_path !== '') {
        if (!file_exists($local_path)) {
            luxstage_write_catalog_placeholder($local_path, $catalog_id);
        }

        if (is_readable($local_path)) {
            nocache_headers();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($local_path) . '"');
            header('Content-Length: ' . (string) filesize($local_path));
            readfile($local_path);
            exit;
        }
    }

    wp_safe_redirect($pdf_url);
    exit;
});

add_action('init', static function (): void {
    if (empty($_GET['luxstage_catalog_return'])) {
        return;
    }

    setcookie('luxstage_catalog_returning', '1', time() + (30 * DAY_IN_SECONDS), COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
});

add_shortcode('luxstage_catalog_returning', static function (): string {
    $is_returning = !empty($_COOKIE['luxstage_catalog_returning']);
    $archive_url = home_url('/downloads/catalogs/');
    $set_cookie_url = add_query_arg('luxstage_catalog_return', '1', home_url('/catalog-request/'));

    if ($is_returning) {
        return '<p><strong>' . esc_html__('Returning visitor: download catalog directly.', 'luxstage') . '</strong></p><p><a href="' . esc_url($archive_url) . '">' . esc_html__('Go to catalog downloads', 'luxstage') . '</a></p>';
    }

    return '<p>' . esc_html__('Already submitted before?', 'luxstage') . ' <a href="' . esc_url($set_cookie_url) . '">' . esc_html__('Mark as returning visitor', 'luxstage') . '</a></p>';
});

add_action('wpcf7_mail_sent', static function ($contact_form): void {
    if (!class_exists('WPCF7_Submission')) {
        return;
    }

    $submission = WPCF7_Submission::get_instance();
    if (!$submission) {
        return;
    }

    $data = $submission->get_posted_data();
    if (!is_array($data)) {
        $data = [];
    }

    $name = sanitize_text_field((string) ($data['your-name'] ?? 'Unknown'));
    $email = sanitize_email((string) ($data['your-email'] ?? ''));
    $form_title = method_exists($contact_form, 'title') ? (string) $contact_form->title() : 'CF7 Form';

    $content_lines = [
        'Form: ' . $form_title,
        'Name: ' . $name,
        'Email: ' . $email,
        'Data: ' . wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];

    wp_insert_post([
        'post_type' => 'inquiry_record',
        'post_status' => 'publish',
        'post_title' => sprintf('%s - %s', $form_title, $name ?: current_time('mysql')),
        'post_content' => implode("\n", $content_lines),
    ]);
}, 10, 1);

add_action('wp_mail_succeeded', static function (array $mail_data): void {
    $to = $mail_data['to'] ?? '';
    if (is_array($to)) {
        $to = implode(',', array_map('strval', $to));
    }

    wp_insert_post([
        'post_type' => 'mail_record',
        'post_status' => 'publish',
        'post_title' => 'Mail success - ' . current_time('mysql'),
        'post_content' => implode("\n", [
            'Status: success',
            'To: ' . (string) $to,
            'Subject: ' . (string) ($mail_data['subject'] ?? ''),
            'Message: ' . (string) ($mail_data['message'] ?? ''),
        ]),
    ]);
}, 10, 1);

add_action('wp_mail_failed', static function (WP_Error $error): void {
    $data = $error->get_error_data();
    if (!is_array($data)) {
        $data = [];
    }
    $to = $data['to'] ?? '';
    if (is_array($to)) {
        $to = implode(',', array_map('strval', $to));
    }

    wp_insert_post([
        'post_type' => 'mail_record',
        'post_status' => 'publish',
        'post_title' => 'Mail failed - ' . current_time('mysql'),
        'post_content' => implode("\n", [
            'Status: failed',
            'To: ' . (string) $to,
            'Subject: ' . (string) ($data['subject'] ?? ''),
            'Error: ' . $error->get_error_message(),
        ]),
    ]);
}, 10, 1);

add_filter('wp_mail_from', static function (string $from): string {
    $configured = (string) getenv('LUXSTAGE_MAIL_FROM');
    if ($configured !== '' && is_email($configured)) {
        return $configured;
    }

    // Avoid invalid default "wordpress@localhost" in containerized local env.
    return 'no-reply@luxstage.local';
});

add_filter('wp_mail_from_name', static function (string $from_name): string {
    $configured = (string) getenv('LUXSTAGE_MAIL_FROM_NAME');
    if ($configured !== '') {
        return $configured;
    }

    return $from_name !== '' ? $from_name : 'Luxstage Local';
});

add_action('phpmailer_init', static function (PHPMailer\PHPMailer\PHPMailer $phpmailer): void {
    $mail_mode = (string) getenv('LUXSTAGE_MAIL_MODE');
    if ($mail_mode !== 'mailpit') {
        return;
    }

    $smtp_host = (string) getenv('LUXSTAGE_SMTP_HOST');
    $smtp_port_raw = (string) getenv('LUXSTAGE_SMTP_PORT');

    $smtp_host = $smtp_host !== '' ? $smtp_host : 'mailpit';
    $smtp_port = (int) ($smtp_port_raw !== '' ? $smtp_port_raw : '1025');

    $phpmailer->isSMTP();
    $phpmailer->Host = $smtp_host;
    $phpmailer->Port = $smtp_port;
    $phpmailer->SMTPAuth = false;
    $phpmailer->SMTPSecure = '';
    $phpmailer->Timeout = 15;
});

if (!function_exists('luxstage_contact_target_email')) {
    function luxstage_contact_target_email(): string
    {
        $candidates = [
            (string) getenv('LUXSTAGE_INQUIRY_TO_EMAIL'),
            (string) getenv('LUXSTAGE_CONTACT_TO_EMAIL'),
            (string) get_option('admin_email'),
        ];

        foreach ($candidates as $email) {
            $email = sanitize_email($email);
            if ($email !== '' && is_email($email)) {
                return $email;
            }
        }

        return 'no-reply@luxstage.local';
    }
}

if (!function_exists('luxstage_contact_page')) {
    function luxstage_contact_page(): bool
    {
        if (is_admin() || !is_page()) {
            return false;
        }

        if (is_page(['contact', 'contact-us'])) {
            return true;
        }

        $object = get_queried_object();
        if (!$object instanceof WP_Post || $object->post_type !== 'page') {
            return false;
        }

        $slug = sanitize_title((string) $object->post_name);
        if (in_array($slug, ['contact', 'contact-us', 'lianxiwomen'], true)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('luxstage_contact_flash_key')) {
    function luxstage_contact_flash_key(string $token): string
    {
        return 'luxstage_contact_flash_' . $token;
    }
}

if (!function_exists('luxstage_contact_store_flash')) {
    function luxstage_contact_store_flash(array $payload): string
    {
        $token = wp_generate_password(16, false, false);
        set_transient(luxstage_contact_flash_key($token), $payload, 10 * MINUTE_IN_SECONDS);
        return $token;
    }
}

if (!function_exists('luxstage_contact_read_flash')) {
    function luxstage_contact_read_flash(): array
    {
        $token = sanitize_key((string) ($_GET['luxstage_contact_token'] ?? ''));
        if ($token === '') {
            return [];
        }

        $flash = get_transient(luxstage_contact_flash_key($token));
        if (!is_array($flash)) {
            return [];
        }

        delete_transient(luxstage_contact_flash_key($token));
        return $flash;
    }
}

add_action('template_redirect', static function (): void {
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        return;
    }
    if (empty($_POST['luxstage_contact_submit']) || !luxstage_contact_page()) {
        return;
    }

    $redirect_url = get_permalink() ?: home_url('/contact/');
    $nonce = (string) ($_POST['luxstage_contact_nonce'] ?? '');
    if (!wp_verify_nonce($nonce, 'luxstage_contact_submit')) {
        $token = luxstage_contact_store_flash([
            'status' => 'error',
            'message' => __('Security check failed. Please submit again.', 'luxstage'),
            'old' => [],
        ]);
        wp_safe_redirect(add_query_arg('luxstage_contact_token', $token, $redirect_url));
        exit;
    }

    $trap = trim((string) ($_POST['website'] ?? ''));
    if ($trap !== '') {
        $token = luxstage_contact_store_flash([
            'status' => 'error',
            'message' => __('Submission rejected.', 'luxstage'),
            'old' => [],
        ]);
        wp_safe_redirect(add_query_arg('luxstage_contact_token', $token, $redirect_url));
        exit;
    }

    $name = sanitize_text_field((string) wp_unslash($_POST['lux_name'] ?? ''));
    $phone = sanitize_text_field((string) wp_unslash($_POST['lux_phone'] ?? ''));
    $email = sanitize_email((string) wp_unslash($_POST['lux_email'] ?? ''));
    $company = sanitize_text_field((string) wp_unslash($_POST['lux_company'] ?? ''));
    $message = sanitize_textarea_field((string) wp_unslash($_POST['lux_message'] ?? ''));
    $product_sku = sanitize_text_field((string) wp_unslash($_POST['lux_product_sku'] ?? ''));

    $old = [
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'company' => $company,
        'message' => $message,
        'product_sku' => $product_sku,
    ];

    if ($name === '' || !is_email($email) || $message === '') {
        $token = luxstage_contact_store_flash([
            'status' => 'error',
            'message' => __('Please fill required fields: Name, Email, Description.', 'luxstage'),
            'old' => $old,
        ]);
        wp_safe_redirect(add_query_arg('luxstage_contact_token', $token, $redirect_url));
        exit;
    }

    $content_lines = [
        'Source: Contact page fallback form',
        'Name: ' . $name,
        'Phone: ' . $phone,
        'Email: ' . $email,
        'Company: ' . $company,
        'Product SKU: ' . $product_sku,
        'Description:',
        $message,
    ];

    $inquiry_id = wp_insert_post([
        'post_type' => 'inquiry_record',
        'post_status' => 'publish',
        'post_title' => sprintf('Contact - %s - %s', $name, current_time('mysql')),
        'post_content' => implode("\n", $content_lines),
    ], true);

    $to = luxstage_contact_target_email();
    $subject = sprintf('[Luxstage] New Contact Inquiry - %s', $name);
    $mail_body = implode("\n", [
        'A new inquiry is submitted from Contact page.',
        '',
        'Name: ' . $name,
        'Phone: ' . $phone,
        'Email: ' . $email,
        'Company: ' . $company,
        'Product SKU: ' . $product_sku,
        '',
        'Description:',
        $message,
    ]);
    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    if ($email !== '' && is_email($email)) {
        $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
    }

    $mail_ok = wp_mail($to, $subject, $mail_body, $headers);
    if (is_int($inquiry_id) && $inquiry_id > 0) {
        update_post_meta($inquiry_id, 'mail_status', $mail_ok ? 'sent' : 'failed');
        update_post_meta($inquiry_id, 'mail_to', $to);
    }

    $token = luxstage_contact_store_flash([
        'status' => $mail_ok ? 'success' : 'warning',
        'message' => $mail_ok
            ? __('Inquiry sent successfully. Our team will contact you soon.', 'luxstage')
            : __('Inquiry saved, but email delivery failed. Please check mail configuration.', 'luxstage'),
        'old' => $mail_ok ? [] : $old,
    ]);

    wp_safe_redirect(add_query_arg('luxstage_contact_token', $token, $redirect_url));
    exit;
}, 12);

if (!function_exists('luxstage_contact_form_markup')) {
    function luxstage_contact_form_markup(): string
    {
        $flash = luxstage_contact_read_flash();
        $status = (string) ($flash['status'] ?? '');
        $notice = (string) ($flash['message'] ?? '');
        $old = is_array($flash['old'] ?? null) ? $flash['old'] : [];

        $default_sku = sanitize_text_field((string) ($_GET['product_sku'] ?? ''));
        $values = [
            'name' => (string) ($old['name'] ?? ''),
            'phone' => (string) ($old['phone'] ?? ''),
            'email' => (string) ($old['email'] ?? ''),
            'company' => (string) ($old['company'] ?? ''),
            'product_sku' => (string) ($old['product_sku'] ?? $default_sku),
            'message' => (string) ($old['message'] ?? ''),
        ];

        $recipient = luxstage_contact_target_email();
        $nonce = wp_create_nonce('luxstage_contact_submit');

        ob_start();
        ?>
        <section class="lux-contact-fallback" id="lux-contact-fallback">
            <style>
                .lux-contact-fallback{margin-top:8px;padding:24px;border:1px solid #e5e7eb;border-radius:12px;background:#fff}
                .lux-contact-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
                .lux-contact-grid .lux-field-full{grid-column:1 / -1}
                .lux-contact-fallback label{display:block;font-weight:600;margin-bottom:6px}
                .lux-contact-fallback input,.lux-contact-fallback textarea{width:100%;border:1px solid #d1d5db;border-radius:8px;padding:10px 12px;box-sizing:border-box}
                .lux-contact-fallback textarea{min-height:130px;resize:vertical}
                .lux-contact-note{font-size:.875rem;color:#4b5563;margin:10px 0 14px}
                .lux-contact-notice{padding:10px 12px;border-radius:8px;margin:0 0 12px}
                .lux-contact-notice.is-success{background:#ecfdf5;color:#065f46}
                .lux-contact-notice.is-warning{background:#fffbeb;color:#92400e}
                .lux-contact-notice.is-error{background:#fef2f2;color:#991b1b}
                .lux-contact-actions{margin-top:14px}
                .lux-contact-submit{display:inline-block;padding:10px 18px;border:0;border-radius:8px;background:#111827;color:#fff;font-weight:700;cursor:pointer}
                @media (max-width:720px){.lux-contact-grid{grid-template-columns:1fr}}
            </style>
            <?php if ($notice !== '') : ?>
                <div class="lux-contact-notice <?php echo esc_attr('is-' . ($status !== '' ? $status : 'success')); ?>">
                    <?php echo esc_html($notice); ?>
                </div>
            <?php endif; ?>
            <p class="lux-contact-note">
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: %s is inquiry recipient email */
                        __('Your inquiry will be delivered to %s.', 'luxstage'),
                        $recipient
                    )
                );
                ?>
            </p>
            <form method="post" action="<?php echo esc_url(get_permalink() ?: home_url('/contact/')); ?>">
                <input type="hidden" name="luxstage_contact_submit" value="1">
                <input type="hidden" name="luxstage_contact_nonce" value="<?php echo esc_attr($nonce); ?>">
                <div style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;">
                    <label for="lux-website"><?php esc_html_e('Website', 'luxstage'); ?></label>
                    <input type="text" id="lux-website" name="website" value="">
                </div>
                <div class="lux-contact-grid">
                    <div>
                        <label for="lux-name"><?php esc_html_e('Name *', 'luxstage'); ?></label>
                        <input id="lux-name" name="lux_name" type="text" required value="<?php echo esc_attr($values['name']); ?>">
                    </div>
                    <div>
                        <label for="lux-phone"><?php esc_html_e('Phone', 'luxstage'); ?></label>
                        <input id="lux-phone" name="lux_phone" type="text" value="<?php echo esc_attr($values['phone']); ?>">
                    </div>
                    <div>
                        <label for="lux-email"><?php esc_html_e('Email *', 'luxstage'); ?></label>
                        <input id="lux-email" name="lux_email" type="email" required value="<?php echo esc_attr($values['email']); ?>">
                    </div>
                    <div>
                        <label for="lux-company"><?php esc_html_e('Company', 'luxstage'); ?></label>
                        <input id="lux-company" name="lux_company" type="text" value="<?php echo esc_attr($values['company']); ?>">
                    </div>
                    <div class="lux-field-full">
                        <label for="lux-product-sku"><?php esc_html_e('Product SKU (Optional)', 'luxstage'); ?></label>
                        <input id="lux-product-sku" name="lux_product_sku" type="text" value="<?php echo esc_attr($values['product_sku']); ?>">
                    </div>
                    <div class="lux-field-full">
                        <label for="lux-message"><?php esc_html_e('Description *', 'luxstage'); ?></label>
                        <textarea id="lux-message" name="lux_message" required><?php echo esc_textarea($values['message']); ?></textarea>
                    </div>
                </div>
                <div class="lux-contact-actions">
                    <button class="lux-contact-submit" type="submit"><?php esc_html_e('Send Inquiry', 'luxstage'); ?></button>
                </div>
            </form>
        </section>
        <?php
        return (string) ob_get_clean();
    }
}

add_filter('the_content', static function (string $content): string {
    if (is_admin() || !in_the_loop() || !is_main_query() || !luxstage_contact_page()) {
        return $content;
    }

    $fallback = luxstage_contact_form_markup();
    return $fallback !== '' ? $fallback : $content;
}, 40);
