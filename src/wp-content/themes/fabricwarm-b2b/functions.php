<?php
/**
 * Core bootstrap for Fabricwarm B2B theme.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FW_B2B_THEME_VERSION', '1.0.0');

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
});

add_action('wp_enqueue_scripts', static function (): void {
    $theme_uri = get_stylesheet_directory_uri();

    wp_enqueue_style(
        'fabricwarm-b2b-style',
        $theme_uri . '/style.css',
        [],
        FW_B2B_THEME_VERSION
    );

    wp_enqueue_style(
        'fabricwarm-b2b-functions',
        $theme_uri . '/functions.css',
        ['fabricwarm-b2b-style'],
        FW_B2B_THEME_VERSION
    );

    wp_register_script(
        'fabricwarm-b2b-main',
        $theme_uri . '/assets/js/main.js',
        [],
        FW_B2B_THEME_VERSION,
        true
    );

    if (file_exists(get_stylesheet_directory() . '/assets/js/main.js')) {
        wp_enqueue_script('fabricwarm-b2b-main');
    }
});

add_filter('script_loader_tag', static function (string $tag, string $handle, string $src): string {
    $async_handles = [
        'fabricwarm-b2b-main',
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

add_action('init', static function (): void {
    $labels = [
        'name'                  => __('Stage Lighting', 'fabricwarm-b2b'),
        'singular_name'         => __('Stage Lighting', 'fabricwarm-b2b'),
        'menu_name'             => __('Stage Lighting', 'fabricwarm-b2b'),
        'name_admin_bar'        => __('Stage Lighting', 'fabricwarm-b2b'),
        'add_new'               => __('Add New', 'fabricwarm-b2b'),
        'add_new_item'          => __('Add New Stage Lighting', 'fabricwarm-b2b'),
        'new_item'              => __('New Stage Lighting', 'fabricwarm-b2b'),
        'edit_item'             => __('Edit Stage Lighting', 'fabricwarm-b2b'),
        'view_item'             => __('View Stage Lighting', 'fabricwarm-b2b'),
        'all_items'             => __('All Stage Lighting', 'fabricwarm-b2b'),
        'search_items'          => __('Search Stage Lighting', 'fabricwarm-b2b'),
        'not_found'             => __('No stage lighting found.', 'fabricwarm-b2b'),
        'not_found_in_trash'    => __('No stage lighting found in Trash.', 'fabricwarm-b2b'),
        'featured_image'        => __('Product Image', 'fabricwarm-b2b'),
        'set_featured_image'    => __('Set product image', 'fabricwarm-b2b'),
        'remove_featured_image' => __('Remove product image', 'fabricwarm-b2b'),
        'use_featured_image'    => __('Use as product image', 'fabricwarm-b2b'),
    ];

    register_post_type('stage_lighting', [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'has_archive'        => 'products',
        'rewrite'            => [
            'slug'       => 'products',
            'with_front' => false,
        ],
        'menu_icon'          => 'dashicons-lightbulb',
        'supports'           => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        'map_meta_cap'       => true,
    ]);
}, 9);

add_action('init', static function (): void {
    register_taxonomy('product_type', ['stage_lighting'], [
        'labels'            => [
            'name'          => __('Product Type', 'fabricwarm-b2b'),
            'singular_name' => __('Product Type', 'fabricwarm-b2b'),
        ],
        'public'            => true,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => [
            'slug'       => 'products/type',
            'with_front' => false,
        ],
    ]);

    register_taxonomy('lighting_source', ['stage_lighting'], [
        'labels'            => [
            'name'          => __('Lighting Source', 'fabricwarm-b2b'),
            'singular_name' => __('Lighting Source', 'fabricwarm-b2b'),
        ],
        'public'            => true,
        'hierarchical'      => false,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => [
            'slug'       => 'products/source',
            'with_front' => false,
        ],
    ]);
}, 10);

add_action('init', static function (): void {
    $product_types = [
        'Suiting/Shirting' => 'suiting-shirting',
        'Functional'       => 'functional',
        'Fleece'           => 'fleece',
        'Denim'            => 'denim',
        'Outdoor'          => 'outdoor',
        'Workwear'         => 'workwear',
        'Technical'        => 'technical',
    ];

    foreach ($product_types as $name => $slug) {
        if (!term_exists($name, 'product_type')) {
            wp_insert_term($name, 'product_type', ['slug' => $slug]);
        }
    }

    $sources = [
        'LED'   => 'led',
        'Bulb'  => 'bulb',
        'Laser' => 'laser',
    ];

    foreach ($sources as $name => $slug) {
        if (!term_exists($name, 'lighting_source')) {
            wp_insert_term($name, 'lighting_source', ['slug' => $slug]);
        }
    }
}, 20);

add_action('acf/init', static function (): void {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_fw_stage_lighting_specs',
        'title' => 'Stage Lighting Specifications',
        'fields' => [
            [
                'key' => 'field_fw_wattage',
                'label' => 'Wattage',
                'name' => 'wattage',
                'type' => 'text',
                'required' => 1,
                'placeholder' => 'e.g. 200W / 350W',
            ],
            [
                'key' => 'field_fw_channels',
                'label' => 'Channels',
                'name' => 'channels',
                'type' => 'text',
                'required' => 1,
                'placeholder' => 'e.g. 16CH / 24CH',
            ],
            [
                'key' => 'field_fw_prism',
                'label' => 'Prism',
                'name' => 'prism',
                'type' => 'text',
                'required' => 0,
                'placeholder' => 'e.g. 8-facet / 16-facet',
            ],
            [
                'key' => 'field_fw_ip_rating',
                'label' => 'IP Rating',
                'name' => 'ip_rating',
                'type' => 'select',
                'required' => 1,
                'choices' => [
                    'IP20' => 'IP20',
                    'IP54' => 'IP54',
                    'IP65' => 'IP65',
                    'IP67' => 'IP67',
                ],
                'default_value' => 'IP20',
                'allow_null' => 0,
                'ui' => 1,
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
});
