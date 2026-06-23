<?php
/**
 * Theme bootstrap for Luxstage presentation layer.
 *
 * Business logic is intentionally moved to MU plugin:
 * wp-content/mu-plugins/luxstage-core.php
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LUXSTAGE_THEME_VERSION', '1.0.0');

if (!function_exists('luxstage_field')) {
    function luxstage_field(string $name, int $post_id = 0): mixed
    {
        if (!function_exists('get_field')) {
            return '';
        }

        return get_field($name, $post_id ?: get_the_ID());
    }
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
