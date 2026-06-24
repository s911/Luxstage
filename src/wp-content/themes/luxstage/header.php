<?php
/**
 * Theme header.
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="fw-b2b-header">
  <div class="fw-b2b-container lux-header__inner">
    <a class="lux-logo" href="<?php echo esc_url(home_url('/')); ?>" rel="home">
      <?php bloginfo('name'); ?>
    </a>
    <nav class="lux-nav" aria-label="<?php esc_attr_e('Primary navigation', 'luxstage'); ?>">
      <?php
      wp_nav_menu([
          'theme_location' => 'primary',
          'container' => false,
          'menu_class' => false,
          'fallback_cb' => 'luxstage_primary_menu_fallback',
          'items_wrap' => '%3$s',
          'depth' => 1,
      ]);
      ?>
    </nav>
  </div>
</header>
