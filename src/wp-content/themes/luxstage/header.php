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
      <a href="<?php echo esc_url(home_url('/products/')); ?>"><?php esc_html_e('Products', 'luxstage'); ?></a>
      <a href="<?php echo esc_url(home_url('/applications/')); ?>"><?php esc_html_e('Applications', 'luxstage'); ?></a>
      <a href="<?php echo esc_url(home_url('/downloads/catalogs/')); ?>"><?php esc_html_e('Downloads', 'luxstage'); ?></a>
      <a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact', 'luxstage'); ?></a>
    </nav>
  </div>
</header>
