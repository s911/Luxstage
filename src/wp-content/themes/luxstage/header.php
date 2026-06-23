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
  <div class="fw-b2b-container">
    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
      <?php bloginfo('name'); ?>
    </a>
  </div>
</header>
